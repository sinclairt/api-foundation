<?php

use Illuminate\Routing\Router;

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

        Route::get('/', 'DummyController@index');

        $this->getJson('/')->seeJsonStructure([
            'data',
            'meta'  => [
                'pagination' => [
                    'total',
                    'count',
                    'per_page',
                    'current_page',
                    'total_pages',
                ],
            ],
            'links' => [
                'self',
                'first',
                'last',
                'next',
            ],
        ])->seeJson([
            'meta' => [
                'pagination' => [
                    'total'        => 20,
                    'count'        => 15,
                    'per_page'     => 15,
                    'current_page' => 1,
                    'total_pages'  => 2,
                ],
            ],
        ]);
    }

    public function test_i_get_failed_response_when_i_provide_invalid_parameters_for_the_index_method()
    {
        Route::get('/', 'DummyController@index');

        $this->getJson('/?rows=foo,bar,baz')->assertResponseStatus(400);
    }

    public function test_i_can_get_the_second_page_of_results()
    {
        Route::get('/', 'DummyController@index');

        $this->createDummies(20);

        $this->getJson('/?page=2')->seeJsonStructure([
            'data',
            'meta'  => [
                'pagination' => [
                    'total',
                    'count',
                    'per_page',
                    'current_page',
                    'total_pages',
                ],
            ],
            'links' => [
                'self',
                'first',
                'last',
            ],
        ])->seeJson([
            'meta' => [
                'pagination' => [
                    'total'        => 20,
                    'count'        => 5,
                    'per_page'     => 15,
                    'current_page' => 2,
                    'total_pages'  => 2,
                ],
            ],
        ]);
    }

    public function test_i_can_get_all_rows_of_data_paginated_with_filters()
    {
        Route::post('/filter', 'DummyController@filter');

        $dummies = $this->createDummies(20);

        $names = $dummies->pluck('name')
                         ->toArray();

        $name = head(array_keys(array_count_values($names)));

        $total = head(array_count_values($names));

        $json = $this->postJson('/filter', ['name' => $name]);

        $json->seeJsonStructure([
            'data',
            'meta'  => [
                'pagination' => [
                    'total',
                    'count',
                    'per_page',
                    'current_page',
                    'total_pages',
                ],
            ],
            'links' => [
                'self',
                'first',
                'last',
            ],
        ])->seeJson([
            'meta' => [
                'pagination' => [
                    'total'        => $total,
                    'count'        => $total > 15 ? 15 : $total,
                    'per_page'     => 15,
                    'current_page' => 1,
                    'total_pages'  => ceil($total / 15),
                ],
            ],
        ]);

        if ($total > 15)
            $json->seeJsonStructure([
                'links' => [
                    'next',
                ],
            ]);
    }

    public function test_i_get_failed_response_when_i_provide_invalid_parameters_for_the_filter_method()
    {
        Route::post('/filter', 'DummyController@filter');

        $this->postJson('/filter', ['rows' => 'foo'])->assertResponseStatus(400);
    }

    public function test_i_can_create_a_new_model()
    {
        Route::post('/', 'DummyController@store');

        $name = $this->faker->word;

        $this->postJson('/', [
            'name' => $name,
        ])->seeJson([
            'name' => $name,
        ])->seeJsonStructure([
            'data' => [
                'id',
                'name',
                'created_at',
                'updated_at',
            ],
        ]);
    }

    public function test_i_get_a_failed_response_when_creating_a_model_incorrectly()
    {
        Route::post('/', 'DummyController@store');

        $this->postJson('/', [])->assertResponseStatus(400);
    }

    public function test_i_can_retrieve_a_model()
    {
        app('router')->get('/dummy/{dummy}', function ($dummy)
        {
            try
            {
                $dummy = DummyModel::withTrashed()->find($dummy);

                return call_user_func_array([
                    app('DummyController'),
                    'show',
                ], [$dummy]);
            }
            catch (Exception $e)
            {
                dd($e->getMessage());
            }
        });

        $dummy = $this->createDummies(1)
                      ->first();

        $this->getJson('/dummy/' . $dummy->id)->seeJsonStructure([
            'data' => [
                'id',
                'name',
                'created_at',
                'updated_at',
            ],
        ])->seeJson($dummy->toArray());
    }

    public function test_i_get_an_exception_when_supplying_a_object_that_does_not_implement_eloquent()
    {
        Route::get('/dummy/{id}', 'DummyController@show');

        $this->getJson('/dummy/1')->assertResponseStatus(500);
    }

    public function test_i_can_update_a_model()
    {
        // mocking route model binding
        app('router')->put('/dummy/{dummy}', function ($dummy)
        {
            $model = DummyModel::withTrashed()->find($dummy);

            try
            {
                return call_user_func_array([
                    app('DummyController'),
                    'update',
                ], [
                    request(),
                    $model,
                ]);
            }
            catch (Exception $e)
            {
                dd($e->getMessage());
            }
        });

        $dummy = $this->createDummies()
                      ->first();

        $name = $this->faker->word;

        $this->putJson('/dummy/' . $dummy->id, [
            'name' => $name,
        ])->seeJson([
            'name' => $name,
        ])->seeJsonStructure([
            'data' => [
                'id',
                'name',
                'created_at',
                'updated_at',
            ],
        ]);
    }

    public function test_i_can_destroy_a_model()
    {
        // mocking route model binding
        \Route::delete('/dummy/{dummy}', function ($dummy)
        {
            $model = DummyModel::withTrashed()->find($dummy);

            try
            {
                return call_user_func_array([
                    app('DummyController'),
                    'destroy',
                ], [$model,]);
            }
            catch (Exception $e)
            {
                dd($e->getMessage());
            }
        });

        $dummy = $this->createDummies()
                      ->first();

        $this->deleteJson('/dummy/' . $dummy->id)
             ->seeJsonStructure([
                 'data' => [
                     'id',
                     'name',
                     'created_at',
                     'updated_at',
                     'deleted_at',
                 ],
             ]);
    }

    public function test_i_can_restore_a_model()
    {
        // mocking route model binding
        \Route::get('/dummy/{dummy}/restore', function ($dummy)
        {
            $model = DummyModel::withTrashed()->find($dummy);

            try
            {
                return call_user_func_array([
                    app('DummyController'),
                    'restore',
                ], [$model]);
            }
            catch (Exception $e)
            {
                dd($e->getMessage());
            }
        });

        $dummy = $this->createDummies()
                      ->first();

        $dummy->delete();

        $this->getJson('/dummy/' . $dummy->id . '/restore')
             ->seeJsonStructure([
                 'data' => [
                     'id',
                     'name',
                     'created_at',
                     'updated_at',
                     'deleted_at',
                 ],
             ])->seeJson(['deleted_at' => null]);
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
    private function createDummies($count = 1)
    {
        $dummies = [];
        for ($i = 0; $i < $count; $i++)
            $dummies[] = DummyModel::create($this->getDummyData());

        return collect($dummies);
    }

    /**
     * @return array
     */
    private function getDummyData()
    {
        return ['name' => $this->faker->word];
    }
}