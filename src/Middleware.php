<?php

namespace PHPattern;

class Middleware
{
	protected $responseCode 	= 401;
	protected $responseMessage 	= 'Access denied!';

	public function setCode($code=401)
	{
		$this->responseCode = $code;
	}

	public function setMessage($message='Access denied!')
	{
		$this->responseMessage = $message;
	}

	public function getCode()
	{
		return $this->responseCode;
	}

	public function getMessage()
	{
		return $this->responseMessage;
	}
}