<?php

use Mockery as m;
use yajra\Datatables\Datatables;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

class TestDatatablesQueryBuilderEngine extends PHPUnit_Framework_TestCase  {

	public function setUp()
	{
		parent::setUp();
		$app = m::mock('AppMock');
		$app->shouldReceive('instance')->once()->andReturn($app);

		Illuminate\Support\Facades\Facade::setFacadeApplication($app);
		Config::swap($config = m::mock('ConfigMock'));
	}

	public function tearDown()
	{
		m::close();
	}

	public function test_datatables_make_with_data()
	{
		$builder = $this->setupBuilder();
		// set Input variables
		$this->setupInputVariables();

		$datatables = new Datatables(Request::capture());

		$response = $datatables->usingQueryBuilder($builder)->make();

		$actual = $response->getContent();
		$expected = '{"draw":1,"recordsTotal":2,"recordsFiltered":2,"data":[[1,"foo"],[2,"bar"]]}';

		$this->assertInstanceOf('Illuminate\Http\JsonResponse', $response);
		$this->assertEquals($expected, $actual);
	}

	public function test_datatables_make_with_data_and_uses_mdata()
	{
		$builder = $this->setupBuilder();
		// set Input variables
		$this->setupInputVariables();

		$datatables = new Datatables(Request::capture());

		$response = $datatables->usingQueryBuilder($builder)->make(true);
		$actual = $response->getContent();
		$expected = '{"draw":1,"recordsTotal":2,"recordsFiltered":2,"data":[{"id":1,"name":"foo"},{"id":2,"name":"bar"}]}';

		$this->assertInstanceOf('Illuminate\Http\JsonResponse', $response);
		$this->assertEquals($expected, $actual);
	}

	protected function setupInputVariables()
	{
		$_GET = [];
		$_GET['draw'] = 1;
		$_GET['start'] = 0;
		$_GET['length'] = 10;
		$_GET['search']['value'] = '';
		$_GET['columns'][0]['name'] = 'foo';
		$_GET['columns'][0]['search']['value'] = '';
		$_GET['columns'][0]['searchable'] = true;
		$_GET['columns'][0]['orderable'] = true;
		$_GET['columns'][1]['name'] = 'bar';
		$_GET['columns'][1]['search']['value'] = '';
		$_GET['columns'][1]['searchable'] = true;
		$_GET['columns'][1]['orderable'] = true;

	}

	protected function getBuilder()
	{
		$grammar = new Illuminate\Database\Query\Grammars\Grammar;
		$processor = m::mock('Illuminate\Database\Query\Processors\Processor');
		return new Builder(m::mock('Illuminate\Database\Connection'), $grammar, $processor);
	}

	protected function setupBuilder($showAllRecords = false)
	{
		Config::shouldReceive('get');

		$cache = m::mock('stdClass');
		$driver = m::mock('stdClass');
		$data = array(
			array('id' => 1, 'name' => 'foo'),
			array('id' => 2, 'name' => 'bar'),
			);
		$builder = m::mock('Illuminate\Database\Query\Builder');
		$builder->shouldReceive('select')->once()->with(array('id','name'))->andReturn($builder);
		$builder->shouldReceive('from')->once()->with('users')->andReturn($builder);
		$builder->shouldReceive('get')->once()->andReturn($data);

		$builder->columns = array('id', 'name');
		$builder->select(array('id', 'name'))->from('users');

		// ******************************
		// Datatables::of() mocks
		// ******************************
		$builder->shouldReceive('getConnection')->andReturn(m::mock('Illuminate\Database\Connection'));
		$builder->shouldReceive('toSql')->times(6)->andReturn('select id, name from users');
		$builder->getConnection()->shouldReceive('raw')->once()->andReturn('select \'1\' as row_count');
		$builder->shouldReceive('select')->once()->andReturn($builder);
		$builder->getConnection()->shouldReceive('raw')->andReturn('(select id, name from users) count_row_table');
		$builder->shouldReceive('select')->once()->andReturn($builder);
		$builder->getConnection()->shouldReceive('table')->times(2)->andReturn($builder);
		$builder->shouldReceive('getBindings')->times(2)->andReturn(array());
		$builder->shouldReceive('setBindings')->times(2)->with(array())->andReturn($builder);

		// ******************************
		// Datatables::make() mocks
		// ******************************
		if ( ! $showAllRecords) {
			$builder->shouldReceive('skip')->once()->andReturn($builder);
			$builder->shouldReceive('take')->once()->andReturn($builder);
		}
		$builder->shouldReceive('count')->times(2)->andReturn(2);

		return $builder;
	}

}
