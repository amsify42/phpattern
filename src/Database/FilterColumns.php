<?php

namespace PHPattern\Database;

use PHPattern\Database\Model;

class FilterColumns
{
	private $model;
	private $skip 		= ['id', 'sage_id', 'created_at', 'updated_at', 'image_url_small', 'image_url_medium', 'image_url_large'];
	private $columns 	= [];

	private $isValid 	= true;
	private $errors 	= [];

	private $row 		= [];

	function __construct(Model $model, $skip = [])
	{
		$this->model = $model;
		if(is_array($skip) && sizeof($skip)> 0)
		{
			$this->skip = $skip;
		}
	}

	public function addSkip($column)
	{
		if(!empty($column))
		{
			if(is_array($column))
			{
				foreach($column as $col)
				{
					$this->skip[] = $col;
				}
			}
			else
			{
				$this->skip[] = $column;
			}
		}
	}

	public function setColumns()
	{
		if(empty($this->columns))
		{
			$columns = $this->model->getColumns();
			if(sizeof($columns)> 0)
			{
				foreach($columns as $column)
				{
					if(!in_array($column['Field'], $this->skip))
					{
						$this->columns[] = $column['Field'];
					}
				}
			}
		}
	}

	public function add($values = [])
	{
		$this->setColumns();
		if(sizeof($values)> 0)
		{
			foreach($values as $column => $value)
			{
				if($this->isAllowed($column) && !isEmpty($value))
				{
					$this->row[$column] = $value;
				}
			}
		}
		return $this;
	}

	private function isAllowed($column)
	{
		$column = trim($column);
		if($column)
		{
			if(in_array($column, $this->columns))
			{
				return true;
			}
			else
			{
				$this->isValid 	= false;
				$this->errors[] = $column.' - is not a valid column';
				return false;
			}
		}
		return false;
	}

	public function isValid()
	{
		return $this->isValid;
	}

	public function errors()
	{
		return $this->errors;
	}

	public function getRow()
	{
		return $this->row;
	}
}