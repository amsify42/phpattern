<?php

namespace PHPattern\Database;

use PHPattern\DB;
use \PHPattern\Database\Raw;

class QueryBuilder
{
    private static $model  = NULL;
    private static $type   = '';
    private static $data   = [];
    private static $cols   = ['*'];
    private static $alias  = NULL;
    private static $conds  = [];
    private static $order  = [];
    private static $join   = [];
    private static $actJTab= '';
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

    public static function alias($alias=NULL)
    {
        self::$alias = $alias;
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
                    if($op)
                    {
                        self::$conds[$col] = $op;
                    }
                    else
                    {
                        self::$conds[] = $col;   
                    }
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

    public static function join($table, $alias=NULL)
    {
        self::$actJTab      = $table;
        self::$join[$table] = ($alias)? ['alias' => $alias]: [];
        return self::$model;
    }

    public static function on($x, $op='=', $y=NULL)
    {
        if(self::$actJTab)
        {
            if(!isset(self::$join[self::$actJTab]['conds']))
            {
                self::$join[self::$actJTab]['conds'] = [];   
            }

            if(is_array($x))
            {
                foreach($x as $xk => $cond)
                {
                    self::$join[self::$actJTab]['conds'][$xk] = $cond;
                }
            }
            else
            {
                $condition = $x;
                if($y)
                {
                    $condition = $x.$op.$y;
                }
                self::$join[self::$actJTab]['conds'][] = $condition;   
            }
        }
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

    public static function insert($data=[], $execute=true)
    {
        self::$type = 'insert';
        self::$data = $data;
        if($execute)
        {
            self::buildQuery();
            return self::execute();
        }
        else
        {
            return self::$model;
        }
    }

    public static function set($data=[])
    {
        self::$type = 'update';
        self::$data= $data;
        return self::$model;
    }

    public static function update($data=[], $conds=[])
    {
        self::$type = 'update';
        if(!empty($data))
        {
            self::$data= $data;
        }
        if(!empty($conds))
        {
            self::$conds= $conds;
        }
        self::buildQuery();
        return self::execute();
    }

    public static function delete($conds=[])
    {
        self::$type = 'delete';
        if(!empty($conds))
        {
            self::$conds= $conds;
        }
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
        else if(self::$type == 'delete')
        {
            self::prepareDelete();
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
                $values .= ($value instanceof Raw || $value == DB::NOW)? $value: "'".$value."'";
            }
            else if(is_string($value))
            {
                $values .= "'".addslashes($value)."'";
            }
            else if($value instanceof Raw)
            {
                $values .= $value;
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
        self::$query = "UPDATE ".self::getTable()." SET ".self::setValues()." ";
        self::setConditions();
    }

    private static function prepareDelete()
    {
        self::checkTimestamps();
        self::$query = "DELETE FROM ".self::getTable()." ";
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
            self::$query = trim(self::$query)." GROUP BY ";
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
                if(is_numeric($column))
                {
                    $query .= $value;
                }
                else
                {
                    $query .= "{$column}=";
                    if(in_array($column, self::$model->getTimestampsCols()))
                    {
                        $query .= ($value instanceof Raw || $value == DB::NOW)? $value: "'".$value."'";
                    }
                    else if(is_string($value))
                    {
                        $query .= "'".addslashes($value)."'";
                    }
                    else
                    {
                        $query .= ($value === NULL)? "NULL": $value;
                    }  
                }
                $query .= self::DELIMITER;
            }
            $query = rtrim($query, self::DELIMITER);
        }
        return $query;
    }

    private static function setJoin()
    {
        if(sizeof(self::$join)> 0)
        {
            $i = 0;
            foreach(self::$join as $table => $tjoin)
            {
                $query = (($i)?" JOIN ":"JOIN ").$table.(isset($tjoin['alias'])?" ".$tjoin['alias']:"")." ON ";
                if(sizeof($tjoin['conds']) > 0)
                {
                    $clauses = '';
                    foreach($tjoin['conds'] as $ck => $cond)
                    {
                        $clauses .= self::clauseToRaw($ck, $cond, true);
                    }
                    $query .= substr($clauses, strlen(DB::SQL_AND));
                }
                self::$query .= $query;
                $i++;
            }
        }
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
                    if(is_array($value) && isset($value[self::MULTI_COND]))
                    {
                        $clauses .= self::conditionsToRaw($value[self::MULTI_COND], $value['conj']);
                    }
                    else
                    {
                        $clauses .= self::clauseToRaw($column, $value);
                    }
                }
                self::$query .= substr($clauses, strlen(DB::SQL_AND));
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

    private static function conditionsToRaw($columns, $conj=NULL)
    {
        $conds = ''; 
        if(sizeof($columns))
        {
            foreach($columns as $column => $value)
            {
                $conds .= self::clauseToRaw($column, $value);
            }
        }
        return (($conj)? $conj: '').'('. substr($conds, strlen(DB::SQL_AND)).')';
    }

    private static function clauseToRaw($column, $value, $isJoin=false)
    {
        $clause = '';
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
                $clause .= self::whereInColVal($col, $val, $conj);
            }
            else
            {
                $clause .= self::whereColVal($col, $op, $val, $conj, $isJoin);
            }
        }
        else
        {
            if(is_numeric($column))
            {
                $clause .= DB::SQL_AND.$value;
            }
            else
            {
                $clause .= self::whereColVal($column, self::DEFAULT_OP, $value, NULL, $isJoin);
            }
        }
        return $clause;
    }

    public static function whereColVal($col, $op, $value, $conj=NULL, $isJoin=false)
    {
        $query  = ($conj)? $conj: DB::SQL_AND;
        $query .= "{$col}".(($value !== NULL)? $op: "");
        if($value !== NULL)
        {
            if(is_string($value))
            {
                $query .= ($isJoin && strpos($value, '.') !== false)? $value:"'".addslashes($value)."'";
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
        self::$query = "SELECT ".self::selectColumns()." FROM ".self::getTable().((self::$alias)? " ".self::$alias." ": " ");
        if($val)
        {
            self::$conds[self::$model->getPrimaryKey()] = $val;
        }
        self::setJoin();
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

    public static function execute()
    {
        $result = DB::execute(self::$query, self::$type, '', self::$model->fetchObj(), (self::$model->isORM())? get_class(self::$model): '');
        self::reset();
        return $result;
    }

    public static function reset()
    {
        self::$type   = '';
        self::$cols   = ['*'];
        self::$alias  = NULL;
        self::$join   = [];
        self::$conds  = [];
        self::$order  = [];
        self::$group  = NULL;
        self::$having = NULL;
        self::$limit  = NULL;
        self::$offset = NULL;
        self::$query  = NULL;
    }
}