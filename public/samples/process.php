<?php namespace Sites\Admin\Controllers\Tools;

use \App\Showhub\Models\Object;
use \App\Showhub\Models\Occupant;
use \App\Showhub\Models\Plan;
use \App\Showhub\Models\Plot;

use \App\Core\Classes\DxfParser\DxfParser;

use \Illuminate\Support\Collection;

use \DB;
use \Input;
use \Redirect;
use \Session;
use \UnityServer;
use \UpdateListener;
use \View;

class PlanImportController extends \Sites\Admin\Controllers\BaseController {

	// /tools/plan-import GET
	public function getIndex()
	{
		$plans = Plan::all();

		//make view
		return View::make('sites.admin.tools.planImport.planImport')->with([
			'plans' => $plans
		]);
	}

	// /tools/plan-import POST
	public function postIndex()
	{
		//godlike
		ini_set('memory_limit', '512M');
		set_time_limit(900);

		//make sure plan id is set
		$plan_id = Input::get('plan_id');
		if(!$plan_id)
		{
			return Redirect::to('tools/plot-import');
		}
		$plan = \App\Showhub\Models\Plan::find($plan_id);
		$plan_settings = $plan->settings;

		//import parts?
		$import_plots = (bool) Input::get('import_plots');
		$import_objects = (bool) Input::get('import_objects');
		$delete_missing_plots = (bool) Input::get('delete_missing_plots');

		//storage variable
		$entities = [];

		//get dxf file
		$dxf_raw = file_get_contents(Input::file('dxf_raw')->getPathName());

		//initiate dxf parser
		$dxf = new DxfParser($dxf_raw);
		$entities_dxf = $dxf->getEntities();

		//cycle through entities
		$sanity_check_errors = [];
		$entities_simple = [];
		foreach($entities_dxf as $entity_dxf)
		{
			$entity = DxfParser::getEntityObject($entity_dxf);

			//no entity - type not supported
			if(!$entity) continue;

			//only process text and polylines
			if(!in_array($entity->type, ['TEXT', 'LWPOLYLINE'])) continue;

			//ignore unsupported layers
			foreach(['object', 'plot', 'plot_ref'] as $layer_type)
			{
				if(strpos(strtolower($entity->layer), '#'.$layer_type) === 0)
				{
					$entity->layer_type = $layer_type;
					continue;
				}
			}
			if(!$entity->layer_type) continue;

			//skip?
			if(!$import_objects && $entity->layer_type == 'object'
				|| !$import_plots && in_array($entity->layer_type, ['plot', 'plot_ref']))
			{
				continue;
			}

			//process layer name to get object properties
			$entity->properties = [];
			$layer_properties = explode('#', trim($entity->layer, '#'));
			foreach($layer_properties as $property)
			{
				$property_value = false;
				if(preg_match('/(.+)\[(.+)\]/', $property, $match))
				{
					$property = $match[1];
					$property_value = explode('+', $match[2]);
				}

				$entity->properties = array_merge($entity->properties, [
					$property => $property_value
				]);
			}

			//stand numbers
			if($entity->layer_type == 'plot_ref')
			{
				//check type
				if($entity->type != 'TEXT')
				{
					$sanity_check_errors[] = 'DXF error: stand number is not text object: '.json_encode($entity->coords).' : '.$entity->layer;
				}

				//no value?
				if(!$entity->value)
				{
					$sanity_check_errors[] = 'DXF error: stand number has no value: '.json_encode($entity->coords).' : '.$entity->layer;
				}

				//save
				$entities[$entity->layer_type][] = $entity;
				$entities_simple[$entity->layer_type][] = [
					'id' => $entity->id,
					'coord' => $entity->coords,
					'value' => $entity->value
				];
			}

			//plots and objects
			else if(in_array($entity->layer_type, ['object', 'plot']))
			{
				//check type
				if($entity->type != 'LWPOLYLINE' && $entity->type != 'TEXT')
				{
					$sanity_check_errors[] = 'DXF error: '.ucfirst($entity->layer_type).' is not polyline or text object: '.json_encode($entity->coords).' : '.$entity->layer;
				}

				//closed?
				if($entity->type == 'LWPOLYLINE' && !$entity->isClosed)
				{
					$sanity_check_errors[] = 'DXF error: '.ucfirst($entity->layer_type).' polyline object is not closed: '.json_encode($entity->coords).' : '.$entity->layer;
				}

				//plots
				if($entity->layer_type == 'plot')
				{
					$entities[$entity->layer_type][] = $entity;
					$entities_simple[$entity->layer_type][] = [
						'id' => $entity->id,
						'coords' => $entity->coords
					];
				}

				//objects
				else
				{
					$entities[$entity->layer_type][] = $entity;
				}
			}
		}

		//sanity check errors
		if($sanity_check_errors)
		{
			return Redirect::to('tools/plan-import')->with([
				'errors' => new Collection($sanity_check_errors)
			]);
		}

		//get stands -> stand_numbers - using do server
		$post_data = [
			'import_data' => json_encode($entities_simple)
		];
		$context  = stream_context_create([
			'http' => [
				'method'  => 'POST',
				'header'  => 'Content-type: application/x-www-form-urlencoded',
				'content' => http_build_query($post_data)
			]
		]);
		$geometry_import_objects = file_get_contents('http://127.0.0.1:3000/geometry/plan-import', false, $context);

		//data errors list
		$geometry_import_objects_errors = [];

		//json decode response
		$geometry_import_objects = json_decode($geometry_import_objects, true);

		//merge into $entities[$object_type] - detect objects with missing numbers and coord clashes
		if($import_plots)
		{
			foreach($entities['plot'] as $entity_plot)
			{
				//assign stand number
				if(isset($geometry_import_objects['plots'][$entity_plot->id]) && $geometry_import_objects['plots'][$entity_plot->id]['ref'])
				{
					$geometry_import_plot = $geometry_import_objects['plots'][$entity_plot->id];
					$entity_plot->ref = $geometry_import_plot['ref'];

					//any clashes?
					if(isset($geometry_import_plot['geometry_intersects']['overlaps']))
					{
						foreach($geometry_import_plot['geometry_intersects']['overlaps'] as $intersecting_plot_id)
						{
							$geometry_import_objects_errors[] = 'DXF error: '.$entity_plot->ref.' overlaps with '.$geometry_import_objects['plots'][$intersecting_plot_id]['ref'];
						}
					}
					if(isset($geometry_import_plot['geometry_intersects']['contains']))
					{
						foreach($geometry_import_plot['geometry_intersects']['contains'] as $intersecting_plot_id)
						{
							$geometry_import_objects_errors[] = 'DXF error: '.$entity_plot->ref.' contains '.$geometry_import_objects['plots'][$intersecting_plot_id]['ref'];
						}
					}
				}
				//otherwise add error
				else
				{
					$geometry_import_objects_errors[] = 'DXF error: Missing stand number: '.$entity_plot->layer.' @ '.json_encode($entity_plot->coords);
				}
			}

			//numbers used more than once - we'll check here and when integrating into existing data
			$used_numbers = [];
			foreach($geometry_import_objects['plots'] as $geometry_import_plot)
			{
				if(in_array($geometry_import_plot['ref'], $used_numbers))
				{
					$geometry_import_objects_errors[] = 'DXF error: Repeat stand number: '.print_r($geometry_import_plot['ref'], true);
				}
				$used_numbers[] = $geometry_import_plot['ref'];
			}
		}

		//report errors
		if($geometry_import_objects_errors)
		{
			return Redirect::to('tools/plan-import')->with([
				'errors' => new Collection($geometry_import_objects_errors)
			]);
		}

		//dxf stage done, let's move onto merging the data

		//storage variables for data and proposed actions
		$import_actions = [
			'delete' => [],
			'create' => [],
			'update' => []
		];

		if($import_plots)
		{
			//cycle through plots
			$plots = Plot::with('occupants')->where('plan_id', $plan_id)->get();

			//cycle through existing records
			foreach($plots as $plot)
			{
				//should item be removed?
				$remove = true;
				foreach($geometry_import_objects['plots'] as $geometry_import_plot)
				{
					if($geometry_import_plot['ref'] == $plot->plot_ref)
					{
						$remove = false;
						break;
					}
				}

				//remove it
				if($delete_missing_plots && $remove)
				{
					$delete_action = [
						'id' => $plot->id,
						'ref' => $plot->plot_ref,
						'children' => [],
						'data' => [
							'type' => $plot->type,
							'manager_tags' => $plot->manager_tags,
							'geometry.coords' => $plot->geometry->coords->zeroOffset,
							'geometry.width' => $plot->geometry->width,
							'geometry.height' => $plot->geometry->height,
							'geometry.area' => $plot->geometry->area
						]
					];

					//remove occupants too
					foreach($plot->occupants as $occupant)
					{
						$delete_action['children'][] = [
							'id' => $occupant->id,
							'name' => $occupant->name
						];
					}

					//remember
					$import_actions['delete'][] = $delete_action;
				}
			}

			foreach($entities['plot'] as $entity_plot)
			{
				$geometry_import_plot = $geometry_import_objects['plots'][$entity_plot->id];

				//basic data
				$plot_action = [
					'ref' => $geometry_import_plot['ref']
				];

				$plot = Plot::where('plot_ref', '=', $entity_plot->ref)->where('plan_id', '=', $plan_id)->first();

				//replace?
				if(!$plot)
				{
					$plot_action_type = 'create';
					$plot_action['id'] = null;

					//create-only data
					$plot_action['data']['manager_tags'] = isset($entity_plot->properties['tags']) ? $entity_plot->properties['tags'] : [];
				}
				else
				{
					$plot_action_type = 'update';
					$plot_action['id'] = $plot->id;

					//data
					//$plot_action['original_data']['type'] = $plot->type;
					$plot_action['original_data']['geometry.coords'] = $plot->geometry->coords->zeroOffset;
				}

				if(!isset($entity_plot->properties['plot'][0]))
				{
					dd('No plot type defined');
				}

				//plot type
				if(isset($entity_plot->properties['plot'][0]))
				{
					$plot_action['data']['type'] = strtoupper($entity_plot->properties['plot'][0]);
				}
				//use default plot type if plot is new
				else if(!$plot)
				{
					$plot_action['data']['type'] = $plan_settings->defaultPlotType;
				}

				//data
				$plot_action['data']['geometry.coords'] = $entity_plot->coords;

				//remember
				$import_actions[$plot_action_type][] = $plot_action;
			}
		}

		//structure data
		$object_types = [];
		$objects_data = [];
		if($import_objects)
		{
			foreach($entities['object'] as $entity_object)
			{
				$type = strtoupper($entity_object->properties['object'][0]);

				if(!isset($object_types[$type])) $object_types[$type] = 0;
				$object_types[$type]++;

				$data = [];

				//per-type options
				if(in_array($type, ['ICON', 'LABEL']))
				{
					$data['font_size'] = $entity_object->fontSize;
					$data['color'] = isset($entity_object->properties['color'][0]) ? '#'.str_replace('#', '', $entity_object->properties['color'][0]) : '#333';
					$data['rotation'] = $entity_object->rotation;
					$data['font'] = $entity_object->style;
					$data['value'] = $entity_object->value;
				}
				if($type == 'HEIGHT_ZONE')
				{
					$data['limit'] = isset($entity_object->properties['limit'][0]) ? $entity_object->properties['limit'][0] : null;
				}
				if(in_array($type, ['ICON', 'LABEL', 'HEIGHT_ZONE', 'REGION', 'SHAPE']))
				{
					$data['background'] = isset($entity_object->properties['background'][0]) && $entity_object->properties['background'][0] != 'none' ? '#'.str_replace('#', '', $entity_object->properties['background'][0]) : null;
					$data['border'] = isset($entity_object->properties['border'][0]) && $entity_object->properties['border'][0] != 'none' ? '#'.str_replace('#', '', $entity_object->properties['border'][0]) : null;
				}
				if(in_array($type, ['FLOOR', 'REGION', 'SHAPE']))
				{
					if(isset($entity_object->properties['focusable'][0]))
					{
						$data['focusable'] = $entity_object->properties['focusable'][0];
					}
				}
				$objects_data[] = [
					'type' => $type,
					'coords' => $entity_object->coords,
					'data' => $data
				];
			}
		}

		//save data
		Session::put('admin.tools.planImport', [
			'plan_id' => $plan_id,
			'plan' => $plan,
			'import_actions' => $import_actions,
			'import_plots' => $import_plots,
			'import_objects' => $import_objects,
			'delete_missing_plots' => $delete_missing_plots,
			'object_types' => $object_types,
			'objects_data' => $objects_data
		]);

		//success
		return Redirect::to('tools/plan-import/confirm');
	}

	// /tools/plot-import/confirm GET
	public function getConfirm()
	{
		return View::make('sites.admin.tools.planImport.planImportConfirm')->with(Session::get('admin.tools.planImport'));
	}

	// /tools/plot-import/confirm POST
	public function postConfirm()
	{
		//godlike
		ini_set('memory_limit', '1G');
		set_time_limit(1800);

		//disable update listener
		//UpdateListener::disableFinishProcessor('GeometryFinishProcessor');
		UpdateListener::disableNotifier('GossipNotifier');
		//UpdateListener::disableAllNotifiers();

		//run in transaction
		DB::transaction(function()
		{
			\Mail::pretend(); //why? To stop occupants getting deletion notices

			//get data
			$plan_id = Session::get('admin.tools.planImport.plan_id');
			$import_actions = Session::get('admin.tools.planImport.import_actions');
			$import_plots = Session::get('admin.tools.planImport.import_plots');
			$import_objects = Session::get('admin.tools.planImport.import_objects');
			$delete_missing_plots = Session::get('admin.tools.planImport.delete_missing_plots');
			$objects_data = Session::get('admin.tools.planImport.objects_data');

			if($import_plots)
			{
				if($delete_missing_plots)
				{
					//removals
					foreach($import_actions['delete'] as $import_action)
					{
						//children - occupants
						foreach($import_action['children'] as $child_data)
						{
							Occupant::find($child_data['id'])->delete();
						}

						//delete
						Plot::find($import_action['id'])->delete();
					}
				}

				//update
				foreach($import_actions['update'] as $import_action)
				{
					$plot = Plot::find($import_action['id']);
					$plot->base_geometry = [
						'coords' => $import_action['data']['geometry.coords'],
						'origin' => [0,0],
						'rotation' => 0
					];
					unset($import_action['data']['geometry.coords']);

					//data
					foreach($import_action['data'] as $key => $value)
					{
						if(strpos($key, '.') !== false)
						{
							$keys = explode('.', $key);
							$plot->$keys[0] = array_merge((array)$plot->$keys[0], [
								$keys[1] => $value
							]);
						}
						else
						{
							$plot->$key = $value;.getEn
						}
					}

					//save
					//$plot->delayIntersectionsProcessing();
					$plot->save();
				}

				//create
				foreach($import_actions['create'] as $import_action)
				{
					$plot = new Plot;
					$plot->plot_ref = $import_action['ref'];
					$plot->plan_id = $plan_id;
					$plot->base_geometry = [
						'coords' => $import_action['data']['geometry.coords'],
						'origin' => [0,0],
						'rotation' => 0
					];
					unset($import_action['data']['geometry.coords']);

					//data
					foreach($import_action['data'] as $key => $value)
					{
						if(strpos($key, '.') !== false)
						{
							$keys = explode('.', $key);
							$plot->$keys[0] = array_merge((array)$plot->$keys[0], [
								$keys[1] => $value
							]);
						}
						else
						{
							$plot->$key = $value;
						}
					}

					if(!$plot->type)
					{
						$plot->type = 'DEFAULT';
					}

					//save
					//$plot->delayIntersectionsProcessing();
					$plot->save();
				}
			}

			//structures
			if($import_objects)
			{
				//removal
				if(Input::get('objects_remove_all'))
				{
					//dd('objects_remove_all', Input::get('objects_remove_all'));
					Object::where('plan_id', $plan_id)->whereNotIn('type', ['MEDIA_OBJECT', 'BLOCKING_WALKWAY'])->forceDelete();
				}
				else
				{
					if(Input::get('objects_remove'))
					{
						foreach(Input::get('objects_remove') as $type => $remove)
						{
							if($remove)
							{
								//dd('objects_remove', Input::get('objects_remove'));
								Object::where('plan_id', $plan_id)->where('type', $type)->forceDelete();
							}
						}
					}
				}

				//imports
				$objects_import = (array) Input::get('objects_import');
				foreach($objects_data as $object_data)
				{
					if(!isset($objects_import[$object_data['type']]) || !$objects_import[$object_data['type']])
					{
						continue;
					}

					$object = new Object;
					$object->plan_id = $plan_id;
					$object->type = $object_data['type'];
					$object->base_geometry = [
						'coords' => $object_data['coords'],
						'origin' => [0,0],
						'rotation' => 0
					];
					$object->data = $object_data['data'];

					//save
					//$object->delayIntersectionsProcessing();
					$object->save();
				}
			}

			//restart unity server
			UnityServer::restartAfter($plan_id);
		});

		//success
		return Redirect::to('tools/plan-import')->with([
			'success' => 'Plan import was successful.'
		]);
	}
}