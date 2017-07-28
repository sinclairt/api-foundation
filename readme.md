# API Foundation

A simple trait to build RESTful controllers using Fractal. 

### Installation

Register the service provider in `config/app.php` providers array:
``` Sinclair\ApiFoundation\Providers\ApiFoundationServiceProvider::class```

### Usage
Use the `\Sinclair\ApiFoundation\Traits\ApiFoundation` trait inside your API controllers to create RESTful controllers.

This is written as a trait to prevent inheritance issues, so feel free to overwrite any of the methods.

ApiFoundation ships with a default transformer, which turns the Eloquent model to an array, and with an overload `__call` method to handle includes for additional relationships.

You will need to `__construct` the controller to inject a resource implementation of `Sinclair\Repository\Contracts\Repository` and the transformer you would like to use. You can optionally set the resource name for Fractal to use.

There are fluent setters for the transformer, resource name, and repository should you need them.

##### Available Methods
* index _GET_ _paginated collection_
* filter _POST_ _paginated collection_
* store _POST_ _item_
* show _GET_ _item_
* update _PUT/PATCH_ _item_
* destroy _DELETE_ _item_
* restore _GET_ _item_

##### Using Laravel/Eloquent to take the load off!
 
It is recommended to use the properties that Eloquent provides to reduce the code in the Transformers. 
* Use the `$hidden`/`$visible` properties to control what is seen inside an array.
* Use the `$casts` property to control the type the fields are cast to for arrays. See <a href="https://laravel.com/docs/5.2/eloquent-mutators#attribute-casting">here</a> for more detail. 
* Use the `$with` property to control which relations are eager loaded by default.

```
class User extends Model
{
    protected $fillable = ['name', 'email', 'password', 'api_token', 'is_admin'];
    
    protected $hidden = ['password'];
    // or
    protected $visible = ['name', 'email', 'api_token', 'is_admin'];
    
    protected $casts = [
        'name'      => 'string',
        'posts'     => 'collection',
        'is_admin'  => 'boolean'
    ];
    
    protected $with = [
        'posts'
    ];
    
    public function posts()
    {
        return $this->hasMany(App\Posts::class);
    }
}
```


It would be sensible to add the token auth driver to the api middleware group in `App\Http\Kernel.php`:
```
'api' => [
            'throttle:60,1',
            'auth:token'
        ],
```
But you are free to use your own authorisation driver.
 
I strongly recommend setting up an API route group such as:
```
Route::group(['middleware' => 'api', 'namespace' => 'Api'], function()
{
    Route::get('api/v1/user/{user}/restore', [
        'as'   => 'api.v1.user.restore',
        'uses' => 'UserController@restore'
    ]);
    
    Route::post('api/v1/user/{user}/filter', [
        'as'   => 'api.v1.user.filter',
        'uses' => 'UserController@filter'
    ]);
    
    Route::resource('/api/v1/user', 'UserController', ['except' => ['create', 'edit']]);
}):
```

Use form requests for each resource so you're validation is abstracted away from the controller as well. I'd expect you to over write the `store` and `update` methods, so you can inject your form requests.

Finally, let's use Route Model Binding, here's a quick script I use for this in `App\Providers\RouteServiceProvider`:
```
protected $bindings = [
    'user'
];

public function boot( Router $router )
{
    foreach ( $this->bindings as $model )
    {
        $router->bind(strtolower($model), function ( $value ) use ( $model )
        {
            $model = app(studly_case($model));

            $model = in_array(SoftDeletes::class, class_uses($model)) ? $model->withTrashed()
                                                                              ->find($value) : $model->find($value);

            // the abort is optional
            if ( is_null($model) )
                abort(403);

            return $model;
        });
    }

    parent::boot($router);
}
``` 
