<?php

namespace PHPattern\Request;

use PHPattern\Request;

class Data
{
	/**
	 * CLI params
	 * @var array
	 */
	private $cli = [];
	/**
	 * CLI params loaded
	 * @var boolean
	 */
	private $cliLoaded = false;
	/**
	 * request header
	 * @var array
	 */
	private $headers = [];
	/**
	 * Headers loaded
	 * @var boolean
	 */
	private $headersLoaded = false;
	/**
	 * request data
	 * @var array
	 */
	private $data = [];
	/**
	 * Data loaded
	 * @var boolean
	 */
	private $dataLoaded = false;
	/**
	 * uri params
	 * @var array
	 */
	private $params = [];
	/**
	 * Request content type
	 * @var string
	 */
	protected $contentType = 'json';
	/**
	 * Decides whether body data needs to loaded as array 
	 * @var boolean
	 */
	protected $loadAsArray = true;

	public function __get($key)
	{
		if(isset($this->params[$key]))
		{
			return $this->params[$key];
		}
	}

	public function setContentType($type='json')
	{
		$this->contentType = $type;
	}

	public function isLoadAsArray($set=true)
	{
		$this->asArray = $set;
	}

	public function contentType()
	{
		return $this->contentType;
	}

	public function loadAsArray()
	{
		return $this->loadAsArray;
	}

	public function _loadAll()
	{
		$this->_loadHeaders();
		$this->_loadBody();
	}

	public function _loadHeaders()
	{
		if(!$this->headersLoaded)
		{
			$this->headersLoaded = true;
			foreach($_SERVER as $key => $value)
		    {
		        if(substr($key, 0, 5) <> 'HTTP_')
		        {
		        	continue;
		        }
		        $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
		        $this->headers[$header] = $value;
		    }
		}
	}

	public function _loadBody()
	{
		if(!$this->dataLoaded)
		{
			$this->dataLoaded = true;
			if($this->contentType == 'json')
			{
				$this->data = json_decode(file_get_contents("php://input"), $this->loadAsArray);
			}
			else if($this->contentType == 'xml')
			{
				if($this->loadAsArray)
				{
			        $encode 	= json_encode(simplexml_load_string(file_get_contents("php://input"), 'SimpleXMLElement', LIBXML_NOCDATA));
			        $this->data = json_decode($encode, $this->loadAsArray);
				}
				else
				{
					$this->data = simplexml_load_string(file_get_contents("php://input"));
				}
			}
		}
	}

	public function _loadCLIParams()
	{
		if(!$this->cliLoaded)
		{
			$this->cliLoaded = true;
			if(sizeof($_SERVER['argv'])> 0)
			{
				$getOptKey = NULL;
				foreach($_SERVER['argv'] as $aKey => $argv)
				{
					if($aKey == 1)
					{
						$this->cli['action'] = $argv;
					}
					else if($aKey == 2)
					{
						$this->cli['type'] = $argv;
					}
					else if($aKey == 3)
					{
						$this->cli['name'] = $argv;
					}
					else if($getOptKey)
					{
						$this->cli[$getOptKey] = $argv;
						$getOptKey = NULL;
					}
					else if(preg_match('/^-([^=]+)=(.*)/', $argv, $match))
					{
					    $this->cli[trim($match[1])] = $match[2];
					}
					else if(preg_match('/^-([^-]+)(.*)/', $argv, $match))
				    {
				        $getOptKey = trim($match[1]);
				    }
					else
					{
						$this->cli[$aKey] = $argv;	
					}
				}
			}
		}
	}

	public function headers()
	{
		$this->_loadHeaders();
		return $this->headers;
	}

	public function header($key)
	{
		$this->_loadHeaders();
		return isset($this->headers[$key])? $this->headers[$key]: null;
	}

	public function append($key, $value)
	{
		$this->data[$key] = $value;
	}

	public function remove($key)
	{
		unset($this->data[$key]);
	}

	public function addParams($params=[])
	{
		$this->params = $params;
	}

	public function cli($key)
	{
		$this->_loadCLIParams();
		return isset($this->cli[$key])? $this->cli[$key]: null;	
	}

	public function params()
	{
		return $this->params;
	}

	public function param($key)
	{
		return isset($this->params[$key])? $this->params[$key]: null;
	}

	public function all()
	{
		$this->_loadBody();
		if(!empty($this->data))
		{
			return $this->data;
		}
		else if(Request::isPost())
		{
			return $_POST;
		}
		else
		{
			return $_GET;
		}
	}

	public function get($key)
	{
		$this->_loadBody();
		if(!empty($this->data))
		{
			return isset($this->data[$key])? $this->data[$key]: null;
		}
		else if(Request::isPost())
		{
			return $this->post($key);
		}
		else
		{
			return $this->queryParam($key);
		}
	}

	public function getPath($path='')
	{
		$this->_loadBody();
		$path = trim($path);
		if($path)
		{
			$array = explode('.', $path);
			if(sizeof($array)> 0)
			{
				return $this->getValue($array);
			}
		}
		return NULL;
	}

	private function getValue($array)
	{
		for($i=$this->data; $key=array_shift($array),$key!==NULL; $i=$i[$key])
		{
			if(!isset($i[$key]))
			{
				return NULL;
			}
	    }
	    return $i;
	}

	public function setPath($path, $value)
	{
		$path = trim($path);
		if($path)
		{
			$pathArray = explode('.', $path);
			if(sizeof($pathArray)> 0)
			{
				return $this->setValue($pathArray, $value);
			}
		}
	}

	private function setValue($keysPath, $value)
	{
		for($i=&$this->data; $key=array_shift($keysPath),$key!==NULL; $i=&$i[$key])
		{
			if(!isset($i[$key]))
			{
				$i[$key] = array();
			}
	    }
	    $i = $value;
	}

	public function post($key)
	{
		return isset($_POST[$key])? $_POST[$key]: NULL;
	}
	
	public function queryParam($key)
	{
		return isset($_GET[$key])? $_GET[$key]: NULL;
	}

	public function queryParams()
	{
		return $_GET;
	}
}