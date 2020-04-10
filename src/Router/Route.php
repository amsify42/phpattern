<?php

namespace PHPattern\Router;

use PHPattern\Script;
use PHPattern\Router;
use PHPattern\URL;
use ArrayAccess;
use JsonSerializable;

class Route implements ArrayAccess, JsonSerializable
{
	/**
	 * Route method type
	 * @var string
	 */
	private $type = '';
	/**
	 * Route URI pattern
	 * @var string
	 */
	private $pattern = '';
	/**
	 * Controller class of route
	 * @var string
	 */
	private $class = '';
	/**
	 * Action for route
	 * @var string
	 */
	private $method = '';
	/**
	 * Callback for route
	 * @var string
	 */
	private $callback = '';
	/**
	 * Name of the route
	 * @var string
	 */
	private $name = '';
	/**
	 * Group of the route
	 * @var string
	 */
	private $group = '';
	/**
	 * URI segment types
	 * @var array
	 */
	private $segments = [];
	/**
	 * Middlewares that needs to be validated for the route
	 * @var array
	 */
	private $middleware = [];
	/**
	 * Auto include files for specific route
	 * @var array
	 */
	private $include = [];

	function __construct($type)
	{
		$this->type = $type;
	}

	function __get($key)
	{
		return isset($this->{$key})? $this->{$key}: NULL;
	}

	public static function __callStatic($method, $args)
	{
		$route = Router::newRoute($method);
		if(isset($args[0]))
		{
			$route->pattern($args[0]);
		}
		if(isset($args[1]))
		{
			$route->method($args[1]);
		}
		return $route;
	}

	private function pattern($uri)
	{
		$this->pattern = Router::prefixURI().$uri;
		$this->group   = Router::activeGroup();
	}

	private function method($method)
	{
		if(is_callable($method))
		{
			$this->callback = $method;
		}
		else
		{
			$methodArr 	  = explode('@', $method);
			$this->class  = (trim($methodArr[0]))? $methodArr[0]: '';
			$this->method = isset($methodArr[1])? $methodArr[1]: '';
		}
	}

	public function middleware($middleware)
	{
		$this->middleware = is_array($middleware)? $middleware: [$middleware];
		return $this;
	}

	public function include($filePaths)
	{
		$this->include = is_array($filePaths)? $filePaths: [$filePaths];
		return $this;
	}

	public function segment($name, $type)
	{
		$this->segments[$name] = $type;
		return $this;
	}

	public function name($name)
	{
		$this->name = $name;
		return $this;
	}

	public static function current($params=[])
	{
		return URL::get(Router::currentRoute('pattern'), $params);
	}

	public static function url($name, $params=[])
	{
		$name = trim($name);
		if($name)
		{
			if(Router::isRoute($name))
			{
				return URL::get(Router::route($name, 'pattern'), $params);
			}
		}
		return URL::get();
	}

	public function offsetSet($offset, $value)
    {
        $this->{$offset} = $value;
    }

	public function offsetExists($offset)
    {
        return isset($this->{$offset});
    }

	public function offsetGet($offset)
    {
    	return isset($this->{$offset})? $this->{$offset}: NULL;
    }

    public function offsetUnset($offset)
    {
        $this->{$offset} = NULL;
    }

	public function jsonSerialize()
    {
        $vars = get_object_vars($this);
        unset($vars['name']);
        foreach($vars as $key => $value)
        {
        	if(empty($value))
        	{
        		unset($vars[$key]);
        	}	
        }
        if(isset($vars['callback']) && $vars['callback'])
        {
        	$vars['callback'] = Script::closureToString($vars['callback']);
        }
        return $vars;
    }
}