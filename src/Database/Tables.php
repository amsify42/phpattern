<?php

namespace PHPattern\Database;

use ReflectionClass;

class Tables
{
    private static $queryBuilders = [];

    public static function storeQueryBuilder($table, $queryBuilder)
    {
        self::$queryBuilders[$table] = $queryBuilder;
    }

    public static function retreiveQueryBuilder($table)
    {
        return self::isQueryBuilder($table)? self::$queryBuilders[$table]: NULL;
    }

    public static function isQueryBuilder($table)
    {
        return isset(self::$queryBuilders[$table])? true: false;
    }
}