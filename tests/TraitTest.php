<?php

require_once 'DbTestCase.php';

/**
 * Class TraitTest
 */
class TraitTest extends DbTestCase
{
    /**
     * @var
     */
    private $faker;

    /**
     *
     */
    public function setUp()
    {
        parent::setUp();

        $this->migrate(__DIR__ . '/migrations');

        $this->migrate(__DIR__ . '/../vendor/laravel/laravel/database/migrations');

        $this->faker = Faker\Factory::create();
    }

    public function test_i_get_an_exception_when_class_does_not_have_repository_injected()
    {
        $this->setExpectedException(TypeError::class);

        new DummyController();
    }

    public function test_i_get_an_exception_when_class_does_not_have_transformer_injected()
    {
        $this->setExpectedException(TypeError::class);

        new DummyController(new DummyRepository(new DummyModel()));
    }

    public function test_my_controller_has_access_to_the_api_rest_methods()
    {
        $object = $this->makeController();

        $this->assertTrue(method_exists($object, 'index'));
        $this->assertTrue(method_exists($object, 'show'));
        $this->assertTrue(method_exists($object, 'store'));
        $this->assertTrue(method_exists($object, 'update'));
        $this->assertTrue(method_exists($object, 'destroy'));
        $this->assertTrue(method_exists($object, 'restore'));
        $this->assertTrue(method_exists($object, 'filter'));
    }

    public function test_i_can_get_all_rows_of_data_paginated()
    {
        $this->createDummies(20);

        $data = $this->makeController()
                     ->index();

        $this->assertTrue(is_array($data));

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('pagination', $data[ 'meta' ]);
        $this->assertArrayHasKey('total', $data[ 'meta' ][ 'pagination' ]);
        $this->assertArrayHasKey('count', $data[ 'meta' ][ 'pagination' ]);
        $this->assertArrayHasKey('per_page', $data[ 'meta' ][ 'pagination' ]);
        $this->assertArrayHasKey('current_page', $data[ 'meta' ][ 'pagination' ]);
        $this->assertArrayHasKey('total_pages', $data[ 'meta' ][ 'pagination' ]);
        $this->assertArrayHasKey('links', $data);
        $this->assertArrayHasKey('self', $data[ 'links' ]);
        $this->assertArrayHasKey('first', $data[ 'links' ]);
        $this->assertArrayHasKey('last', $data[ 'links' ]);
        $this->assertArrayHasKey('next', $data[ 'links' ]);

        $this->assertEquals(15, sizeof($data[ 'data' ]));
        $this->assertEquals(20, array_get($data, 'meta.pagination.total'));
        $this->assertEquals(15, array_get($data, 'meta.pagination.count'));
        $this->assertEquals(15, array_get($data, 'meta.pagination.per_page'));
        $this->assertEquals(1, array_get($data, 'meta.pagination.current_page'));
        $this->assertEquals(2, array_get($data, 'meta.pagination.total_pages'));
    }

    public function test_i_get_failed_response_when_i_provide_invalid_parameters_for_the_index_method()
    {
        request()->offsetSet('columns', [ 'foo' ]);

        $response = $this->makeController()
                         ->index();

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
    }

    public function test_i_can_get_the_second_page_of_results()
    {
        $this->createDummies(20);

        request()->offsetSet('page', 2);

        $data = $this->makeController()
                     ->index();

        $this->assertTrue(is_array($data));

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('pagination', $data[ 'meta' ]);
        $this->assertArrayHasKey('total', $data[ 'meta' ][ 'pagination' ]);
        $this->assertArrayHasKey('count', $data[ 'meta' ][ 'pagination' ]);
        $this->assertArrayHasKey('per_page', $data[ 'meta' ][ 'pagination' ]);
        $this->assertArrayHasKey('current_page', $data[ 'meta' ][ 'pagination' ]);
        $this->assertArrayHasKey('total_pages', $data[ 'meta' ][ 'pagination' ]);
        $this->assertArrayHasKey('links', $data);
        $this->assertArrayHasKey('self', $data[ 'links' ]);
        $this->assertArrayHasKey('first', $data[ 'links' ]);
        $this->assertArrayHasKey('last', $data[ 'links' ]);
        $this->assertArrayHasKey('prev', $data[ 'links' ]);

        $this->assertEquals(5, sizeof($data[ 'data' ]));
        $this->assertEquals(20, array_get($data, 'meta.pagination.total'));
        $this->assertEquals(5, array_get($data, 'meta.pagination.count'));
        $this->assertEquals(15, array_get($data, 'meta.pagination.per_page'));
        $this->assertEquals(2, array_get($data, 'meta.pagination.current_page'));
        $this->assertEquals(2, array_get($data, 'meta.pagination.total_pages'));
    }

    public function test_i_can_get_all_rows_of_data_paginated_with_filters()
    {
        $dummies = $this->createDummies(20);

        $names = $dummies->pluck('name')
                         ->toArray();

        $name = head(array_keys(array_count_values($names)));

        $total = head(array_count_values($names));

        $request = request();

        $request->offsetSet('name', $name);

        $data = $this->makeController()
                     ->filter($request);

        $this->assertTrue(is_array($data));

        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('meta', $data);
        $this->assertArrayHasKey('pagination', $data[ 'meta' ]);
        $this->assertArrayHasKey('total', $data[ 'meta' ][ 'pagination' ]);
        $this->assertArrayHasKey('count', $data[ 'meta' ][ 'pagination' ]);
        $this->assertArrayHasKey('per_page', $data[ 'meta' ][ 'pagination' ]);
        $this->assertArrayHasKey('current_page', $data[ 'meta' ][ 'pagination' ]);
        $this->assertArrayHasKey('total_pages', $data[ 'meta' ][ 'pagination' ]);
        $this->assertArrayHasKey('links', $data);
        $this->assertArrayHasKey('self', $data[ 'links' ]);
        $this->assertArrayHasKey('first', $data[ 'links' ]);
        $this->assertArrayHasKey('last', $data[ 'links' ]);
        if ( $total > 15 )
            $this->assertArrayHasKey('next', $data[ 'links' ]);

        $this->assertEquals($total > 15 ? 15 : $total, sizeof($data[ 'data' ]));
        $this->assertEquals($total, array_get($data, 'meta.pagination.total'));
        $this->assertEquals($total > 15 ? 15 : $total, array_get($data, 'meta.pagination.count'));
        $this->assertEquals(15, array_get($data, 'meta.pagination.per_page'));
        $this->assertEquals(1, array_get($data, 'meta.pagination.current_page'));
        $this->assertEquals(ceil($total / 15), array_get($data, 'meta.pagination.total_pages'));
    }

    public function test_i_get_failed_response_when_i_provide_invalid_parameters_for_the_filter_method()
    {
        request()->offsetSet('columns', [ 'foo' ]);

        $response = $this->makeController()
                         ->filter(request());

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
    }

    public function test_i_can_create_a_new_model()
    {
        $request = request();

        $name = $this->faker->word;
        $request->offsetSet('name', $name);

        $data = $this->makeController()
                     ->store($request);

        $this->assertTrue(is_array($data));
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('id', $data[ 'data' ]);
        $this->assertArrayHasKey('name', $data[ 'data' ]);
        $this->assertArrayHasKey('created_at', $data[ 'data' ]);
        $this->assertArrayHasKey('updated_at', $data[ 'data' ]);
        $this->assertEquals($data[ 'data' ][ 'name' ], $name);
    }

    public function test_i_get_a_failed_response_when_creating_a_model_incorrectly()
    {
        $response = $this->makeController()
                         ->store(request());

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
    }

    public function test_i_can_retrieve_a_model()
    {
        $dummy = $this->createDummies(1)
                      ->first();

        $data = $this->makeController()
                     ->show($dummy);

        $this->assertTrue(is_array($data));
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('id', $data[ 'data' ]);
        $this->assertArrayHasKey('name', $data[ 'data' ]);
        $this->assertArrayHasKey('created_at', $data[ 'data' ]);
        $this->assertArrayHasKey('updated_at', $data[ 'data' ]);

        $this->assertEquals($data[ 'data' ][ 'id' ], $dummy->id);
        $this->assertEquals($data[ 'data' ][ 'name' ], $dummy->name);
        $this->assertEquals($data[ 'data' ][ 'created_at' ], $dummy->created_at);
        $this->assertEquals($data[ 'data' ][ 'updated_at' ], $dummy->updated_at);
    }

    public function test_i_get_an_exception_when_supplying_a_object_that_does_not_implement_eloquent()
    {
        $this->setExpectedException(TypeError::class);

        $this->makeController()
             ->show(new stdClass());
    }

    public function test_i_can_update_a_model()
    {
        $dummy = $this->createDummies()
                      ->first();

        $name = $this->faker->word;

        request([ 'name', $name ]);

        $data = $this->makeController()
                     ->update(request(), $dummy);

        $this->assertTrue(is_array($data));
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('id', $data[ 'data' ]);
        $this->assertArrayHasKey('name', $data[ 'data' ]);
        $this->assertArrayHasKey('created_at', $data[ 'data' ]);
        $this->assertArrayHasKey('updated_at', $data[ 'data' ]);

        $this->assertEquals($data[ 'data' ][ 'id' ], $dummy->id);
        $this->assertEquals($data[ 'data' ][ 'name' ], $dummy->name);
        $this->assertEquals($data[ 'data' ][ 'created_at' ], $dummy->created_at);
        $this->assertEquals($data[ 'data' ][ 'updated_at' ], $dummy->updated_at);
    }

    public function test_i_can_destroy_a_model()
    {
        $dummy = $this->createDummies()
                      ->first();

        $data = $this->makeController()
                     ->destroy($dummy);

        $this->assertTrue(is_array($data));
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('id', $data[ 'data' ]);
        $this->assertArrayHasKey('name', $data[ 'data' ]);
        $this->assertArrayHasKey('created_at', $data[ 'data' ]);
        $this->assertArrayHasKey('updated_at', $data[ 'data' ]);
        $this->assertArrayHasKey('deleted_at', $data[ 'data' ]);

        $this->assertEquals($data[ 'data' ][ 'id' ], $dummy->id);
        $this->assertEquals($data[ 'data' ][ 'name' ], $dummy->name);
        $this->assertEquals($data[ 'data' ][ 'created_at' ], $dummy->created_at);
        $this->assertEquals($data[ 'data' ][ 'updated_at' ], $dummy->updated_at);
        $this->assertEquals($data[ 'data' ][ 'deleted_at' ], $dummy->deleted_at);
    }

    public function test_i_can_restore_a_model()
    {
        $dummy = $this->createDummies()
                      ->first();

        $dummy->delete();

        $data = $this->makeController()
                     ->restore($dummy);

        $this->assertTrue(is_array($data));
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('id', $data[ 'data' ]);
        $this->assertArrayHasKey('name', $data[ 'data' ]);
        $this->assertArrayHasKey('created_at', $data[ 'data' ]);
        $this->assertArrayHasKey('updated_at', $data[ 'data' ]);
        $this->assertArrayHasKey('deleted_at', $data[ 'data' ]);

        $this->assertEquals($data[ 'data' ][ 'id' ], $dummy->id);
        $this->assertEquals($data[ 'data' ][ 'name' ], $dummy->name);
        $this->assertEquals($data[ 'data' ][ 'created_at' ], $dummy->created_at);
        $this->assertEquals($data[ 'data' ][ 'updated_at' ], $dummy->updated_at);
        $this->assertNull($data[ 'data' ][ 'deleted_at' ]);
    }

    /**
     * @return DummyController
     */
    private function makeController()
    {
        return new DummyController(new DummyRepository(new DummyModel()), new \Sinclair\ApiFoundation\Transformers\DefaultTransformer());
    }

    /**
     * @param $count
     *
     * @return \Illuminate\Support\Collection
     */
    private function createDummies( $count = 1 )
    {
        $dummies = [];
        for ( $i = 0; $i < $count; $i++ )
            $dummies[] = DummyModel::create($this->getDummyData());

        return collect($dummies);
    }

    /**
     * @return array
     */
    private function getDummyData()
    {
        return [ 'name' => $this->faker->word ];
    }
}