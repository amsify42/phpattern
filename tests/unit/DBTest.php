<?php

namespace Amsify42\Tests;

use PHPUnit\Framework\TestCase;
use App\Models\User;

final class DBTest extends TestCase
{
    function __construct()
    {
        if(!defined('DS'))
        {
            define('DS', DIRECTORY_SEPARATOR);
        }
        if(!defined('ROOT_PATH'))
        {
            define('ROOT_PATH', __DIR__.DS.'..');
        }
        parent::__construct();
    }

    public function testSelectAll()
    {
        $result = \PHPattern\DB::execute('SELECT * FROM user');
        $this->assertIsArray($result);
    }
}