<?php

namespace PHPattern\Database;

use ReflectionClass;
use PHPattern\Database\Tables;
use PHPattern\Database\QueryBuilder;

class Model
{
    protected $table          = '';
    protected $primaryKey     = 'id';
    protected $timestamps     = false;
    protected $pageLimit      = 10;

    protected $fetchObj       = true;
    protected $isORM          = true;
    protected $timestampsCols = ['created_at', 'updated_at'];

    function __construct()
    {
        if(!$this->table)
        {
            $this->table = self::findTable();
        }
    }

    private static function findTable()
    {
        $reflect = new ReflectionClass(get_called_class());
        return class_to_underscore($reflect->getShortName());
    }

    function __call($name, $args)
    {
        if(is_callable([QueryBuilder::class, $name]))
        {
            return call_user_func_array([QueryBuilder::class, $name], $args);
        }
        else if(isset($this->{$name}))
        {
            return $this->{$name};
        }
    }

    public static function __callStatic($name, $args)
    {
        $model = Tables::retreiveModel(self::findTable());
        if(!$model)
        {
            $model = new static;
            Tables::storeModel($model->getTable(), $model);
        }
        if(is_callable([QueryBuilder::class, $name]))
        {
            if(QueryBuilder::getModel() != $model)
            {
                QueryBuilder::setModel($model);
            }
            return call_user_func_array([QueryBuilder::class, $name], $args);
        }
    }

    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function setPrimaryKey($key = 'id')
    {
        $this->primaryKey = $key;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function setTimestamps($is=true)
    {
        $this->timestamps = $timestamps;
        return $this;
    }

    public function isTimestamps()
    {
        return $this->timestamps;
    }

    public function setPageLimit($pageLimit=10)
    {
        $this->pageLimit = $pageLimit;
        return $this;
    }

    public function getPageLimit()
    {
        return $this->pageLimit;
    }

    public function getTimestampsCols()
    {
        return $this->timestampsCols;
    }

    public function isFetchObj()
    {
        return $this->fetchObj;
    }

     public function isORM()
    {
        return $this->isORM;
    }
}