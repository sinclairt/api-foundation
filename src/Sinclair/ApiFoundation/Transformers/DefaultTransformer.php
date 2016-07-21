<?php

namespace Sinclair\ApiFoundation\Transformers;

use Illuminate\Contracts\Support\Arrayable;
use League\Fractal\TransformerAbstract;

/**
 * Class DefaultTransformer
 * @package App\Transformers
 */
class DefaultTransformer extends TransformerAbstract
{
    /**
     * @param $model
     *
     * @return mixed
     */
    public function transform( Arrayable $model )
    {
        return $model->toArray();
    }

    public function __call( $name, $arguments )
    {
        if ( !empty( $arguments ) && starts_with($name, 'include') && method_exists($model = array_first($arguments), $relation = lcfirst(str_replace('include', '', $name))) )
        {
            $relation = $model->load($relation);

            $transformer = array_first(array_filter($arguments, function ( $item )
            {
                return is_subclass_of($item, TransformerAbstract::class);
            }));

            $transformer = $transformer ?: new DefaultTransformer();

            return $this->item($relation, $transformer);
        }
    }
}