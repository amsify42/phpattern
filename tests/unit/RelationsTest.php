<?php

namespace Amsify42\Tests;

use PHPUnit\Framework\TestCase;
use App\Models\User;
use App\Models\Student;
use App\Models\Category;
use PHPattern\DB;

final class RelationsTest extends TestCase
{
    function __construct()
    {
        if(!defined('DS'))
        {
            define('DS', DIRECTORY_SEPARATOR);
        }
        if(!defined('APP_PATH'))
        {
            define('APP_PATH', __DIR__.DS.'..'.DS.'app');
        }
        if(!defined('ROOT_PATH'))
        {
            define('ROOT_PATH', APP_PATH.DS.'..');
        }
        parent::__construct();
    }

    public function testHasMany()
    {
        $user = User::first(2);
        $this->assertIsArray($user->categories());
    }

    public function testHasOne()
    {
        $user = User::first(2);
        $this->assertInstanceOf(Student::class, $user->student());
    }

    public function testBelongsTo()
    {
        $category = Category::first(1);
        $this->assertInstanceOf(User::class, $category->user());
    }
}