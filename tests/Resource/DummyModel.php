<?php

class DummyModel extends \Illuminate\Database\Eloquent\Model
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $fillable = [ 'name' ];

    protected $table = 'dummies';

    protected $dates = ['deleted_at'];

    public $filters = ['name'];

    public function scopeFilterName( $query, $value, $trashed = false )
    {
        return $query->where('name', $value);
    }
}