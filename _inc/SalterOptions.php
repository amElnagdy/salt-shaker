<?php

namespace SaltShaker;

class SalterOptions
{

	private static $_instance = null;
	public array $options;

	public function __construct()
	{
		$tempSalterOptions = get_option('salt_shaker_options');
		$this->options = $tempSalterOptions ? $tempSalterOptions : [];
	}

	public function getOption(string $option_name): ?string
	{
		if (!isset($option_name)) {
			return null;
		}

		return isset($this->options[$option_name]) ? $this->options[$option_name] : null;
	}

	public function setOption(string $option_name, string $option_value)
	{
		if (!isset($option_name) || !isset($option_value)) {
			return;
		}

		$this->options[$option_name] = $option_value;

		update_option('salt_shaker_options', $this->options);
	}

	public function getSalterOptions()
	{
		return $this->options;
	}

	public static function getInstance()
	{
		if (!isset(self::$_instance)) {
			self::$_instance = new SalterOptions();
		}

		return self::$_instance;
	}
}
