<?php

namespace PHPattern;

use PHPattern\Request;

class Console
{
	private $actions 	= ['create'];
	private $types 	 	= ['class', 'trait', 'action', 'model', 'request', 'typeStruct', 'middleware'];

	private $namespace 	= 'App';
	private $class 		= '';

	public function output()
	{
		$type 	= Request::cli('type');
		$action = Request::cli('action');
		$name 	= Request::cli('name');
		if($type && $action && $name)
		{
			if(in_array($type, $this->types) && in_array($action, $this->actions))
			{
				$method  = $action.ucfirst($type);
				$message = $this->{$method}($name);
				return response()->output($message);
			}
		}
		return response()->output('No action executed!');
	}

	private function createClass($name)
	{
		$file = $this->getFilePath($name, 'class');
		if(!file_exists($file))
		{
			$fp = fopen($file,'w');
			$content = "<?php\n\nnamespace {$this->namespace};\n\nclass {$this->class}\n{\n\t\n}";
			fwrite($fp, $content);
			fclose($fp);
			$this->openFile($file);
			return 'Class Created Successfully';
		}
		else
		{
			return 'Class already exist';
		}
	}

	private function createTrait($name)
	{
		$file = $this->getFilePath($name, 'traits');
		if(!file_exists($file))
		{
			$fp = fopen($file,'w');
			$content = "<?php\n\nnamespace {$this->namespace};\n\ntrait {$this->class}\n{\n\t\n}";
			fwrite($fp, $content);
			fclose($fp);
			$this->openFile($file);
			return 'Trait Created Successfully';
		}
		else
		{
			return 'Trait already exist';
		}
	}

	private function createAction($name)
	{
		$file = $this->getFilePath($name, 'actions');
		if(!file_exists($file))
		{
			$fp = fopen($file,'w');
			$content = "<?php\n\nnamespace {$this->namespace};\n\nuse PHPattern\Action;\n\nclass {$this->class} extends Action\n{\n\tpublic function index()\n\t{\n\t\t\n\t}\n}";
			fwrite($fp, $content);
			fclose($fp);
			$this->openFile($file);
			return 'Action Created Successfully';
		}
		else
		{
			return 'Action already exist';
		}
	}

	private function createModel($name)
	{
		$file = $this->getFilePath($name, 'models');
		if(!file_exists($file))
		{
			$fp = fopen($file,'w');
			$content = "<?php\n\nnamespace {$this->namespace};\n\nuse PHPattern\Database\Model;\n\nclass {$this->class} extends Model\n{\n";
			/**
			 * table
			 */
			$table = Request::cli('table');
			if($table)
			{
				$content .= "\tprotected \$table = '{$table}';\n";
			}
			/**
			 * primaryKey
			 */
			$primaryKey = Request::cli('primaryKey');
			if($primaryKey)
			{
				$content .= "\tprotected \$primaryKey = '{$primaryKey}';\n";
			}		
			/**
			 * timestamps
			 */
			$timestamps = Request::cli('timestamps');
			if($timestamps)
			{
				$content .= "\tprotected \$timestamps = {$timestamps};\n";
			}
			/**
			 * pageLimit
			 */
			$pageLimit = Request::cli('pageLimit');
			if($pageLimit)
			{
				$content .= "\tprotected \$pageLimit = {$pageLimit};\n";
			}		
			$content .= "\t\n}";

			fwrite($fp, $content);
			fclose($fp);
			$this->openFile($file);
			return 'Model Created Successfully';
		}
		else
		{
			return 'Model already exist';
		}
	}

	private function createRequest($name)
	{
		$file = $this->getFilePath($name, 'request');
		if(!file_exists($file))
		{
			$fp = fopen($file,'w');
			$content = "<?php\n\nnamespace {$this->namespace};\n\nuse PHPattern\Request\Form;\n\nclass {$this->class} extends Form\n{\n\tprotected function rules()\n\t{\n\t\treturn [\n\t\t\t\n\t\t];\n\t}\n\n}";
			fwrite($fp, $content);
			fclose($fp);
			$this->openFile($file);
			return 'Request Created Successfully';
		}
		else
		{
			return 'Request already exist';
		}
	}

	private function createTypeStruct($name)
	{
		$file = $this->getFilePath($name, 'typeStruct');
		if(!file_exists($file))
		{
			$fp = fopen($file,'w');
			$content = "<?php\n\nnamespace {$this->namespace};\n\nexport typestruct {$this->class} {\n\t\n}";
			fwrite($fp, $content);
			fclose($fp);
			$this->openFile($file);
			return 'TypeStruct Created Successfully';
		}
		else
		{
			return 'TypeStruct already exist';
		}
	}

	private function createMiddleware($name)
	{
		$file = $this->getFilePath($name, 'middlewares');
		if(!file_exists($file))
		{
			$fp = fopen($file,'w');
			$content = "<?php\n\nnamespace {$this->namespace};\n\nuse PHPattern\Middleware;\nuse PHPattern\Request\Data;\n\nclass {$this->class} extends Middleware\n{\n\tpublic function process(Data \$requestData)\n\t{\n\t\treturn false;\n\t}\n\n}";
			fwrite($fp, $content);
			fclose($fp);
			$this->openFile($file);
			return 'Middleware Created Successfully';
		}
		else
		{
			return 'Middleware already exist';
		}
	}

	private function getFilePath($name, $type)
	{
		$pathArr = explode('.', $name);
		$inDir 	 = ($type == 'class')? DS: DS.ucfirst($type).DS;
		$info 	 = ['dirs' => [], 'file' => ''];
		$size 	 = sizeof($pathArr);
		$space 	 = '';
		if($size > 1)
		{
			$level = '';
			foreach($pathArr as $dk => $dir)
			{
				if(($dk+1) == $size)
				{
					break;
				}
				$space 	= ($space)? $space.'\\'.$dir: $dir;
				$level 	= ($level)? $level.DS.$dir: $dir;
				$info['dirs'][] = APP_PATH.$inDir.$level;
			}
			$this->class 	= end($pathArr);
			$info['file'] 	= end($info['dirs']).DS.$this->class.'.php';
		}
		else
		{
			$this->class  = $pathArr[0];
			$info['file'] = APP_PATH.$inDir.$this->class.'.php';
		}
		$this->namespace = 'App';
		if($type != 'class')
		{
			$this->namespace .= '\\'.ucfirst($type);
		}
		if($space)
		{
			$this->namespace .= '\\'.$space;
		}
		/**
		 * Create root directory if does not exist
		 */
		$rootDir = APP_PATH.$inDir;
		if(!is_dir($rootDir))
		{
			mkdir($rootDir, 0777, true);
		}
		/**
		 * Create directories if needed
		 */
		if(sizeof($info['dirs'])> 0)
		{
			foreach($info['dirs'] as $dk => $dirPath)
			{
				if(!is_dir($dirPath))
				{
					mkdir($dirPath, 0777, true);
				}
			}
		}
		return $info['file'];
	}

	private function openFile($filePath)
	{
		if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
		{
			sleep(1);
			/**
			 * Open file with default program
			 */
			exec("START ".$filePath);
		}
	}
}