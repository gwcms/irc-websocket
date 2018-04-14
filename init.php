<?php

ini_set('display_errors', 1);

error_reporting(E_ALL);

define('ROOT_DIR', __DIR__.'/');


define('AUTOLOAD_DIR', __DIR__.'/lib/');

function __autoload($class_name) 
{
	$parts = explode('\\', strtolower($class_name));
	$file = AUTOLOAD_DIR.implode('/', $parts) . '.class.php';
	
	if(!file_exists($file))
		if($files = glob(AUTOLOAD_DIR . "*/" . basename($file)))
			if(count($files) > 1){
				die('Multiple files found '.basename($file).' cant decide wich to load');
			}elseif(count($files) == 1){
				$file = $files[0];
			}else{
				die("Cant autoload ".implode('/', $parts) . '.class.php');
			}
	
	//echo "AL $class_name - $file \n";
	
	include $file;
}

GW::init();