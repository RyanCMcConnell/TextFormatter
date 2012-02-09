<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder;

use InvalidArgumentException;

abstract class ConfigurableItem implements Configurable, Item
{
	public function __get($k)
	{
		return $this->getOption($k);
	}

	public function __set($k, $v)
	{
		$this->setOption($k, $v);
	}

	/**
	* Return the value of an option
	*
	* @param  string $optionName
	* @return mixed
	*/
	public function getOption($optionName)
	{
		if (!property_exists($this, $optionName))
		{
			throw new InvalidArgumentException("Option '" . $optionName . "' does not exist");
		}

		return $this->$optionName;
	}

	/**
	* Get all of this object's options
	*
	* @return array
	*/
	public function getOptions()
	{
		return get_object_vars($this);
	}

	/**
	* Set a single option for this object
	*
	* @param string $optionName
	* @param mixed  $optionValue
	*/
	public function setOption($optionName, $optionValue)
	{
		$methodName = 'set' . ucfirst($optionName);

		// Look for a setter, e.g. setDefaultChildRule()
		if (method_exists($this, $methodName))
		{
			return $this->$methodName($optionValue);
		}

		// If the property already exists, preserve its type
		if (isset($this->$optionName))
		{
			// If this is a Collection, we clear its content then add every item in order
			if ($this->$optionName instanceof Collection)
			{
				$this->$optionName->clear();

				foreach ($optionValue as $itemName => $item)
				{
					$this->$optionName->add($itemName, $item);
				}

				return;
			}

			settype($optionValue, gettype($this->$optionName));
		}

		$this->$optionName = $optionValue;
	}

	/**
	* Set several options for this object
	*
	* @param array $options
	*/
	public function setOptions(array $options)
	{
		foreach ($options as $optionName => $optionValue)
		{
			$this->setOption($optionName, $optionValue);
		}
	}
}