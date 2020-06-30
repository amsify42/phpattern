<?php

namespace PHPattern;

use PHPattern\File\Config;

class ENV
{
    private static $env = NULL;

    public static function get($key)
    {
        self::load();
        return self::$env->get($key);
    }

    private static function load()
    {
        if(!self::$env)
        {
            self::$env = new Config(ROOT_PATH.DS.'.env');
            if(!self::$env->isFile())
            {
                self::$env->saveContent("DB_CONNECTION=mysql\nDB_HOST=127.0.0.1\nDB_PORT=3306\nDB_NAME=phpattern\nDB_USER=root\nDB_PASSWORD=");
                self::$env->loadData();
            }
        }
    }
}