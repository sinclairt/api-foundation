<?php

namespace Sinclair\ApiFoundation\Traits;

use Illuminate\Http\Request;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Serializer\JsonApiSerializer;
use League\Fractal\TransformerAbstract;
use Sinclair\ApiFoundation\Transformers\DefaultTransformer;
use Sinclair\Repository\Contracts\Repository;

/**
 * Class ApiFoundation
 */
trait ApiFoundation
{
    /**
     * @var Repository
     */
    protected $repository;

    /**
     * @var DefaultTransformer
     */
    protected $transformer;

    /**
     * @var null|string
     */
    protected $resourceName;

    /**
     * ApiFoundation constructor.
     *
     * @param Repository $repository
     * @param TransformerAbstract $transformer
     * @param null $resourceName
     */
    public function __construct( Repository $repository, TransformerAbstract $transformer, $resourceName = null )
    {
        $this->repository = $repository;

        $this->transformer = $transformer;

        $this->resourceName = $resourceName;
    }

    /**
     * @param Repository $repository
     *
     * @return ApiFoundation
     */
    protected function setRepository( Repository $repository ): ApiFoundation
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * @param DefaultTransformer $transformer
     *
     * @return ApiFoundation
     */
    protected function setTransformer( DefaultTransformer $transformer ): ApiFoundation
    {
        $this->transformer = $transformer;

        return $this;
    }

    /**
     * @param null $resourceName
     *
     * @return ApiFoundation
     */
    protected function setResourceName( $resourceName )
    {
        $this->resourceName = $resourceName;

        return $this;
    }

    /**
     * @return array
     */
    public function index()
    {
        $rows = $this->repository->getAllPaginate(request('rows', 15), request('order_by'), request('direction', 'asc'), explode(',', request('columns', '*')), request('page_name', 'page'));

        return $this->collection($rows);
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function filter( Request $request )
    {
        $rows = $this->repository->filterPaginated($request, request('rows', 15), request('order_by'), request('direction', 'asc'), explode(',', request('columns', '*')), request('page_name', 'page'), request('search'));

        return $this->collection($rows);
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function store( Request $request )
    {
        $model = $this->repository->add($request->all());

        return $this->item($model);
    }

    /**
     * @param $model
     *
     * @return array
     */
    public function show( $model )
    {
        return $this->item($model);
    }

    /**
     * @param Request $request
     * @param $model
     *
     * @return array
     */
    public function update( Request $request, $model )
    {
        $model = $this->repository->update($request->all(), $model);

        return $this->item($model);
    }

    /**
     * @param $model
     *
     * @return array
     */
    public function destroy( $model )
    {
        $this->repository->destroy($model);

        return $this->item($model);
    }

    /**
     * @param $model
     *
     * @return array
     */
    public function restore( $model )
    {
        $model = $this->repository->restore($model);

        return $this->item($model);
    }

    /**
     * @param $model
     *
     * @return array
     */
    protected function item( $model )
    {
        return fractal()
            ->item($model, $this->transformer, $this->resourceName)
            ->toArray();
    }

    /**
     * @param \Illuminate\Pagination\LengthAwarePaginator $rows
     *
     * @return array
     */
    protected function collection( $rows )
    {
        return fractal()
            ->collection($rows->getCollection(), $this->transformer, $this->resourceName)
            ->serializeWith(new JsonApiSerializer())
            ->paginateWith(new IlluminatePaginatorAdapter($rows))
            ->parseIncludes(request('includes'))
            ->toArray();
    }
}