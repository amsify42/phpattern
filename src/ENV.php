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
                //self::$env->saveContent("");
                self::$env->loadData();
            }
        }
    }
}