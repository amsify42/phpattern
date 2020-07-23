<?php

namespace Amsify42\Tests;

use PHPUnit\Framework\TestCase;
use App\Models\User;
use PHPattern\DB;

final class QueryBuilderTest extends TestCase
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
        $insertId = User::insert(['name' => 'Test name']);
        $this->assertIsInt($insertId);

        $this->idTestUpdate($insertId);
        $this->idTestDelete($insertId);
    }

    private function idTestUpdate($id)
    {
        $effected = User::update(['name' => 'Test name edited'], $id);
        $this->assertEquals(1, $effected);

        $effected = User::update(['name' => 'Test name edited 2'], ['name' => 'Test name edited']);
        $this->assertEquals(1, $effected);
    }

    private function idTestDelete($id)
    {
        $effected = User::delete($id);
        $this->assertEquals(1, $effected);

        $effected = User::delete(['name' => 'Test name edited 2']);
        $this->assertEquals(0, $effected);
    }

    public function testSelectAll()
    {
        $results = User::all();
        $this->assertIsArray($results);

        $query = User::select()->query();
        $this->assertEquals('SELECT * FROM user', $query);
    }

    public function testConditions()
    {
        $model = User::select('id, name')->where('name', 'sami');
        $query = $model->query();
        $model->reset();
        $this->assertEquals("SELECT id, name FROM user WHERE name='sami'", $query);

        $model = User::select('id, name')->where('name', 'sami')->and('id', 1);
        $query = $model->query();
        $model->reset();
        $this->assertEquals("SELECT id, name FROM user WHERE name='sami' AND id=1", $query);
    }

    public function testOrderBy()
    {
        $model = User::select(['id', 'name'])->where('name', 'sami')->orderBy('id', 'DESC');
        $query = $model->query();
        $model->reset();
        $this->assertEquals("SELECT id,name FROM user WHERE name='sami' ORDER BY id DESC", $query);
    }

    public function testGroupBy()
    {
        $model = User::select(['id', 'name'])->where('name', 'sami')->groupBy('id');
        $query = $model->query();
        $model->reset();
        $this->assertEquals("SELECT id,name FROM user WHERE name='sami' GROUP BY id", $query);
    }

    public function testHaving()
    {
        $model = User::select('COUNT(1) AS count')->where('name', 'sami')->having(['count', '>', 10]);
        $query = $model->query();
        $model->reset();
        $this->assertEquals("SELECT COUNT(1) AS count FROM user WHERE name='sami' HAVING count>10", $query);

        $model = User::select('COUNT(1) AS count')->where('name', 'sami')->having([['count', '>', 0],['count', '<', 10]]);
        $query = $model->query();
        $model->reset();
        $this->assertEquals("SELECT COUNT(1) AS count FROM user WHERE name='sami' HAVING count>0 AND count<10", $query);
    }

    public function testRaw()
    {
        $model = User::select(['id', 'NOW()'])->where('DATE(created_at)>2020-07-10');
        $query = $model->query();
        $model->reset();
        $this->assertEquals("SELECT id,NOW() FROM user WHERE DATE(created_at)>2020-07-10", $query);

        $model = User::set(['name' => 'sami', 'created_at = NOW() - INTERVAL 1 DAY'])->where('DATE(created_at)>2020-07-10');
        $query = $model->query();
        $model->reset();
        $this->assertEquals("UPDATE user SET name='sami',created_at = NOW() - INTERVAL 1 DAY,updated_at=NOW() WHERE DATE(created_at)>2020-07-10", $query);

        $model = User::where("DATE(created_at)='2020-02-20'")->and("IFNULL(image, '')!=''")->or('YEAR(created_at)=2019')->and(['col1' => 'val1', 'col2' => ['1','2']])->or(['id' => '1', [DB::SQL_OR => ['name' => 'sami']], ['some' => 'value']])->or('updated_at', 'val')->and('created_at', 'some');
        $query = $model->query();
        $model->reset();
        $this->assertEquals("SELECT * FROM user WHERE DATE(created_at)='2020-02-20' AND IFNULL(image, '')!='' OR YEAR(created_at)=2019 AND (col1='val1' AND col2 IN('1','2')) OR (id='1' OR name='sami' AND some='value') OR updated_at='val' AND created_at='some'", $query);
    }

    public function testLimit()
    {
        $model = User::select('COUNT(id) as count, id, name')->orderBy('id', 'DESC')->groupBy('id')->limit(5);
        $query = $model->query();
        $model->reset();
        $this->assertEquals("SELECT COUNT(id) as count, id, name FROM user GROUP BY id ORDER BY id DESC LIMIT 5", $query);

        $model = User::select('*')->groupBy('id')->limit(10, 10);
        $query = $model->query();
        $model->reset();
        $this->assertEquals("SELECT * FROM user GROUP BY id LIMIT 10 OFFSET 10", $query);
    }

    public function testPaginate()
    {
        $results = User::select('*')->paginate(10);
        $this->assertIsArray($results);
    }

    public function testJoin()
    {
        $model = User::select('*')->join('student')->on('user.id=student.user_id');
        $query = $model->query();
        $model->reset();
        $this->assertEquals('SELECT * FROM user JOIN student ON user.id=student.user_id', $query);

        $model = User::select('*')->join('student')->on('user.id', '=', 'student.user_id');
        $query = $model->query();
        $model->reset();
        $this->assertEquals('SELECT * FROM user JOIN student ON user.id=student.user_id', $query);

        $model = User::select('*')->join('student')->on(['user.id' => 'student.user_id']);
        $query = $model->query();
        $model->reset();
        $this->assertEquals('SELECT * FROM user JOIN student ON user.id=student.user_id', $query);

        $model = User::select('*')->join('student')->on(['user.id' => 'student.user_id', 'student.name' => 1]);
        $query = $model->query();
        $model->reset();
        $this->assertEquals('SELECT * FROM user JOIN student ON user.id=student.user_id AND student.name=1', $query);

        $model = User::select('*')->join('student')->on(['user.id' => 'student.user_id', 'user.active' => 'student.active']);
        $query = $model->query();
        $model->reset();
        $this->assertEquals('SELECT * FROM user JOIN student ON user.id=student.user_id AND user.active=student.active', $query);

        $model = User::select('*')->join('student')->on('user.id', '=', 'student.user_id')->join('user_student')->on('user.id', '=', 'user_student.user_id');
        $query = $model->query();
        $model->reset();
        $this->assertEquals('SELECT * FROM user JOIN student ON user.id=student.user_id JOIN user_student ON user.id=user_student.user_id', $query);
    }

    public function testJoinResult()
    {
        $results = User::select(['u.id', 'u.name', 's.id', 's.user_id', 's.father_name'])->alias('u')->join('student', 's')->on(['u.id' => 's.user_id'])->all();
        $this->assertIsArray($results);

        $result = User::select(['u.id', 'u.name', 's.id', 's.user_id', 's.father_name'])->alias('u')->join('student', 's')->on(['u.id' => 's.user_id'])->first();
        $this->assertInstanceOf(User::class, $result);
    }
}