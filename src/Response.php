<?php

namespace PHPattern;

use SimpleXMLElement;

class Response
{
	private $type 		= '';
	private $content 	= '';
	private $code 		= 200;
	private $viewsDir 	= 'views';

	public function setCode($code = 200)
	{
		$this->code = $code; return $this;
	}

	public function getCode()
	{
		return $this->code;
	}

	public function setType($type = '')
	{
		$this->type = $type; return $this;
	}

	public function getType()
	{
		return $this->type;
	}

	public function setContent($content = '')
	{
		$this->content = $content; return $this;
	}

	public function getContent()
	{
		return $this->content;
	}

	public function view($viewFile, $vars = [])
	{
		return $this->createView($viewFile, $vars);
	}

	public function render($viewFile, $vars = [])
	{
		return $this->createView($viewFile, $vars, true);	
	}

	private function createView($viewFile, $vars=[], $render = false)
	{
		ob_start();
		extract($vars);
		include APP_PATH.DS.$this->viewsDir.DS.$this->convertToView($viewFile).'.html';
		$this->content = ob_get_contents();
		ob_end_clean();
		if($render)
		{
			return $this->content;
		}
		else
		{
			return $this->setType('text/html; charset=utf-8');
		}
	}

	public function json($message = 'Something went wrong', $status = false, $data = [], $meta = [], $errors = [])
	{
		$response = ['status' => $status, 'message' => $message];
		if(sizeof($data)> 0)
		{
			$response['data'] 	= $data;
		}
		if(sizeof($errors)> 0)
		{
			$response['errors'] = $errors;
		}
		if(sizeof($meta)> 0)
		{
			$response['meta'] 	= $meta;
		}
		return $this->setType('application/json')->setContent(json_encode($response));
	}

	public function xml($data=[])
	{
		$xml = new SimpleXMLElement('<?xml version="1.0"?><data></data>');
		array_to_xml($data, $xml);
		return $this->setType('text/xml; charset=utf-8')->setContent($xml->asXML());
	}

	public function output($message)
	{
		if(is_array($message))
		{
			$message = implode("\n\n", $message);
		}
		$message = "\n".$message."\n"; 
		return $this->setContent($message);
	}

	private function convertToView($viewStr)
	{
		return str_replace('.', DS, $viewStr);
	}

	public function validationErrors($errors=[], $message='Validation errors occured')
	{
		return $this->setCode(400)->json($message, false, [], [], $errors);
	}
}