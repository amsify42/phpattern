<?php

namespace PHPattern\Database;

use PHPattern\DB;
use PHPattern\Database\Raw;

class QueryBuilder
{
    private $model  = NULL;
    private $type   = '';
    private $data   = [];
    private $cols   = ['*'];
    private $alias  = NULL;
    private $conds  = [];
    private $order  = [];
    private $join   = [];
    private $actJTab= '';
    private $group  = NULL;
    private $having = NULL;
    private $limit  = NULL;
    private $offset = NULL;
    private $query  = NULL;

    const DELIMITER  = ',';
    const SQL_WHERE  = 'WHERE ';
    const MULTI_COND = '__multi';
    const DEFAULT_OP = '='; 

    function __construct($model)
    {
        $this->model = $model;
    }

    private function getTable()
    {
        return ($this->model)? $this->model->getTable(): NULL;
    }

    public function select($cols=['*'])
    {
        $this->type = '';
        $this->cols = $cols;
        return $this;
    }

    public function alias($alias=NULL)
    {
        $this->alias = $alias;
        return $this;
    }

    public function where($col, $op=NULL, $value=NULL)
    {
        return $this->setCondition($col, $op, $value);
    }

    public function and($col, $op=NULL, $value=NULL)
    {
        return $this->setCondition($col, $op, $value, DB::SQL_AND);
    }

    public function or($col, $op=NULL, $value=NULL)
    {
        return $this->setCondition($col, $op, $value, DB::SQL_OR);
    }

    private function setCondition($col, $op=NULL, $value=NULL, $conj=NULL)
    {
        if(is_array($col))
        {
            $this->conds[] = [self::MULTI_COND => $col, 'conj' => $conj];
        }
        else
        {
            if($conj && $conj == DB::SQL_OR)
            {
                $this->conds[] = [
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
                    $this->conds[] = [
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
                        $this->conds[$col] = $op;
                    }
                    else
                    {
                        $this->conds[] = $col;   
                    }
                }
            }
        }
        return $this;
    }

    public function whereIn($col, $value)
    {
        $this->conds[$col] = is_array($value)? $value: [$value];
        return $this;
    }

    public function like($col, $value)
    {
        $this->conds[$col] = $value;
        return $this;
    }

    public function join($table, $alias=NULL)
    {
        $this->actJTab      = $table;
        $this->join[$table] = ($alias)? ['alias' => $alias]: [];
        return $this;
    }

    public function leftJoin($table, $alias=NULL)
    {
        $this->actJTab = $table;
        $info = ['type' => 'LEFT'];
        if($alias !== NULL)
        {
            $info['alias'] = $alias;
        }
        $this->join[$table] = $info;
        return $this;
    }

    public function rightJoin($table, $alias=NULL)
    {
        $this->actJTab = $table;
        $info = ['type' => 'RIGHT'];
        if($alias !== NULL)
        {
            $info['alias'] = $alias;
        }
        $this->join[$table] = $info;
        return $this;
    }

    public function fullOuterJoin($table, $alias=NULL)
    {
        $this->actJTab = $table;
        $info = ['type' => 'FULL OUTER'];
        if($alias !== NULL)
        {
            $info['alias'] = $alias;
        }
        $this->join[$table] = $info;
        return $this;
    }

    public function on($x, $op='=', $y=NULL)
    {
        if($this->actJTab)
        {
            if(!isset($this->join[$this->actJTab]['conds']))
            {
                $this->join[$this->actJTab]['conds'] = [];   
            }

            if(is_array($x))
            {
                foreach($x as $xk => $cond)
                {
                    $this->join[$this->actJTab]['conds'][$xk] = $cond;
                }
            }
            else
            {
                $condition = $x;
                if($y)
                {
                    $condition = $x.$op.$y;
                }
                $this->join[$this->actJTab]['conds'][] = $condition;   
            }
        }
        return $this;
    }

    public function orderBy($col, $order='ASC')
    {
        $this->order[$col] = $order;
        return $this;
    }

    public function groupBy($col)
    {
        $this->group = $col;
        return $this;
    }

    public function having($havings)
    {
        $this->having = $havings;
        return $this;
    }

    public function limit($limit, $offset=NULL)
    {
        $this->limit = $limit;
        if($offset)
        {
            $this->offset($offset);
        }
        return $this;
    }

    public function offset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    public function insert($data=[], $execute=true)
    {
        $this->type = 'insert';
        $this->data = $data;
        if($execute)
        {
            $this->buildQuery();
            return $this->execute();
        }
        else
        {
            return $this;
        }
    }

    public function set($data=[])
    {
        $this->type = 'update';
        $this->data= $data;
        return $this;
    }

    public function update($data=[], $conds=[])
    {
        $this->type = 'update';
        if(!empty($data))
        {
            $this->data= $data;
        }
        if(!empty($conds))
        {
            $this->conds= $conds;
        }
        $this->buildQuery();
        return $this->execute();
    }

    public function delete($conds=[])
    {
        $this->type = 'delete';
        if(!empty($conds))
        {
            $this->conds= $conds;
        }
        $this->buildQuery();
        return $this->execute();
    }

    public function all()
    {
        $this->buildQuery();
        return $this->execute();
    }

    public function first($val='')
    {
        $this->limit = 1;
        $this->buildQuery($val);
        $result = $this->execute();
        return isset($result[0])? $result[0]: NULL;
    }

    public function count($col='')
    {
        $el = ($col == 'id')? 'id': 1;
        $this->cols = "COUNT({$el}) as count";
        $this->buildQuery();
        return $this->execute();
    }

    public function paginate($limit, $page=NULL)
    {
        if(!$this->limit)
        {
            $this->limit = $limit;
        }

        $page = ($page !== NULL)? $page: \PHPattern\Request::get('page');
        if(!$page)
        {
            $page = 1;
        }

        $this->offset = ($page-1)*$this->limit;
        $this->buildQuery();
        return $this->execute();
    }

    private function buildQuery($val='')
    {
        if($this->type == 'insert')
        {
            $this->prepareInsert();
        }
        else if($this->type == 'update')
        {
            $this->prepareUpdate();
        }
        else if($this->type == 'delete')
        {
            $this->prepareDelete();
        }
        else
        {
            $this->prepareSelect($val);
        }
        $this->query = trim($this->query);
    }

    private function prepareInsert()
    {
        $this->checkTimestamps();
        $query  = "INSERT INTO ".$this->getTable()." (";
        $values = " VALUES (";
        foreach($this->data as $column => $value)
        {
            $query .= "{$column},";
            if(in_array($column, $this->model->getTimestampsCols()))
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
        $this->query = $query.$values;
    }

    private function prepareUpdate()
    {
        $this->checkTimestamps();
        $this->query = "UPDATE ".$this->getTable()." SET ".$this->setValues()." ";
        $this->setConditions();
    }

    private function prepareDelete()
    {
        $this->checkTimestamps();
        $this->query = "DELETE FROM ".$this->getTable()." ";
        $this->setConditions();
    }

    private function setOrder()
    {
        if(!empty($this->order))
        {
            $this->query .= " ORDER BY";
            foreach($this->order as $col => $order)
            {
                $this->query .= " ".$col." ".$order.self::DELIMITER;
            }
            $this->query = rtrim($this->query, self::DELIMITER);
        }
    }

    private function setGroupBy()
    {
        if($this->group)
        {
            $this->query = trim($this->query)." GROUP BY ";
            if(is_array($this->group))
            {
                $this->query .= implode(',', $this->group);
            }
            else
            {
                $this->query .= $this->group;
            }
        }
    }

    private function setHaving()
    {
        if($this->having)
        {
            $query = " HAVING ";
            if(is_array($this->having))
            {
                $isOne = false;
                foreach($this->having as $col => $value)
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
                $query .= $this->having;
            }
            $this->query .= $query;
        }
    }

    private function setLimit()
    {
        if($this->limit)
        {
            $this->query .= " LIMIT ".$this->limit;
        }
        if($this->offset)
        {
            $this->query .= " OFFSET ".$this->offset;
        }
    }

    private function setValues()
    {
        $query = "";
        if(sizeof($this->data)> 0)
        {
            foreach($this->data as $column => $value)
            {
                if(is_numeric($column))
                {
                    $query .= $value;
                }
                else
                {
                    $query .= "{$column}=";
                    if(in_array($column, $this->model->getTimestampsCols()))
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

    private function setJoin()
    {
        if(sizeof($this->join)> 0)
        {
            $i = 0;
            foreach($this->join as $table => $tjoin)
            {
                $joinStr = isset($tjoin['type'])? $tjoin['type']." JOIN ": "JOIN ";
                $query   = (($i)?" ".$joinStr: $joinStr).$table.(isset($tjoin['alias'])?" ".$tjoin['alias']:"")." ON ";
                if(sizeof($tjoin['conds']) > 0)
                {
                    $clauses = '';
                    foreach($tjoin['conds'] as $ck => $cond)
                    {
                        $clauses .= $this->clauseToRaw($ck, $cond, true);
                    }
                    $query .= substr($clauses, strlen(DB::SQL_AND));
                }
                $this->query .= $query;
                $i++;
            }
        }
    }

    private function setConditions()
    {
        if(is_array($this->conds))
        {
            if(sizeof($this->conds)> 0)
            {
                $this->query .= self::SQL_WHERE;
                $clauses = '';
                foreach($this->conds as $column => $value)
                {
                    if(is_array($value) && isset($value[self::MULTI_COND]))
                    {
                        $clauses .= $this->conditionsToRaw($value[self::MULTI_COND], $value['conj']);
                    }
                    else
                    {
                        $clauses .= $this->clauseToRaw($column, $value);
                    }
                }
                $this->query .= substr($clauses, strlen(DB::SQL_AND));
            }
        }
        else if($this->conds)
        {
            $this->query .= self::SQL_WHERE.$this->model->getPrimaryKey()."=".$this->conds;
        }
    }

    private function startsWith($haystack, $needle)
    {
         $length = strlen($needle);
         return (substr($haystack, 0, $length) === $needle);
    }

    private function conditionsToRaw($columns, $conj=NULL)
    {
        $conds = ''; 
        if(sizeof($columns))
        {
            foreach($columns as $column => $value)
            {
                $conds .= $this->clauseToRaw($column, $value);
            }
        }
        return (($conj)? $conj: '').'('. substr($conds, strlen(DB::SQL_AND)).')';
    }

    private function clauseToRaw($column, $value, $isJoin=false)
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
                $clause .= $this->whereInColVal($col, $val, $conj);
            }
            else
            {
                $clause .= $this->whereColVal($col, $op, $val, $conj, $isJoin);
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
                $clause .= $this->whereColVal($column, self::DEFAULT_OP, $value, NULL, $isJoin);
            }
        }
        return $clause;
    }

    public function whereColVal($col, $op, $value, $conj=NULL, $isJoin=false)
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

    public function whereInColVal($col, $items, $conj=NULL)
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

    private function prepareSelect($val='')
    {
        $this->query = "SELECT ".$this->selectColumns()." FROM ".$this->getTable().(($this->alias)? " ".$this->alias." ": " ");
        if($val)
        {
            $this->conds[$this->model->getPrimaryKey()] = $val;
        }
        $this->setJoin();
        $this->setConditions();
        $this->setGroupBy();
        $this->setHaving();
        $this->setOrder();
        $this->setLimit();
    }

    public function selectColumns()
    {
        if(is_string($this->cols))
        {
            return $this->cols;
        }
        else
        {
            $query = '';
            foreach($this->cols as $col)
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

    private function checkTimestamps()
    {
        if($this->model->isTimestamps())
        {
            if(sizeof($this->data)>0)
            {
                if($this->type == 'insert' && !isset($this->data['created_at']))
                {
                    $this->data['created_at'] = DB::NOW;
                }
                if(!isset($data['updated_at']))
                {
                    $this->data['updated_at'] = DB::NOW;
                }
            }
        }
    }

    public function query()
    {
        $this->buildQuery();
        return $this->query;
    }

    public function execute()
    {
        $result = DB::execute($this->query, $this->type, '', $this->model->fetchObj(), ($this->model->isORM())? get_class($this->model): '');
        $this->reset();
        return $result;
    }

    public function reset()
    {
        $this->type   = '';
        $this->cols   = ['*'];
        $this->alias  = NULL;
        $this->join   = [];
        $this->conds  = [];
        $this->order  = [];
        $this->group  = NULL;
        $this->having = NULL;
        $this->limit  = NULL;
        $this->offset = NULL;
        $this->query  = NULL;
    }
}