<?php

namespace App\Core;

use PHPattern\Core;
use PHPattern\Router;

class Boot extends Core
{
	/**
	 * Decides whether auto route is allowed or not.
	 * @var boolean
	 */
	protected $autoRoute = false;
	/**
	 * Including file paths which cannot be loaded through autoloading of classes
	 * @var array
	 */
	protected $autoIncludes = [
								//
							];
	/**
	 * Middleware classes needs to be processed before reaching the Action
	 * @var array
	 */
	protected $middlewares = [
								//
							];						
	/**
	 * Actions to escape from global middlewares
	 * @var array
	 */
	protected $escapeGlobalMiddlewares = [
									//
								];

	/**
	 * We can write logic inside this function to execute before things get loaded for futher execution
	 */
	protected function _preload()
	{
		Router::URISegmentsType('id', 'number');
	}							
}