<?php

namespace PHPattern;
use Closure;
use ReflectionFunction;

class Script
{
	/**
	 * Including php files for non class based functions
	 * @param  array   $includes
	 * @param  boolean $core
	 */
	public static function include($includes=[])
	{
		foreach($includes as $ik => $include)
		{
			include_once APP_PATH.DS.$include.'.php';
		}
	}

	/**
	 * Converting closure to string
	 * @param  Closure $c [description]
	 * @return string
	 */
	public static function closureToString(Closure $closure)
	{
	    $str 	= 'function (';
	    $r 		= new ReflectionFunction($closure);
	    $params = array();
	    foreach($r->getParameters() as $p)
	    {
	        $s = '';
	        if($p->isArray())
	        {
	            $s .= 'array ';
	        }
	        else if($p->getClass())
	        {
	            $s .= $p->getClass()->name . ' ';
	        }

	        if($p->isPassedByReference())
	        {
	            $s .= '&';
	        }

	        $s .= '$' . $p->name;
	        if($p->isOptional())
	        {
	            $s .= ' = ' . var_export($p->getDefaultValue(), TRUE);
	        }
	        $params []= $s;
	    }
	    $str .= implode(', ', $params);
	    $str .= '){' . PHP_EOL;
	    $lines = file($r->getFileName());
	    for($l = $r->getStartLine(); $l < $r->getEndLine(); $l++)
	    {
	    	if($l+1 == $r->getEndLine())
	    	{
	    		$lastPart = substr($lines[$l], 0, strrpos($lines[$l], '}'));
    			$str .= $lastPart.'}';
	    	}
	    	else
	    	{
	    		$str .= $lines[$l];
	    	}
	    }
	    $str = trim($str);
	    return $str;
	}
}