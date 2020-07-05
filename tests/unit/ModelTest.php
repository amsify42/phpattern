<?php

namespace Amsify42\Tests;

use PHPUnit\Framework\TestCase;
use App\Models\User;
use App\Models\Student;
use PHPattern\DB;

final class ModelTest extends TestCase
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

    public function testInsertUpdateDelete()
    {
        /**
         * Creating new model instance for new row
         */
        $user = new User();
        /**
         * Assigning value for one of the column
         */
        $user->name = 'sami';
        /**
         * Is row inserted
         */
        $this->assertTrue($user->save());
        /**
         * updating name in a row
         */
        $user->name = 'fasi';
        /**
         * Is row updated
         */
        $this->assertTrue($user->save());
        /**
         * Zero rows effected as same update applied
         */
        $this->assertFalse($user->save());
        /**
         * Primary key id should be assigned to property
         */
        $this->assertIsInt($user->id);
        /**
         * Deleting rowe by primary key
         */
        $this->assertTrue($user->remove());
        /**
         * Zero rows effected as row already deleted
         */
        $this->assertFalse($user->remove());
    }

    public function testInsertUpdateMultiple()
    {
        /**
         * Creating new model instance for new row
         */
        $student = new Student();
        /**
         * Assigning value for one of the column
         */
        $student->user_id = 1;
        $student->father_name = 'Mohd Nazeerullah';
        /**
         * Is row inserted
         */
        $this->assertTrue($student->save());
        /**
         * updating name in a row
         */
        $student->user_id = 2;
        /**
         * Is row updated
         */
        $this->assertTrue($student->save());
        /**
         * Deleting rowe by primary key
         */
        $this->assertTrue($student->remove());
    }
}