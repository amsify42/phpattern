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
    private static $group  = NULL;
    private static $having = NULL;
    private static $limit  = NULL;
    private static $offset = NULL;
    private static $query  = NULL;

    const DELIMITER  = ',';
    const SQL_WHERE  = 'WHERE ';
    const SQL_RAW    = '__raw';
    const MULTI_COND = '__multi';
    const DEFAULT_OP = '='; 

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
        self::$cols = $cols;
        return self::$model;
    }

    public static function find($conds=[])
    {
        self::$conds = $conds;
        return self::$model;
    }

    public static function where($col, $op=NULL, $value=NULL)
    {
        return self::setCondition($col, $op, $value);
    }

    public static function and($col, $op=NULL, $value=NULL)
    {
        return self::setCondition($col, $op, $value, DB::SQL_AND);
    }

    public static function or($col, $op=NULL, $value=NULL)
    {
        return self::setCondition($col, $op, $value, DB::SQL_OR);
    }

    public static function whereRaw($clauses)
    {
        self::$conds[] = [self::SQL_RAW => self::SQL_WHERE.$clauses];
        return self::$model;
    }

    public static function andRaw($clause)
    {
        self::$conds[] = [self::SQL_RAW => DB::SQL_AND.$clause];
        return self::$model;
    }

    public static function orRaw($clause)
    {
        self::$conds[] = [self::SQL_RAW => DB::SQL_OR.$clause];
        return self::$model;
    }

    private static function setCondition($col, $op=NULL, $value=NULL, $conj=NULL)
    {
        if(is_array($col))
        {
            self::$conds[] = [self::MULTI_COND => $col, 'conj' => $conj];
        }
        else
        {
            if($conj && $conj == DB::SQL_OR)
            {
                self::$conds[] = [
                            'conj' => $conj,
                            'col'  => $col,
                            'op'   => ($value)? $op: self::DEFAULT_OP,
                            'val'  => ($value)? $value: $op
                        ];        
            }
            else
            {
                if($value)
                {
                    self::$conds[] = [
                            'conj' => DB::SQL_AND,
                            'col'  => $col,
                            'op'   => $op,
                            'val'  => $value
                        ]; 

                }
                else
                {
                    self::$conds[$col] = $op;
                }
            }
        }
        return self::$model;
    }

    public static function whereIn($col, $value)
    {
        self::$conds[$col] = is_array($value)? $value: [$value];
        return self::$model;
    }

    public static function like($col, $value)
    {
        self::$conds[$col] = $value;
        return self::$model;
    }

    public static function orderBy($col, $order='ASC')
    {
        self::$order[$col] = $order;
        return self::$model;
    }

    public static function groupBy($col)
    {
        self::$group = $col;
        return self::$model;
    }

    public static function having($havings)
    {
        self::$having = $havings;
        return self::$model;
    }

    public static function limit($limit, $offset=NULL)
    {
        self::$limit = $limit;
        if($offset)
        {
            self::offset($offset);
        }
        return self::$model;
    }

    public static function offset($offset)
    {
        self::$offset = $offset;
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

    public static function first($val='')
    {
        self::$limit = 1;
        self::buildQuery($val);
        $result = self::execute();
        return isset($result[0])? $result[0]: NULL;
    }

    public static function count($col='')
    {
        $el = ($col == 'id')? 'id': 1;
        self::$cols = "COUNT({$el}) as count";
        self::buildQuery();
        return self::execute();
    }

    public static function paginate($limit, $page=NULL)
    {
        if(!self::$limit)
        {
            self::$limit = $limit;
        }

        $page = ($page !== NULL)? $page: \PHPattern\Request::get('page');
        if(!$page)
        {
            $page = 1;
        }

        self::$offset = ($page-1)*self::$limit;
        self::buildQuery();
        return self::execute();
    }

    private function buildQuery($val='')
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
            self::prepareSelect($val);
        }
        self::$query = trim(self::$query);
    }

    private static function prepareInsert()
    {
        self::checkTimestamps();
        $query  = "INSERT INTO ".self::getTable()." (";
        $values = " VALUES (";
        foreach(self::$data as $column => $value)
        {
            $query .= "{$column},";
            if(in_array($column, self::$model->getTimestampsCols()))
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
        self::$query = "UPDATE ".self::getTable()." SET ".self::setValues();
        self::setConditions();
    }

    private static function setOrder()
    {
        if(!empty(self::$order))
        {
            self::$query .= " ORDER BY";
            foreach(self::$order as $col => $order)
            {
                self::$query .= " ".$col." ".$order.self::DELIMITER;
            }
            self::$query = rtrim(self::$query, self::DELIMITER);
        }
    }

    private static function setGroupBy()
    {
        if(self::$group)
        {
            self::$query .= " GROUP BY ";
            if(is_array(self::$group))
            {
                self::$query .= implode(',', self::$group);
            }
            else
            {
                self::$query .= self::$group;
            }
        }
    }

    private static function setHaving()
    {
        if(self::$having)
        {
            $query = " HAVING ";
            if(is_array(self::$having))
            {
                $isOne = false;
                foreach(self::$having as $col => $value)
                {
                    if($isOne)
                    {
                        $query .= $value;
                    }
                    else if(is_numeric($col))
                    {
                        if(is_array($value))
                        {
                            $alias = $value[0];
                            $op    = $value[1];
                            $val   = isset($value[2])? $value[2]: '';
                            $isVal = ($val || $val == '0')? true: false; 
                            $query .= $alias.($isVal? $op: self::DEFAULT_OP).($isVal? $val: $op).DB::SQL_AND;
                        }
                        else
                        {
                            $isOne = true;
                            $query .= $value;
                        }
                    }
                    else
                    {
                        $query .= $col.self::DEFAULT_OP.$value.DB::SQL_AND;
                    }
                }
                if(!$isOne)
                {
                    $query = rtrim($query, DB::SQL_AND);
                }
            }
            else
            {
                $query .= self::$having;
            }
            self::$query .= $query;
        }
    }

    private static function setLimit()
    {
        if(self::$limit)
        {
            self::$query .= " LIMIT ".self::$limit;
        }
        if(self::$offset)
        {
            self::$query .= " OFFSET ".self::$offset;
        }
    }

    private static function setValues()
    {
        $query = "";
        if(sizeof(self::$data)> 0)
        {
            foreach(self::$data as $column => $value)
            {
                $query .= "{$column}=";
                if(in_array($column, self::$model->getTimestampsCols()))
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
                self::$query .= self::SQL_WHERE;
                $clauses = '';
                foreach(self::$conds as $column => $value)
                {
                    if(is_array($value))
                    {
                        if(isset($value[self::SQL_RAW]))
                        {
                            if(self::startsWith($value[self::SQL_RAW], self::SQL_WHERE))
                            {
                                self::$query = str_replace(self::SQL_WHERE, '', self::$query);
                            }
                            $clauses .= $value[self::SQL_RAW];
                        }
                        else if(isset($value[self::MULTI_COND]))
                        {
                            $clauses .= self::whereChildRaw($value[self::MULTI_COND], $value['conj']);
                        }
                        else
                        {
                            $col = $column;
                            $op  = self::DEFAULT_OP;
                            $val = $value;
                            $conj= DB::SQL_AND;
                            $in  = true;
                            if(isset($value['conj']))
                            {
                                $col = $value['col'];
                                $val = $value['val'];
                                $conj= $value['conj'];
                                if(!is_array($val))
                                {
                                    $in = false;
                                }
                            }
                            if(isset($value['op']))
                            {
                                $op = $value['op'];
                            }
                            if($in)
                            {
                                $clauses .= self::whereInColVal($col, $val, $conj);
                            }
                            else
                            {
                                $clauses .= self::whereColVal($col, $op, $val, $conj);
                            }
                        }
                    }
                    else
                    {
                        $clauses .= self::whereColVal($column, self::DEFAULT_OP, $value);
                    }
                }
                self::$query .= ltrim($clauses, DB::SQL_AND);
            }
        }
        else if(self::$conds)
        {
            self::$query .= self::SQL_WHERE.self::$model->getPrimaryKey()."=".self::$conds;
        }
    }

    private static function startsWith($haystack, $needle)
    {
         $length = strlen($needle);
         return (substr($haystack, 0, $length) === $needle);
    }

    private static function whereChildRaw($columns, $conj=NULL)
    {
        $conds = ''; 
        if(sizeof($columns))
        {
            foreach($columns as $column => $value)
            {
                if(is_array($value))
                {
                    $col = $column;
                    $op  = self::DEFAULT_OP;
                    $val = $value;
                    $conj= DB::SQL_AND;
                    $in  = true;
                    $isN = false;
                    if(isset($value[DB::SQL_OR]))
                    {
                        foreach($value[DB::SQL_OR] as $colK => $valK)
                        {
                            $col = $colK;
                            $val = $valK;
                        }
                        $conj= DB::SQL_OR;
                        
                    }
                    else if(isset($value['conj']))
                    {
                        $col = $value['col'];
                        $val = $value['val'];
                        $conj= $value['conj'];
                    }
                    else if(is_numeric($column))
                    {
                        $isN = true;
                        foreach($value as $colK => $valK)
                        {
                            $col = $colK;
                            $val = $valK;
                        }
                    }
                    if(!$isN && isset($value['op']))
                    {
                        $op = $value['op'];
                    }
                    if(!is_array($val))
                    {
                        $in = false;
                    }
                    if($in)
                    {
                        $conds .= self::whereInColVal($col, $val, $conj);
                    }
                    else
                    {
                        $conds .= self::whereColVal($col, $op, $val, $conj);
                    }
                }
                else
                {
                    $conds .= self::whereColVal($column, self::DEFAULT_OP, $value);
                }
            }
        }
        return (($conj)? $conj: '').'('.ltrim($conds, DB::SQL_AND).')';
    }

    public static function whereColVal($col, $op, $value, $conj=NULL)
    {
        $query = ($conj)? $conj: DB::SQL_AND;
        if($value === NULL)
        {
            $query .= "{$col} IS NULL";
        }
        else
        {
            $query .= "{$col}".$op;
            if(is_string($value))
            {
                $query .= "'".addslashes($value)."'";
            }
            else
            {
                $query .= $value;
            }
        }
        return $query;
    }

    public static function whereInColVal($col, $items, $conj=NULL)
    {
        $query      = ($conj)? $conj: DB::SQL_AND;
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
        return $query."{$col} IN({$inElements})";
    }

    private static function prepareSelect($val='')
    {
        self::$query = "SELECT ".self::selectColumns()." FROM ".self::getTable()." ";
        if($val)
        {
            self::$conds[self::$model->getPrimaryKey()] = $val;
        }
        self::setConditions();
        self::setGroupBy();
        self::setHaving();
        self::setOrder();
        self::setLimit();
    }

    public static function selectColumns()
    {
        if(is_string(self::$cols))
        {
            return self::$cols;
        }
        else
        {
            $query = '';
            foreach(self::$cols as $col)
            {
                if($col == '*')
                {
                    $query .= '*';
                    break; 
                }
                $query .= "{$col}".self::DELIMITER;
            }
            return rtrim($query, self::DELIMITER);    
        }
    }

    private static function checkTimestamps()
    {
        if(self::$model->isTimestamps())
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

    public static function query()
    {
        self::buildQuery();
        return self::$query;
    }

    private static function execute()
    {
        $result = DB::query(self::$query, self::$type, '', self::$model->fetchObj(), (self::$model->isORM())? get_class(self::$model): '');
        self::reset();
        return $result;
    }

    public static function reset()
    {
        self::$type   = '';
        self::$cols   = ['*'];
        self::$conds  = [];
        self::$order  = [];
        self::$group  = NULL;
        self::$having = NULL;
        self::$limit  = NULL;
        self::$offset = NULL;
        self::$query  = NULL;
    }
}