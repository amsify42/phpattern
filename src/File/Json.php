<?php

namespace PHPattern\File;

use PHPattern\File;
use Amsify42\PHPVarsData\Data\ArraySimple;

class Json extends File
{
	function __construct($filePath)
	{
		parent::__construct($filePath);
	}

	public function getData($default=NULL, $asArray=true)
	{
		return $this->get($default, $asArray);
	}

	public function jsonArray($default=NULL)
	{
		return new ArraySimple($this->get($default, true));
	}

	private function get($default=NULL, $asArray=true)
	{
		if($this->isFile())
		{
			$jsonData = json_decode($this->getContent(), $asArray);
			if($jsonData && is_array($jsonData))
			{
				return $jsonData;
			}
		}
		return $default;
	}

	public function saveData($data, $isRaw=false)
	{
		$content = ($isRaw)? $data: json_encode($data, JSON_UNESCAPED_SLASHES);
		$this->saveContent($content);
	}

}