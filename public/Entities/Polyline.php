<?php namespace \Entities;

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