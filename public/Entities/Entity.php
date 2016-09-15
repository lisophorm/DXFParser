<?php \Entities;

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