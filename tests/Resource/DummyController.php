<?php

class DummyController extends \App\Http\Controllers\Controller
{
    use \Sinclair\ApiFoundation\Traits\ApiFoundation;

    public function __construct( DummyRepository $repository, \Sinclair\ApiFoundation\Transformers\DefaultTransformer $transformer )
    {
        $this->repository = $repository;

        $this->transformer = $transformer;
    }

}