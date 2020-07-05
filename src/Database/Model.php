<?php

namespace PHPattern\Database;

use ReflectionClass;
use PHPattern\DB;
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

    private static function checkTableModel()
    {
        $model = Tables::retreiveModel(self::findTable());
        if(!$model)
        {
            $model = new static;
            Tables::storeModel($model->getTable(), $model);
        }
        return $model;
    }

    function __call($name, $args)
    {
        $model = self::checkTableModel();
        if(is_callable([QueryBuilder::class, $name]))
        {
            if(QueryBuilder::getModel() != $model)
            {
                QueryBuilder::setModel($model);
            }
            return call_user_func_array([QueryBuilder::class, $name], $args);
        }
        else if(isset($this->{$name}))
        {
            return $this->{$name};
        }
    }

    public static function __callStatic($name, $args)
    {
        $model = self::checkTableModel();
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

    public function save()
    {
        $props = (new \ReflectionObject($this))->getProperties(\ReflectionProperty::IS_PUBLIC);
        if(sizeof($props)> 0)
        {
            $row = [];
            foreach($props as $pk => $prop)
            {
                $row[$prop->name] = $this->{$prop->name};
            }
            if(isset($row[$this->primaryKey]))
            {
                $id = $row[$this->primaryKey];
                unset($row[$this->primaryKey]);
                $effected = self::update($row, $id);
                return ($effected > 0)? true: false;
            }
            else
            {
                $id = self::insert($row);
                if($id)
                {
                    $this->{$this->primaryKey} = $id;
                    return true;
                }
            }
        }
        return false;
    }

    public function remove()
    {
        if(isset($this->{$this->primaryKey}))
        {
            $effected = self::delete($this->{$this->primaryKey});
            return ($effected > 0)? true: false;
        }
        return false;
    }

    protected function hasMany($fKey, $fSource, $pKey=NULL)
    {
        $primaryKey = ($pKey)? $pKey: $this->primaryKey;
        $pKeyVal    = $this->{$primaryKey};
        if(class_exists($fSource))
        {
            return $fSource::select('*')->where($fKey, $pKeyVal)->all();
        }
        else
        {
            return DB::query("SELECT * FROM {$fSource} WHERE {$fKey}='{$pKeyVal}'");
        }
    }

    protected function hasOne($fKey, $fSource, $pKey=NULL)
    {
        return $this->relateOne('hasOne', $pKey, $fSource, $fKey);
    }

    protected function belongsTo($pKey, $fSource, $fKey='id')
    {
        return $this->relateOne('belongsTo', $pKey, $fSource, $fKey);
    }

    private function relateOne($type, $pKey, $fSource, $fKey)
    {
        $pKeyVal = '';
        if($type == 'hasOne')
        {
            $primaryKey = ($pKey)? $pKey: $this->primaryKey;
            $pKeyVal    = $this->{$primaryKey};
        }
        else
        {
            $pKeyVal = $this->{$pKey};
        }
        if(class_exists($fSource))
        {
            return $fSource::select('*')->where($fKey, $pKeyVal)->first();
        }
        else
        {
            return DB::query("SELECT * FROM {$fSource} WHERE {$fKey}={$pKeyVal} LIMIT 1");
        }
    }
}