<?php

namespace Amsify42\Tests;

use PHPUnit\Framework\TestCase;
use App\Models\User;

final class QueryBuilderTest extends TestCase
{
    public function testSelectAll()
    {
        $query = User::select()->query();
        $this->assertEquals('SELECT * FROM user', $query);
    }

    public function testConditions()
    {
        $model = User::select(['id', 'name'])->where('name', 'sami');
        $query = $model->query();
        $model->reset();
        $this->assertEquals("SELECT id,name FROM user WHERE name='sami'", $query);
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

    public function testPaginate()
    {
        $model = User::select('*')->paginate(10);
        var_dump($model); die;
        // $query = $model->query();
        // $model->reset();
        // $this->assertEquals("SELECT * FROM user LIMIT 10 OFFSET 0", $query);
    }

    public function remaining()
    {
        //$result = UserModel::all();
        //$result = UserModel::select('id, name')->orderBy('id', 'DESC')->all();
        //$result = UserModel::find(['name' => 'roohi begum'])->first();
        //$result = UserModel::where('name', 'roohi begum')->first();
        //$result = UserModel::where('id', 1)->count('id');
        //$result = UserModel::whereRaw("DATE(created_at)='2020-02-20'")->andRaw("IFNULL(image, '')!=''")->orRaw('YEAR(created_at)=2019')->and(['col1' => 'val1', 'col2' => ['1','2']])->or(['id' => '1', [' OR ' => ['name' => 'sami']], ['some' => 'value']])->or('updated_at', 'val')->and('created_at', 'some')->query();
        //$result = UserModel::select('COUNT(id) as count, id, name')->orderBy('id', 'DESC')->groupBy('id')->having('count > 0')->limit(5)->query();
        //$result = UserModel::select('COUNT(id) as count, id, name')->orderBy('id', 'DESC')->groupBy('id')->having(['count', '>', 0])->limit(5)->query();
        //$result = UserModel::where('name', 'Mohammad Samiullah')->all();
    }
}