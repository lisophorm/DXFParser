<?php 

include 'php2js.php'

class DxfParser
{
	protected $__dxf = null;
	protected $__entities = null;
	protected static $__entityTypesString = '3DFACE|3DSOLID|ACAD_PROXY_ENTITY|ARC|ATTDEF|ATTRIB|BODY|CIRCLE|DIMENSION|ELLIPSE|HATCH|HELIX|IMAGE|INSERT|LEADER|LIGHT|LINE|LWPOLYLINE|MLINE|MLEADER|MLEADERSTYLE|MTEXT|OLEFRAME|OLE2FRAME|POINT|POLYLINE|RAY|REGION|SECTION|SEQEND|SHAPE|SOLID|SPLINE|SUN|SURFACE|TABLE|TEXT|TOLERANCE|TRACE|UNDERLAY|VERTEX|VIEWPORT|WIPEOUT|XLINE';

	public function __construct($dxf)
	{
		$this->__dxf = $dxf;
	}
	
	public function getEntities()
	{
		if($this->__entities)
		{
			return $this->__entities;
		}

		//get entities section
		preg_match("/\r\n\s*0\r\nSECTION\r\n\s*2\r\nENTITIES(.*)\r\n\s*0\r\nENDSEC/s", $this->__dxf, $match);
		$entities_section = $match[1];

		//split into entities
		$entities_section_with_breaks = preg_replace("/(\r\n\s*0\r\n)(".self::$__entityTypesString.")/", '[[ENTITY_SEPARATOR]]$1$2', $entities_section);
		$entities = explode("[[ENTITY_SEPARATOR]]", $entities_section_with_breaks);
		array_shift($entities);

		$this->__entities = $entities;

		return $entities;
	}

	public static function getEntityObject($entity)
	{
		$type = self::getType($entity);
		if($type == 'LWPOLYLINE')
		{
			return new Entities\Polyline($entity);
		}
		else if($type == 'TEXT')
		{
			return new Entities\Text($entity);
		}

		return null;
	}

	public static function getType($entity)
	{
		if(preg_match("/\r\n\s*0\r\n(".self::$__entityTypesString.")/", $entity, $matches))
		{
			return $matches[1];
		}

		return null;
	}
}


class Text extends Entity
{
	protected function __getValue()
	{
		return $this->__getProperty('1');
	}

	protected function __getFontSize()
	{
		return round($this->__getProperty('40'), 2);
	}

	protected function __getStyle()
	{
		$style = $this->__getProperty('7');

		if(!$style)
		{
			$style = 'STANDARD';
		}

		return $style;
	}

	protected function __getRotation()
	{
		$rotation = $this->__getProperty('50');

		if(!$rotation)
		{
			$rotation = 0;
		}

		return $rotation;
	}

	protected function __getCoords()
	{
		return [
			round($this->__getProperty('10'), 2), round($this->__getProperty('20'), 2)
		];
	}

	protected function __getGeoString()
	{
		return 'POINT('.$this->coords[0].' '.$this->coords[1].')';
	}
}

class Polyline extends Entity
{
	protected function __getVertices()
	{
		return $this->__getProperty('90');
	}

	protected function __getIsClosed()
	{
		return $this->__getProperty('70');
	}

	protected function __getCoords()
	{
		$x_coords = $this->__getProperty('10', true);
		$y_coords = $this->__getProperty('20', true);
		$coords = [];
		foreach($x_coords as $key => $value)
		{
			$coords[] = [
				round($x_coords[$key], 4), round($y_coords[$key], 4)
			];
		}

		//force unclosing of polygons
		$first_coord = $coords[0];
		$last_coord = $coords[$key];
		if($last_coord[0] == $first_coord[0] && $last_coord[1] == $first_coord[1])
		{
			array_pop($coords);
		}

		/*
		//coords might not be closed - close them
		$first_coord = $coords[0];
		$last_coord = $coords[$key];
		if($last_coord[0] != $first_coord[0] && $last_coord[1] != $first_coord[1])
		{
			$coords[] = $first_coord;
		}
		*/

		return $coords;
	}

	protected function __getGeoString()
	{
		//get coords string
		$coords = '';
		foreach($this->coords as $coord_pair)
		{
			$coords .= $coord_pair[0].' '.$coord_pair[1].',';
		}

		return 'POLYGON(('.$coords.'))';
	}
}

class Entity
{
	protected $__entity = null;
	protected $__properties = [];
	protected $__cache = [];

	public function __construct($entity)
	{
		$this->__entity = $entity;

		//get entity properties
		$entity_lines = explode("\r\n", trim($entity));
		for($i = 0; $i < count($entity_lines); $i += 2)
		{
			$this->__properties[] = [
				trim($entity_lines[$i]),
				trim($entity_lines[$i + 1])
			];
		}
	}

	//get property - done like this as properties can be repeated (like in coord points)
	protected function __getProperty($ref, $multiple = false)
	{
		$properties = [];
		foreach($this->__properties as $property)
		{
			if($property[0] === $ref)
			{
				$properties[] = $property[1];
			}
		}

		if($multiple)
		{
			return $properties;
		}

		if(isset($properties[0]))
		{
			return $properties[0];
		}
		return null;
	}

	protected function __getType()
	{
		return $this->__getProperty('0');
	}

	protected function __getId()
	{
		return $this->__getProperty('5');
	}

	protected function __getLayer()
	{
		return $this->__getProperty('8');
	}

	protected function __getGeoString()
	{
		return null;
	}

	public function __get($property)
	{
		//get from cache
		if(isset($this->__cache[$property]))
		{
			return $this->__cache[$property];
		}

		//get data
		$value = null;
		$method_name = '__get'.ucfirst($property);
		if(method_exists($this, $method_name))
		{
			$value = $this->{$method_name}();
		}

		//set to cache
		$this->__cache[$property] = $value;

		return $value;
	}

	public function __set($property, $value)
	{
		$this->__cache[$property] = $value;
	}
}