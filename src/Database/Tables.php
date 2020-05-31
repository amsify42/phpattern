<?php

namespace PHPattern\Database;

use ReflectionClass;

class Tables
{
    private static $models = [];

    public static function storeModel($table, $model)
    {
        self::$models[$table] = $model;
    }

    public static function retreiveModel($table)
    {
        return self::isModel($table)? self::$models[$table]: NULL;
    }

    public static function isModel($table)
    {
        return isset(self::$models[$table])? true: false;
    }
}