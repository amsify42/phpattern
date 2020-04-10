<?php

namespace PHPattern\File;

use PHPattern\File;
use Amsify42\PHPVarsData\Data\Evaluate;

class Config extends File
{
	private $data = [];

	function __construct($filePath)
	{
		parent::__construct($filePath);
		$this->loadData();
	}

	public function loadData()
	{
		$contentArr = explode("\n", $this->getContent());
		if(sizeof($contentArr)> 0)
		{
			foreach($contentArr as $cak => $cArr)
			{
				$match = [];
				if(preg_match('/^([^=]+)=(.*)/', $cArr, $match))
				{
					$this->data[trim($match[1])] = Evaluate::toValue($match[2]);
				}
			}
		}
	}

	public function isExist($key)
	{
		return isset($this->data[$key])? true: false;
	}

	public function get($key='', $default=NULL)
	{
		return isset($this->data[$key])? $this->data[$key]: $default;
	}

	public function set($key, $value)
	{
		$this->data[$key] = $value;
	}

	public function saveData()
	{
		$content = '';
		if(sizeof($this->data)> 0)
		{
			$i = 0;
			foreach($this->data as $key => $value)
			{
				if($i)
				{
					$content .= "\n";	
				}
				$val = is_string($value)? $value: json_encode($value);
				$content .= $key.'='.$val;
				$i++;
			}
		}
		$this->saveContent($content);
	}
}