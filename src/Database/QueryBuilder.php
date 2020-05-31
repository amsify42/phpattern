<?php

namespace PHPattern\Database;

use PHPattern\DB;

class QueryBuilder
{
    private static $model  = NULL;
    private static $type   = '';
    private static $data   = [];
    private static $cols   = ['*'];
    private static $conds  = [];
    private static $order  = [];
    private static $havings= [];
    private static $limit  = NULL;
    private static $query  = NULL;

    const DELIMITER  = ',';

    public static function getModel()
    {
        return self::$model;
    }

    public static function setModel($model)
    {
        self::$model = $model;
    }

    private static function getTable()
    {
        return (self::$model)? self::$model->getTable(): NULL;
    }

    public static function select($cols=['*'])
    {
        self::$type = '';
        return self::$model;
    }

    public static function find($conds=[])
    {
        self::$conds = $conds;
        return self::$model;
    }

    public static function where($col, $value)
    {
        self::$conds[$col] = $value;
        return self::$model;
    }

    public static function whereIn($col, $value)
    {
        self::$conds[$col] = is_array($value)? $value: [$value];
        return self::$model;
    }

    public static function orderBy($col, $order='ASC')
    {
        self::$order[$col] = $order;
        return self::$model;
    }

    public static function insert($data=[])
    {
        self::$type = 'insert';
        self::$data = $data;
        self::buildQuery();
        return self::execute();
    }

    public static function update($data=[], $conds=[])
    {
        self::$type = 'update';
        self::$data = $data;
        self::$conds= $conds;
        self::buildQuery();
        return self::execute();
    }

    public static function all()
    {
        self::buildQuery();
        return self::execute();
    }

    public static function first()
    {
        self::$limit = 1;
        self::buildQuery();
        $result = self::execute();
        return isset($result[0])? $result[0]: NULL;
    }

    private function buildQuery()
    {
        if(self::$type == 'insert')
        {
            self::prepareInsert();
        }
        else if(self::$type == 'update')
        {
            self::prepareUpdate();
        }
        else
        {
            self::prepareSelect();
        }
    }

    private static function prepareInsert()
    {
        self::checkTimestamps();
        $query  = "INSERT INTO `".self::getTable()."` (";
        $values = " VALUES (";
        foreach(self::$data as $column => $value)
        {
            $query .= "`{$column}`,";
            if(in_array($column, self::$model->timestampsCols()))
            {
                $values .= ($value == DB::NOW)? $value: "'".$value."'";
            }
            else if(is_string($value))
            {
                $values .= "'".addslashes($value)."'";
            }
            else
            {
                $values .= ($value === NULL)? "NULL": $value;
            }
            $values .= self::DELIMITER;
        }
        $query  = rtrim($query, self::DELIMITER);
        $query .= ")";
        $values = rtrim($values, self::DELIMITER);
        $values .= ")";
        self::$query = $query.$values;
    }

    private static function prepareUpdate()
    {
        self::checkTimestamps();
        self::$query = "UPDATE `".self::getTable()."` SET ".self::setValues();
        self::setConditions();
    }

    private static function setOrder()
    {
        if(!empty(self::$order))
        {
            self::$query .= "ORDER BY";
            foreach(self::$order as $col => $order)
            {
                self::$query .= " ".$col." ".$order.self::DELIMITER;
            }
            self::$query = rtrim(self::$query, self::DELIMITER);
        }
    }

    private static function setHaving()
    {
        if(!empty(self::$havings))
        {
            
        }
    }

    private static function setLimit()
    {
        if(self::$limit)
        {
            self::$query .= " LIMIT ".self::$limit;
        }
    }

    private static function setValues()
    {
        $query = "";
        if(sizeof(self::$data)> 0)
        {
            foreach(self::$data as $column => $value)
            {
                $query .= "`".self::getTable()."`.`{$column}`=";
                if(in_array($column, self::$model->timestampsCols()))
                {
                    $query .= ($value == DB::NOW)? $value: "'".$value."'";
                }
                else if(is_string($value))
                {
                    $query .= "'".addslashes($value)."'";
                }
                else
                {
                    $query .= ($value === NULL)? "NULL": $value;
                }
                $query .= self::DELIMITER;
            }
            $query = rtrim($query, self::DELIMITER);
        }
        return $query;
    }

    private static function setConditions()
    {
        if(is_array(self::$conds))
        {
            if(sizeof(self::$conds)> 0)
            {
                self::$query .= " WHERE ";
                foreach(self::$conds as $column => $value)
                {
                    if(is_array($value))
                    {
                        if(sizeof($value)> 0)
                        {
                            self::whereInRaw($column, $value);
                        }
                    }
                    else
                    {
                        self::whereRaw($column, $value);
                    }
                }
                self::$query = rtrim(self::$query, " AND ");
            }
        }
        else if(self::$conds)
        {
            self::$query .= " WHERE `".self::getTable()."`.`".self::$model->primaryKey()."`=".self::$conds;
        }
    }

    public static function whereRaw($col, $value)
    {
        if($value === NULL)
        {
            self::$query .= "`".self::getTable()."`.`{$col}` IS NULL";
        }
        else
        {
            self::$query .= "`".self::getTable()."`.`{$col}`=";
            if(is_string($value))
            {
                self::$query .= "'".addslashes($value)."' AND ";
            }
            else
            {
                self::$query .= $value." AND ";
            }
        }
    }

    public static function whereInRaw($col, $items)
    {
        $inElements = '';
        foreach($items as $item)
        {
            if(is_string($item))
            {
                $inElements .= "'".addslashes($item)."'";
            }
            else
            {
                $inElements .= $item;
            }
            $inElements .= self::DELIMITER;
        }
        $inElements  = rtrim($inElements, self::DELIMITER);
        self::$query .= "`".self::getTable()."`.`{$col}` IN ({$inElements}) AND ";
    }

    private static function prepareSelect()
    {
        self::$query = "SELECT ".self::selectColumns()." FROM `".self::getTable()."`";
        self::setConditions();
        self::setOrder();
        self::setHaving();
        self::setLimit();
    }

    public static function selectColumns()
    {
        self::$cols = is_string(self::$cols)? explode(',', self::$cols): self::$cols; 
        $query = '';
        foreach(self::$cols as $col)
        {
            if($col == '*')
            {
                $query .= '*';
                break; 
            }
            $query .= "`".self::getTable()."`.`{$col}`".self::DELIMITER;
        }
        return rtrim($query, self::DELIMITER);
    }

    private static function checkTimestamps()
    {
        if(self::$model->timestamps())
        {
            if(sizeof(self::$data)>0)
            {
                if(self::$type == 'insert' && !isset(self::$data['created_at']))
                {
                    self::$data['created_at'] = DB::NOW;
                }
                if(!isset($data['updated_at']))
                {
                    self::$data['updated_at'] = DB::NOW;
                }
            }
        }
    }

    private static function execute()
    {
        $result = DB::query(self::$query, self::$type);
        self::reset();
        return $result;
    }

    private static function reset()
    {
        self::$type   = '';
        self::$cols   = ['*'];
        self::$conds  = [];
        self::$order  = [];
        self::$limit  = NULL;
        self::$query  = NULL;
    }
}