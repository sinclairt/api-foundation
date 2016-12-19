<?php

namespace Sinclair\ApiFoundation\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use League\Fractal\Serializer\JsonApiSerializer;
use League\Fractal\TransformerAbstract;
use Sinclair\ApiFoundation\Transformers\DefaultTransformer;
use Sinclair\Repository\Contracts\Repository;
use League\Fractal\Manager;

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
     * @param Repository          $repository
     * @param TransformerAbstract $transformer
     * @param null                $resourceName
     */
    public function __construct(Repository $repository, TransformerAbstract $transformer, $resourceName = null)
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
    protected function setRepository(Repository $repository)
    {
        $this->repository = $repository;

        return $this;
    }

    /**
     * @param DefaultTransformer $transformer
     *
     * @return ApiFoundation
     */
    protected function setTransformer(DefaultTransformer $transformer)
    {
        $this->transformer = $transformer;

        return $this;
    }

    /**
     * @param null $resourceName
     *
     * @return ApiFoundation
     */
    protected function setResourceName($resourceName)
    {
        $this->resourceName = $resourceName;

        return $this;
    }

    /**
     * @return array|JsonResponse
     */
    public function index()
    {
        try
        {
            $rows = $this->repository->getAllPaginate(request('rows', 15), request('order_by'), request('direction', 'asc'), explode(',', request('columns', '*')), request('page_name', 'page'));

            return new JsonResponse($this->collection($rows));
        }
        catch (\Exception $exception)
        {
            \Log::info($exception->getTraceAsString());

            return new JsonResponse(['message' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param Request $request
     *
     * @return array|JsonResponse
     */
    public function filter(Request $request)
    {
        try
        {
            $rows = $this->repository->filterPaginated($request, $request->get('rows', 15), $request->get('order_by'), $request->get('direction', 'asc'), explode(',', $request->get('columns', '*')), $request->get('page_name', 'page'), $request->get('search'));

            return new JsonResponse($this->collection($rows));
        }
        catch (\Exception $exception)
        {
            \Log::info($exception->getTraceAsString());

            return new JsonResponse(['message' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param Request $request
     *
     * @return array|JsonResponse
     */
    public function store(Request $request)
    {
        try
        {
            $model = $this->repository->add($request->all());

            return new JsonResponse($this->item($model), JsonResponse::HTTP_CREATED);
        }
        catch (\Exception $exception)
        {
            \Log::info($exception->getTraceAsString());

            return new JsonResponse(['message' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param $model
     *
     * @return array|JsonResponse
     */
    public function show($model)
    {
        try
        {
            return new JsonResponse($this->item($model));
        }
        catch (\Exception $exception)
        {
            \Log::info($exception->getTraceAsString());

            return new JsonResponse(['message' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param Request $request
     * @param         $model
     *
     * @return array|JsonResponse
     */
    public function update(Request $request, $model)
    {
        try
        {
            $model = $this->repository->update($request->all(), $model);

            return new JsonResponse($this->item($model));
        }
        catch (\Exception $exception)
        {
            \Log::info($exception->getTraceAsString());

            return new JsonResponse(['message' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param $model
     *
     * @return array|JsonResponse
     */
    public function destroy($model)
    {
        try
        {
            $this->repository->destroy($model);

            return new JsonResponse($this->item($model));
        }
        catch (\Exception $exception)
        {
            \Log::info($exception->getTraceAsString());

            return new JsonResponse(['message' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param $model
     *
     * @return array|JsonResponse
     */
    public function restore($model)
    {
        try
        {
            $model = $this->repository->restore($model);

            return new JsonResponse($this->item($model));
        }
        catch (\Exception $exception)
        {
            \Log::info($exception->getTraceAsString());

            return new JsonResponse(['message' => $exception->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    /**
     * @param $model
     *
     * @return array
     */
    protected function item($model)
    {
        $this->eagerLoadIncludesForItem($model);

        return fractal()
            ->item($model, $this->transformer, $this->resourceName)
            ->toArray();
    }

    /**
     * @param \Illuminate\Pagination\LengthAwarePaginator $rows
     *
     * @return array
     */
    protected function collection($rows)
    {
        $this->eagerLoadIncludesForCollection($rows);

        return fractal()
            ->collection($rows->getCollection(), $this->transformer, $this->resourceName)
            ->serializeWith(new JsonApiSerializer())
            ->paginateWith(new IlluminatePaginatorAdapter($rows))
            ->parseIncludes(request('includes', []))
            ->parseExcludes(request('excludes', []))
            ->toArray();
    }

    protected function parseIncludes()
    {
        return (new Manager)->parseIncludes(request('includes', []))->getRequestedIncludes();
    }

    /**
     * @param $item
     */
    protected function eagerLoadIncludesForItem(&$item)
    {
        if ($item instanceof Model)
            $item->load($this->parseIncludes());
    }

    /**
     * @param $rows
     */
    protected function eagerLoadIncludesForCollection(&$rows)
    {
        $rows->each(function (&$item)
        {
            $this->eagerLoadIncludesForItem($item);
        });
    }
}