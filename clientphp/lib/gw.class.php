<?php
define('GW_GENERIC_ERROR', 100);

/**
 * 
 * @author wdm
 *
 * GateWay CMS namespace
 * 
 */
/* GW Context */
class GW_Context
{

	public $vars;

	function __set($var, $value)
	{
		$this->vars[$var] = $value;
	}

	function &__get($var)
	{
		return $this->vars[$var];
	}
}

class GW
{

	//nekintantys parametrai per visas aplikacijas
	static $settings;
	static $error_log;
	static $context;
	//jeigu prisijunges vartotojas developeris
	static $devel_debug;

	/**
	 * 
	 * @return GW_DB
	 */
	static function db()
	{
		if (!self::$context->db)
			self::$context->db = new GW_DB();

		return self::$context->db;
	}

	function &_($varname)
	{
		return self::$$varname;
	}

	static function init()
	{
		if (self::$context)
			return false; //already done

		include __DIR__ . '/../config/main.php';
		self::$context = new GW_Context;
	}

	static function &s($var_name, $value = Null)
	{
		$var = & self::$settings;
		$explode = explode('/', $var_name);

		foreach ($explode as $part)
			$var = & $var[$part];

		if ($value !== Null)
			$var = $value;

		return $var;
	}

	static function getInstance($class, $file = false)
	{
		static $cache;

		if (isset($cache[$class]))
			return $cache[$class];

		if ($file)
			include_once $file;

		$cache[$class] = self::initClass($class);

		return $cache[$class];
	}

	static function initClass($name)
	{
		$o = new $name();
		$o->db = self::$context->db;

		return $o;
	}
}
