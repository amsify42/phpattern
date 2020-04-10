<?php

namespace PHPattern;

class File
{
	protected $filePath;

	function __construct($filePath)
	{
		$this->filePath = $filePath;
	}

	public function isFile()
	{
		return is_file($this->filePath);
	}

	public function createdTime()
	{
		return filectime($this->filePath);
	}

	public function updatedTime()
	{
		return filemtime($this->filePath);
	}

	public function createdAt($format='Y-m-d H:i:s')
	{
		return date($format, $this->createdTime());
	}

	public function createdDaysAgo($default=0)
	{
		return ($this->isFile())? daysAgo($this->createdAt()): $default;
	}

	public function getContent($default='')
	{
		if($this->isFile())
		{
			return file_get_contents($this->filePath);
		}
		else
		{
			return $default;
		}
	}

	public function saveContent($content)
	{
		$fp = fopen($this->filePath, 'w');
		fwrite($fp, $content);
        fclose($fp);
	}

	public function delete()
	{
		if($this->isFile())
		{
			unlink($this->filePath);	
		}
		return $this;
	}
}