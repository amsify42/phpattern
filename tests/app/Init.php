<?php

namespace App;

use ReflectionFunction;
use ReflectionMethod;
use PHPattern\Request\Form;
use PHPattern\Request;
use PHPattern\Config;
use PHPattern\Script;
use PHPattern\Router;
use PHPattern\Boot;

class Init extends Boot
{
	/**
	 * Route info for the requested URI
	 * @var string
	 */
	private $route = NULL;
	/**
	 * Default response code
	 * @var integer
	 */
	private $responseCode = 404;
	/**
	 * Default response message
	 * @var string
	 */
	private $responseMessage = 'Invalid route';
	
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

	public function acquireRequest()
	{
		if(Request::isCLI())
		{
			$console = new \PHPattern\Console();
			return $console->output();
		}
		else
		{
			/**
			 * Preload logic from App\Core\Boot
			 */
			$this->_preload();
			/**
			 * Get route for the requested URI
			 */
			$this->route = Router::getRoute($this->autoRoute);

			if($this->route)
			{
				if(!isset($this->route['callback']) || !$this->route['callback'])
				{
					if(in_array($this->route['class'], $this->escapeGlobalMiddlewares))
					{
						$this->middlewares = [];
					}
				}

				if(!empty($this->route['middleware']))
				{
					$this->middlewares = array_merge($this->middlewares, $this->route['middleware']);
				}
			}

			return $this->doAction();
		}
	}

	private function doAction()
	{
		$reflectAction = NULL;
		$action    	   = NULL;
		$formRequest   = NULL;
		$notAllowed    = false;
		if(isset($this->route['callback']) && $this->route['callback'])
		{
			$reflectAction = new ReflectionFunction($this->route['callback']);
		}
		else
		{
			$class  = (isset($this->route['class']) && trim($this->route['class']))? trim($this->route['class']): '';
			$method = (isset($this->route['method']) && trim($this->route['method']))? trim($this->route['method']): 'index';
			if($class && $method)
			{
				$action = new $class();
				if(method_exists($action, $method))
				{
					$reflectAction = new ReflectionMethod($class, $method);
				}	
			}
		}

		if($reflectAction)
		{
			$params = isset($this->route['params'])? $this->route['params']: [];
			if($reflectAction->getNumberOfParameters()> 0)
			{
				foreach($reflectAction->getParameters() as $param)
				{
					/**
					 * Param is of type some class
					 */
					if($param->getClass())
					{
						$paramClass  = "\\".$param->getClass()->name;
						$formRequest = new $paramClass();
						/**
						 * If the param instance is of type App\Core\Request\Request\From
						 */
						if($formRequest instanceof Form)
						{
							if($this->isAllowed($params, $formRequest))
							{
								/**
								 * If form request is not validated based on rules
								 */
								if(!$formRequest->validated())
								{
									return $formRequest->responseErrors();
								}
							}
							else
							{
								$formRequest = NULL;
								$notAllowed  = true;
							}
						}
					}
				}					
			}
			/**
			 * If form request is one of the parameter
			 */
			if($formRequest)
			{
				$params['request'] = $formRequest;
			}
			/**
			 * Check allowed when form request is not parameter
			 */
			else if($notAllowed || !$this->isAllowed($params))
			{
				return $this->doResponse();
			}
			/**
			 * If parameters of method is equal to parameters evaluated
			 */
			if($reflectAction->getNumberOfParameters() == sizeof($params))
			{
				if(isset($this->route['include']) && !empty($this->route['include']))
				{
					Script::include($this->route['include']);
				}
				if($action)
				{
					return $reflectAction->invokeArgs($action, $params);
				}
				else
				{
					return $reflectAction->invokeArgs($params);	
				}
			}
		}

		return $this->doResponse();
	}

	private function isAllowed($params=[], $requestData=NULL)
	{
		$requestData = (!$requestData)? Request::data(): $requestData;
		/**
		 * Append uri params to request data
		 */
		if(sizeof($params)> 0)
		{
			$requestData->addParams($params);
		}
		/**
		 * If action class can be escaped or validated middlewares
		 */
		if($this->processMiddlewares($requestData))
		{
			return true;
		}
		return false;
	}

	private function doResponse()
	{
		$response = response()->setCode($this->responseCode);
		if(Config::get('app.response_type') == 'html')
		{
			return $response->view('errors.404');
		}
		else
		{
			return $response->json($this->responseMessage);
		}
	}

	private function processMiddlewares($requestData)
	{
		if(sizeof($this->middlewares)> 0)
		{
			foreach($this->middlewares as $mk => $middleware)
			{
				$instance = new $middleware();
				if(!$instance->process($requestData))
				{
					$this->responseCode 	= $instance->getCode();
					$this->responseMessage 	= $instance->getMessage();
					return false;
				}
			}
		}
		return true;
	}
}