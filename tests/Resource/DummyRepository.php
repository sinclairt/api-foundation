<?php

class DummyRepository extends \Sinclair\Repository\Repositories\Repository
{
    public function __construct( DummyModel $model )
    {
        $this->model = $model;
    }
}
