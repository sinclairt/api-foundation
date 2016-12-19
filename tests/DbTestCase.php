<?php

use Illuminate\Filesystem\ClassFinder;
use Illuminate\Filesystem\Filesystem;

require_once __DIR__ . '/../src/Sinclair/ApiFoundation/Providers/ApiFoundationServiceProvider.php';

/**
 * Class DbTestCase
 */
abstract class DbTestCase extends \Illuminate\Foundation\Testing\TestCase
{
    /**
     * @var mixed
     */
    protected $baseUrl;

    /**
     * DbTestCase constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->baseUrl = env('APP_URL');
    }

    /**
     * Creates the application.
     *
     * Needs to be implemented by subclasses.
     *
     * @return \Symfony\Component\HttpKernel\HttpKernelInterface
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../vendor/laravel/laravel/bootstrap/app.php';

        $app->register(\Sinclair\ApiFoundation\Providers\ApiFoundationServiceProvider::class);

        $app->make('Illuminate\Contracts\Console\Kernel')
            ->bootstrap();

        return $app;
    }

    /**
     * Setup DB before each test.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set('database.connections.sqlite.database', ':memory:');

        $this->migrate();
    }

    /**
     * run package database migrations
     *
     * @param string $path
     */
    public function migrate($path = __DIR__ . "/Resource/migrations")
    {
        $fileSystem = new Filesystem;
        $classFinder = new ClassFinder;

        foreach ($fileSystem->files($path) as $file)
        {
            $fileSystem->requireOnce($file);
            $migrationClass = $classFinder->findClass($file);

            (new $migrationClass)->up();
        }
    }
}