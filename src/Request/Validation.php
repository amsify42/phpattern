<?php

namespace PHPattern\Request;

use PHPattern\Request;
use PHPattern\Request\Form;

class Validation
{
	private $value 	 = '';
	private $tmpKeys = [];
	private $errors  = [];
	private $request = NULL;

	function __construct(Form $request=NULL)
	{
		$this->request = $request;
	}

	public function setErrors($errors)
	{
		$this->errors = $errors;
	}

	public function setError($key, $error)
	{
		$this->errors[$key] = $error;
	}

	public function validated($rules)
	{
		$this->errors = [];
		foreach($rules as $rKey => $ruleNames)
		{
			$ruleNamesArray = explode('|', $ruleNames);
			if(sizeof($ruleNamesArray)> 0)
			{
				foreach($ruleNamesArray as $rule)
				{
					$ruleArray 	= explode(':', trim($rule));
					$ruleName 	= trim($ruleArray[0]);
					$childsRules= isset($ruleArray[1])? trim($ruleArray[1]): '';
					$this->setValue($rKey);
					if($ruleName == 'requiredif')
					{
						if(!isEmpty($this->value))
						{
							continue;
						}
						else
						{
							break;
						}
					}
					$this->checkRule($this->errors, $rKey, $ruleName, $childsRules);
					if(isset($this->errors[$rKey]))
					{
						break; 
					}
				}
			}
		}
		return (sizeof($this->errors)> 0)? false: true;
	}

	private function setValue($key)
	{
		$this->value = ($this->request)? $this->request->get($key): Request::get($key);
	}

	public function assignValue($value)
	{
		$this->value = $value;
	}

	public function getValue($key='')
	{
		if($key)
		{
			return isset($this->value[$key])? $this->value[$key]: '';
		}
		else
		{
			return $this->value;
		}
	}

	public function responseErrors()
	{
		return response()->validationErrors($this->errors);
	}

	private function checkRule(&$errors, $field, $ruleName, $childsRules='')
	{
		switch($ruleName)
		{
			case 'required':
				if(isEmpty($this->value))
				{
					$errors[$field] = 'Field is required';
				}
				break;
			case 'string':
				if(!is_string($this->value))
				{
					$errors[$field] = 'Field must be string';
				}
				break;
			case 'int':
				if(!is_int($this->value))
				{
					$errors[$field] = 'Field must be int';
				}
				break;
			case 'float':
				if(!is_float($this->value))
				{
					$errors[$field] = 'Field must be float';
				}
				break;
			case 'bool':
				if(!is_bool($this->value))
				{
					$errors[$field] = 'Field must be boolean';
				}
				break;	
			case 'array':
				if(!is_array($this->value))
				{
					$errors[$field] = 'Field must be an array';
				}
				break;		
			case 'keys':
				if($childsRules && !$this->areKeysPresent($this->value, $childsRules))
				{
					$errors[$field] = $this->keysToArray();
				}
				break;
			case 'childkeys':
				if($childsRules && !$this->areChildKeysPresent($this->value, $childsRules))
				{
					$errors[$field] = $this->keysToArray(true);
				}
				break;
			default:
				/**
				 * Check if the form request have custom validation method
				 */
				if($this->request && method_exists($this->request, $ruleName) && is_callable([$this->request, $ruleName]))
				{
					$result = call_user_func_array([$this->request, $ruleName], []);
					if($result !== true)
					{
						$errors[$field] = is_string($result)? $result: 'Field must be valid';
					}
				}
				/**
				 * else do nothing
				 */
				break;
		}
	}

	private function areChildKeysPresent($items, $keys)
	{
		$isPresent = false;
		if(is_array($items) && sizeof($items)> 0)
		{
			$isPresent = true;
			foreach($items as $item)
			{
				if(!$this->areKeysPresent($item, $keys))
				{
					return false; break;
				}
			}
		}
		return $isPresent;
	}

	private function areKeysPresent($value, $keys)
	{
		$isPresent = false;
		$keysArray = explode(',', trim($keys));
		if(is_array($value) && sizeof($keysArray)> 0)
		{
			$isPresent = true;
			foreach($keysArray as $key)
			{
				$tKey = trim($key);
				if(!isset($value[$tKey]) || isEmpty($value[$tKey]))
				{
					$this->tmpKeys[] = $tKey; 
					$isPresent = false;
				}
			}
		}
		return $isPresent;
	}

	private function keysToArray($isChild = false)
	{
		$messages = [];
		foreach($this->tmpKeys as $tmpKey)
		{
			$text = $tmpKey.' is mandatory';
			if($isChild)
			{
				$text .= ' for child element';
			}
			$messages[] = $text;
		}
		$this->tmpKeys = [];
		return $messages;
	}
}