<?php

namespace PHPattern\Database;

use PHPattern\DB;
use ReflectionClass;

class Model
{
	protected $env 			= '';
	protected $table 		= '';
	protected $primaryKey 	= 'id';
	protected $timestamps 	= false;
	protected $pageLimit 	= 10;

	private $timestampsCols = ['created_at', 'updated_at'];

	function __construct()
	{
		if(!$this->table)
		{
			$reflect 	 = new ReflectionClass(get_called_class());
			$this->table = class_to_underscore($reflect->getShortName());
		}
	}

	public function setTable($table)
	{
		$this->table = $table;
		return $this;
	}

	public function setPrimaryKey($key = 'id')
	{
		$this->primaryKey = $key;
	}

	public function setTimestamps($timestamps)
	{
		$this->timestamps = $timestamps;
		return $this;
	}

	public function setPageLimit($pageLimit=10)
	{
		$this->pageLimit = $pageLimit;
		return $this;
	}

	public function getTable()
	{
		return $this->table;
	}

	public function setEnvDB($env = 'dev')
	{
		$this->env = $env;
	}

	public function getColumns($like = '')
	{
		$query = "SHOW COLUMNS FROM `{$this->table}`";
		if($like)
		{
			$query .= " LIKE '%{$like}%'";
		}
		return $this->execute($query);
	}

	public function getAll($columns = ['*'], $moreQuery = '')
	{
		$query = "SELECT ".$this->selectColumns($columns)." FROM `{$this->table}`";
		if($moreQuery)
		{
			$query .= " ".$moreQuery;
		}
		return $this->execute($query);	
	}

	public function select($columns = ['*'], $where = [], $moreQuery = '')
	{
		$query 	= "SELECT ".$this->selectColumns($columns)." FROM `{$this->table}`";
		$noCond = true;
		if(sizeof($where)> 0)
		{
			$noCond = false;
			$query .= " WHERE ".$this->whereColumns($where);	
		}
		if($moreQuery)
		{
			$query .= " ".$moreQuery;
		}
		if($noCond && (!$moreQuery || strpos($moreQuery, ' LIMIT ') === false))
		{
			$query .= " LIMIT {$this->pageLimit}";	
		}
		return $this->execute($query);
	}

	public function paginate($columns = ['*'], $where = [], $moreQuery = '', $page=1)
	{
		$query 	= "SELECT ".$this->selectColumns($columns)." FROM `{$this->table}`";
		if(sizeof($where)> 0)
		{
			$query .= " WHERE ".$this->whereColumns($where);	
		}
		if($moreQuery) 
		{
			$query .= " ".$moreQuery;
		}
		$query .= " LIMIT {$this->getOffset($page)},{$this->pageLimit}";
		return $this->execute($query);
	}

	private function getOffset($page=1)
	{
		return ($page-1)*$this->pageLimit;
	}

	public function insert($data = [])
	{
		$data 	= $this->checkTimestamps($data);
		$query 	= $this->insertQuery($data);
		return $this->execute($query, true, 'Yes', true);
	}

	public function first($columns = ['*'], $where, $moreQuery = '')
	{
		$query = "SELECT ".$this->selectColumns($columns)." FROM `{$this->table}`";
		if(is_array($where))
		{
			$query .= " WHERE ".$this->whereColumns($where);
		}
		else
		{
			$query .= " WHERE `{$this->table}`.`{$this->primaryKey}`={$where}";
		}
		if($moreQuery)
		{
			$query .= " ".$moreQuery;
		}
		$query .= " LIMIT 1";
		$result = $this->execute($query);
		return isset($result[0])? $result[0]: null;
	}

	public function update($data = [], $where)
	{
		$data 	= $this->checkTimestamps($data, 'update');
		$query 	= $this->updateQuery($data, $where);
		return $this->execute($query, true);
	}

	public function checkTimestamps($data, $type = 'save')
	{
		if($this->timestamps)
		{
			if(sizeof($data)>0)
			{
				if($type == 'save' && !isset($data['created_at']))
				{
					$data['created_at'] = DB::NOW;
				}
				if(!isset($data['updated_at']))
				{
					$data['updated_at'] = DB::NOW;
				}
			}
		}
		return $data;
	}

	public function delete($where, $limit = 0)
	{
		$query = "DELETE FROM `{$this->table}` WHERE ";
		if(is_array($where))
		{
			$query .= $this->whereColumns($where);
		}
		else
		{
			$query .= "`{$this->table}`.`{$this->primaryKey}`={$where}";
		}
		if($limit)
		{
			$query .= " LIMIT ".$limit;	
		}
		return $this->execute($query, true);
	}

	public function join($model, $pcols = [], $fcols = [], $on = [], $type = '', $pwhere = [], $fwhere = [], $moreQuery = '')
	{
		$primary 	= isset($on[0])? $on[0]: $this->primaryKey;
		$foreign 	= isset($on[1])? $on[1]: strtolower($this->table).'_id'; 
		$query 		= "SELECT ".$this->selectColumns($pcols);
		/**
		 * Foreign key table selections
		 */
		if(sizeof($fcols)> 0)
		{
			if(sizeof($pcols)> 0)
			{
				$query .= ", ";
			}
			$query .= $model->selectColumns($fcols);
		}

		$query .= " FROM `{$this->table}` {$type} JOIN `{$model->table}` ON `{$this->table}`.`{$primary}`=`{$model->table}`.`{$foreign}`";

		if(sizeof($pwhere) > 0)
		{
			$query .= " WHERE ".$this->whereColumns([$pwhere]);
		}

		/**
		 * Foreign key table conditions
		 */
		if(sizeof($fwhere) > 0)
		{
			if(sizeof($pwhere) > 0)
			{
				$query .= " AND ";
			}
			else
			{
				$query .= " WHERE ";
			}
			$query .= $model->whereColumns([$fwhere]);
		}
		if($moreQuery)
		{
			$query .= " ".$moreQuery;
		}
		return $this->execute($query);				
	}

	private function insertQuery($data = [])
	{
		$query 	= "INSERT INTO `{$this->table}` (";
		$values = " VALUES (";
		foreach($data as $column => $value)
		{
			$query .= "`{$column}`,";
			if(in_array($column, $this->timestampsCols))
			{
				$values .= ($value == DB::NOW)? $value.",": "'".$value."',";
			}
			else if(is_string($value))
			{
				$values .= "'".addslashes($value)."',";
			}
			else
			{
				$values .= $value.",";
			}
		}
		$query  = rtrim($query, ",");
		$query .= ")";
		$values = rtrim($values, ",");
		$values .= ")";
		return $query.$values;
	}

	private function updateQuery($data = [], $where = '')
	{
		$query 	= "UPDATE `{$this->table}` SET ".$this->columnValues($data);
		if(is_array($where))
		{
			$query .= " WHERE ".$this->whereColumns($where);
		}
		else
		{
			$query .= " WHERE `{$this->table}`.`{$this->primaryKey}`={$where}";
		}
		return $query;
	}

	public function selectColumns($columns)
	{
		$query = '';
		foreach($columns as $column)
		{
			if($column == '*')
			{
				$query .= '*'; break; 
			}

			if(strpos($column, '(') !== false)
			{
				$query .= "{$column}, ";
			}
			else if(strpos($column, ' as ') !== false)
			{
				$query .= "`{$this->table}`.{$column}, ";
			}
			else
			{
				$query .= "`{$this->table}`.`{$column}`, ";
			}
		}
		return rtrim($query, ", ");
	}

	public function columnValues($data = [])
	{
		$query = "";
		if(sizeof($data)> 0)
		{
			foreach($data as $column => $value)
			{
				$query .= "`{$this->table}`.`{$column}`=";
				if(in_array($column, $this->timestampsCols))
				{
					$query .= ($value == DB::NOW)? $value.", ": "'".$value."', ";
				}
				else if(is_string($value))
				{
					$query .= "'".addslashes($value)."', ";
				}
				else
				{
					$query .= $value.", ";
				}
			}
			$query = rtrim($query, ", ");
		}
		return $query;
	}

	public function whereColumns($conditions = [])
	{
		$columns 	= isset($conditions[0])? $conditions[0]: [];
		$type 		= isset($conditions[1])? $conditions[1]: 'AND';
		$query 		= "";
		if(sizeof($columns)> 0)
		{
			foreach($columns as $column => $value)
			{
				if($value instanceof \Closure)
				{
					$query .= $value($this);
				}
				else if(is_array($value))
				{
					if(sizeof($value)> 0)
					{
						$inElements = '';
						foreach($value as $val)
						{
							if(is_string($val))
							{
								$inElements .= "'".addslashes($val)."',";
							}
							else
							{
								$inElements .= $val.",";
							}
						}
						$inElements  = rtrim($inElements, ",");
						$query 		.= "`{$this->table}`.`{$column}` IN ({$inElements}) ".$type." ";
					}
				} else {
					$colArray = explode(' ', $column);
					if(sizeof($colArray)> 1)
					{
						$query .= "`{$this->table}`.`{$colArray[0]}` {$colArray[1]}";
						if($colArray[1] == 'IN')
						{
							$query .= "({$value})"." ".$type." ";
						}
						else
						{
							$query .= " '%{$value}%'"." ".$type." ";
						}
					}
					else
					{
						if($value === NULL)
						{
							$query .= "`{$this->table}`.`{$column}` IS NULL";
						}
						else
						{
							$query .= "`{$this->table}`.`{$column}`=";
							if(is_string($value))
							{
								$query .= "'".addslashes($value)."' ".$type." ";
							}
							else
							{
								$query .= $value." ".$type." ";
							}
						}
					}
				}
			}
			$query = rtrim($query, " ".$type." ");
		}
		return $query;
	}

	public function execute($query, $isUpdate=false, $isResult='Yes', $lastId=false)
	{
		$type = '';
		if($isUpdate)
		{
			$type = 'update';
		}
		else if($lastId)
		{
			$type = 'insert';
		}
		return DB::query($query, $type, $this->env);
	}
}