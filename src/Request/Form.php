<?php

namespace PHPattern\Request;

use PHPattern\Request;
use PHPattern\Request\Data;
use PHPattern\Request\Validation;
use Amsify42\TypeStruct\TypeStruct;

class Form extends Data
{
	/**
	 * $validation
	 * @var App\Helpers\Validation
	 */
	private $validation;
	/**
	 * Typestruct full class name
	 * @var string
	 */
	protected $typeStruct = NULL;
	/**
	 * Typestruct validate full
	 * @var boolean
	 */
	protected $tsValidateFull = true;
	/**
	 * Checks whether typestruct already set
	 * @var boolean
	 */
	private static $isTypeStructSet = false;	

	protected function rules()
	{
		return [];
	}

	private function initValidation()
	{
		if(!$this->validation)
		{
			$this->validation = new Validation($this);
		}
	}

	protected function value($do='')
	{
		$this->initValidation();
		if($do == 'trim')
		{
			return trim($this->validation->getValue());
		}
		else
		{
			return $this->validation->getValue();	
		}
	}

	protected function valKey($key='')
	{
		$this->initValidation();
		return $this->validation->getValue($key);	
	}

	public function getTypeStruct()
	{
		return $this->typeStruct;
	}

	public function validation()
	{
		return $this->validation;
	}

	public function validated()
	{
		$this->initValidation();
		/**
		 * If TypeStruct is assigned to the request class
		 */
		if($this->typeStruct)
		{
			if(!self::$isTypeStructSet)
			{
				self::$isTypeStructSet = true;
			}
			$typeStruct = new TypeStruct();
			$result 	= $typeStruct->validateFull($this->tsValidateFull)
									 ->setClass($this->typeStruct)
									 ->validate($this->all());
			if($result['is_validated'])
			{
				return true;
			}
			else
			{
				$this->validation->setErrors($result['messages']);
				return false;
			}
		}
		else
		{
			return $this->validation->validated($this->rules());
		}
	}

	public function responseErrors()
	{
		$this->initValidation();
		return $this->validation->responseErrors();
	}
}