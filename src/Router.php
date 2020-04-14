<?php

namespace PHPattern;

use Amsify42\PHPVarsData\Data\Evaluate;
use PHPattern\Router\Route;
use PHPattern\File\Json;
use PHPattern\Request;
use PHPattern\URL;
use PHPattern\File;

class Router
{
	/**
	 * Routes registering path
	 * @var string
	 */
	private static $path = APP_PATH.DS.'config'.DS.'routes.php';
	/**
	 * Temporary path for routes code
	 * @var string
	 */
	private static $temPath = __DIR__.DS.'generated'.DS.'temp_routes.php';
	/**
	 * Routes saving in json file path
	 * @var string
	 */
	private static $jsonPath = __DIR__.DS.'generated'.DS.'routes.json';
	/**
	 * Instance of json file
	 * @var JsonFile
	 */
	private static $jsonFile = NULL;
	/**
	 * $route keys for naming unnamed routes
	 * @var integer
	 */
	private static $routeKey = 0;
	/**
	 * Set the current route name while iterating the routes
	 * @var string
	 */
	private static $current = '';
	/**
	 * Check if routes are auto
	 * @var boolean
	 */
	private static $isAuto = true;
	/**
	 * Groups data
	 * @var array
	 */
	protected static $groups = [];
	/**
	 * List of routes
	 * @var array
	 */
	protected static $routes = [];
	/**
	 * Global uri segment types
	 * @var array
	 */
	protected static $segments = [];
	/**
	 * Active route name
	 * @var null
	 */
	private static $active = NULL;
	/**
	 * $prefixURI
	 * @var string
	 */
	private static $prefixURI = '';
	/**
	 * Active group name
	 * @var string
	 */
	private static $activeGroup = '';
	/**
	 * $groupClass
	 * @var string
	 */
	private static $groupClass = '';
	/**
	 * $middleware
	 * @var array
	 */
	private static $middleware = [];
	/**
	 * $include
	 * @var array
	 */
	private static $include = [];

	public static function newRoute($type)
	{
		self::$routeKey++;
		self::$routes[self::$routeKey] = new Route($type);
		return self::$routes[self::$routeKey];
	}

	public static function routeKey()
	{
		return self::$routeKey;
	}

	public static function prefixURI()
	{
		return self::$prefixURI;
	}

	public static function activeGroup()
	{
		return self::$activeGroup;
	}

	public static function groupClass()
	{
		return self::$groupClass;
	}

	public static function include()
	{
		return self::$include;
	}

	public static function getRoute($isAuto=true)
	{
		/**
		 * Get requested route URI
		 */
		$requestURI = URL::requestURI();
		/**
		 * Check if routes changed
		 */
		if(self::isRoutesEdited() || self::isAuto() != $isAuto)
		{
			self::$isAuto = $isAuto;
			self::saveRoutes();
		}

		$route = self::isRegistered($requestURI);
		/**
		 * If route is registered in /config/routes.php
		 */
		if($route)
		{
			if(isset($route['callback']) && $route['callback'])
			{
				if(!is_callable($route['callback']))
				{
					eval("\$route['callback'] = ".$route['callback'].";");
				}
			}
			return $route;
		}
		else if(!$route)
		{
			/**
			 * This gets the auto route info if available
			 */
			$route = self::autoRouteInfo($requestURI);
			if($route)
			{
				return $route;
			}
		}
		return NULL;
	}

	private static function isRoutesEdited()
	{
		$file = new File(self::$path);

		self::$jsonFile = new Json(self::$jsonPath);
		$jsonArray 		= self::$jsonFile->jsonArray([]);

		$lastEditedTime = $jsonArray->get('last_edited_time');
		if(!$lastEditedTime || $file->updatedTime() > $lastEditedTime)
		{
			return true;
		}
		else
		{
			$routes = $jsonArray->get('routes');
			if($routes && sizeof($routes) > 0)
			{
				self::$isAuto = $jsonArray->get('is_auto');
				self::$groups = $jsonArray->get('groups');
				self::$routes = $jsonArray->get('routes');
				return false;
			}
			return true;
		}
	}

	public static function isAuto()
	{
		return self::$isAuto;
	}

	public static function URISegmentsType($segments, $type)
	{
		if(is_array($segments))
		{
			if(sizeof($segments)> 0)
			{
				foreach($segments as $sk => $segment)
				{
					self::$segments[$segment] = $type;
				}
			}
		}
		else
		{
			self::$segments[$segments] = $type;
		}
	}

	private static function autoRouteInfo($requestURI)
	{
		if(!self::$isAuto)
		{
			return NULL;
		}
		$result 	= ['class' => '', 'params' => []];
		$routeArray = explode('/', $requestURI);
		$strings 	= [];
		foreach($routeArray as $rKey => $routeEl)
		{
			if(is_numeric($routeEl))
			{
				$result['params'][] = $routeEl;
			}
			else
			{
				$strings[] = $routeEl;
			}
		}
		$strings = array_filter($strings);
		if(sizeof($strings) > 1)
		{
			$result['method'] = dashes_to_camel_case(end($strings));
			array_pop($strings);
		}
		foreach($strings as $sKey => $string)
		{
			$result['class'] .= '\\'.dashes_to_camel_case($string, true);
		}
		$result['class'] = trim($result['class'], '\\');
		$result['class'] = 'App\\Actions\\'.$result['class'];
		return $result;
	}

	private static function saveRoutes()
	{
		$routesFile = new File(self::$path);
		$code 		= $routesFile->getContent();
		/**
		 * Converting custom syntax to PHP
		 */
		$code = str_replace("<?php", "<?php\nuse PHPattern\Router;\nuse PHPattern\Router\Route;\nuse App\Middlewares as Middlewares;\nuse App\Actions as Actions;", $code);
		/**
		 * Converting @ symbol delimited string to php version of concatenation
		 */
		$code = preg_replace("/,\s*@(.*?)\)/", ", '@$1')", $code);
		/**
		 * Converting @ symbol delimited string with class to php version of concatenation
		 */
		$code = preg_replace("/class\s*@(.*?)\)/", "class.'@$1')", $code);
		/**
		 * Executing the code
		 */
		$file = new File(self::$temPath);
		$file->saveContent($code);
		include_once self::$temPath;

		self::fixRouteNames();

		self::$jsonFile->saveData([
			'last_edited_time' 	=> time(),
			'is_auto' 			=> self::$isAuto,
			'groups' 			=> self::$groups,
			'routes' 			=> self::$routes
		]);
		$file->saveContent('');
	}

	private static function fixRouteNames()
	{
		$routes = [];
		foreach(self::$routes as $name => $route)
		{
			if($route->name && $name != $route->name)
			{
				$routes[$route->name] = $route;
			}
			else
			{
				$routes[$name] = $route;
			}
		}
		self::$routes = $routes;
	}

	private static function isRegistered($uri)
	{
		$target = NULL;
		if(sizeof(self::$routes)> 0)
		{
			$uriArray = explode('/', $uri);
			foreach(self::$routes as $rKey => $route)
			{
				if($route['pattern'] == $uri || $route['pattern'] == $uri.'/')
				{
					self::$active 	 = $rKey;
					$target 		 = $route;
					$target['params']= [];
					break;
				}
				else
				{
					$routeArray = explode('/', $route['pattern']);
					$result 	= self::matchPattern($uriArray, $routeArray);
					if($result['matched'])
					{
						self::validateURISegment($result['params'], isset($route['segments'])? $route['segments']: []);
						self::$active = $rKey;
						self::$routes[$rKey]['params'] 	= $result['params'];
						$target 						= self::$routes[$rKey];
						break;
					}
				}
			}
		}
		if($target)
		{
			if(Request::isRequest($target['type']))
			{
				$group 		 = isset($target['group'])? trim($target['group']): '';
				$isGroupSet  = ($group && isset(self::$groups[$group]))? true: false;
				/**
				 * Collect middlewares
				 */
				$middlewares = [];
				if(isset($target['middleware']) && !empty($target['middleware']))
				{
					if(is_array($target['middleware']))
					{
						$middlewares = $target['middleware'];
					}
					else
					{
						$middlewares = [$target['middleware']];
					}
				}

				if($isGroupSet && isset(self::$groups[$group]['middleware']))
				{
					if(is_array(self::$groups[$group]['middleware']))
					{
						$middlewares = array_merge($middlewares, self::$groups[$group]['middleware']);
					}
					else
					{
						$middlewares = array_merge($middlewares, [self::$groups[$group]['middleware']]);
					}
				}
				if(!empty($middlewares))
				{
					$middlewares = array_unique(array_filter($middlewares));
				}
				$target['middleware'] = $middlewares;

				/**
				 * Collect non class based files
				 */
				$includes = [];
				if(!empty($target['include']))
				{
					if(is_array($target['include']))
					{
						$includes = $target['include'];
					}
					else
					{
						$includes = [$target['include']];
					}
				}

				if($isGroupSet && isset(self::$groups[$group]['include']))
				{
					if(is_array(self::$groups[$group]['include']))
					{
						$includes = array_merge($includes, self::$groups[$group]['include']);
					}
					else
					{
						$includes = array_merge($includes, [self::$groups[$group]['include']]);
					}
				}
				if(!empty($includes))
				{
					$includes = array_unique(array_filter($includes));
				}
				$target['include'] = $includes;
				/**
				 * If route action class is grouped
				 */
				if(!isset($target['class']) || !trim($target['class']))
				{
					if($group && $isGroupSet && isset(self::$groups[$group]['class']))
					{
						$target['class'] = self::$groups[$group]['class'];
					}
				}

				return $target;
			}
			else
			{
				$target = NULL;
			}
		}
		return $target;
	}

	private static function getInclude()
	{
		return self::$include;
	}

	private static function matchPattern($uriArray, $routeArray)
	{
		$uriArray 	= array_values(array_filter($uriArray));
		$routeArray = array_values(array_filter($routeArray));
		$result 	= ['matched' => false, 'params' => []];
		if(sizeof($uriArray) == sizeof($routeArray))
		{
			$result['matched'] = true;
			foreach($uriArray as $uKey => $uri)
			{
				preg_match('/{(.*?)}/', $routeArray[$uKey], $matches);
				$matchCount = sizeof($matches);
				if($matchCount > 1)
				{
					$uriParam = isset($matches[1])? trim($matches[1]): '';
					if($uriParam)
					{
						$result['params'][$uriParam] = Evaluate::toValue($uri);
					}
					else
					{
						$result['params'][] = Evaluate::toValue($uri);	
					}
				}
				if($uri != $routeArray[$uKey] && !$matchCount)
				{
					$result['matched'] = false;
					break;
				}
			}
		}
		return $result;
	}

	private static function validateURISegment($params, $segments)
	{
		$errors = [];
		if(sizeof($params)> 0 && (sizeof($segments)> 0 || sizeof(self::$segments)> 0))
		{
			foreach($params as $param => $value)
			{
				$type = isset($segments[$param])? $segments[$param]: (isset(self::$segments[$param])? self::$segments[$param]: NULL);
				if($type)
				{
					switch($type)
					{
						case 'number':
							if(!is_numeric($value))
							{
								$errors[] = 'URI segment['.$param.'] must be a number';
							}
							break;	
						
						default:
							/**
							 * Here it will assume the type to be some regular expression
							 */
							if(!preg_match($type, $value))
							{
								$errors[] = 'URI segment['.$param.'] must be valid';
							}
							break;
					}
				}
			}
		}
		if(sizeof($errors)> 0)
		{
			render_response(response()->validationErrors($errors));
		}
	}

	public static function group($info, $callback)
	{
		$uri = '';
		if(is_array($info))
		{
			if(isset($info['prefix']))
			{
				$uri = $info['prefix'];
			}
			if(isset($info['class']))
			{
				self::$groupClass = $info['class'];
			}
			if(isset($info['middleware']))
			{
				self::$middleware = $info['middleware'];
			}
			if(isset($info['include']))
			{
				self::$include = $info['include'];
			}
		}
		else
		{
			$uri = $info;
		}
		self::$prefixURI = trim($uri, '/');
		if(self::$prefixURI)
		{
			self::$prefixURI = self::$prefixURI.'/';
			self::$activeGroup = preg_replace('/[^A-Za-z0-9\-]/', '', self::$prefixURI);
		}
		else
		{
			self::$activeGroup = '';	
		}

		/**
		 * If route grouping is happening
		 */
		if(self::$activeGroup)
		{
			self::$groups[self::$activeGroup] = [];
			if(self::$groupClass)
			{
				self::$groups[self::$activeGroup]['class'] = self::$groupClass;
			}
			if(!empty(self::$include))
			{
				self::$groups[self::$activeGroup]['include'] = self::$include;
			}
			if(!empty(self::$middleware))
			{
				self::$groups[self::$activeGroup]['middleware'] = self::$middleware;
			}
		}

		if(is_callable($callback))
		{
			$callback();
		}
		self::$prefixURI  = '';
		self::$groupClass = '';
		self::$include 	  = [];
	}

	public static function routes()
	{
		return self::$routes;
	}

	public static function isRoute($name)
	{
		return isset(self::$routes[$name]);
	}

	public static function currentRoute($key='')
	{
		return ($key)? self::$routes[$active][$key]: self::$routes[$active];
	}

	public static function route($name, $key)
	{
		return isset(self::$routes[$name][$key])? self::$routes[$name][$key]: '';
	}
}