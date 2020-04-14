<?php

namespace App;

use App\Core\Boot;
use PHPattern\Script;

class Init extends Boot
{	
	function __construct()
	{
		/**
		 * Define global constants
		 */
		define('DS', DIRECTORY_SEPARATOR);
		define('APP_PATH', __DIR__);
		/**
		 * Auto include php files
		 */
		Script::include($this->autoIncludes, true);	
	}
}