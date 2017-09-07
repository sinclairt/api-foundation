<?php

use Illuminate\Filesystem\Filesystem;

require_once __DIR__ . '/../src/Sinclair/ApiFoundation/Providers/ApiFoundationServiceProvider.php';

/**
 * Class DbTestCase
 */
abstract class DbTestCase extends Laravel\BrowserKitTesting\TestCase
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

        foreach ($fileSystem->files($path) as $file) {
            $fileSystem->requireOnce($file);
            $migrationClass = $this->findClass($file);

            (new $migrationClass)->up();
        }
    }

    protected function findClass($file)
    {
        $fp    = fopen($file, 'r');
        $class = $buffer = '';
        $i     = 0;
        while ( ! $class) {
            if (feof($fp)) {
                break;
            }

            $buffer .= fread($fp, 512);
            $tokens = token_get_all($buffer);

            if (strpos($buffer, '{') === false) {
                continue;
            }

            for (; $i < count($tokens); $i++) {
                if ($tokens[$i][0] === T_CLASS) {
                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        if ($tokens[$j] === '{') {
                            $class = $tokens[$i + 2][1];
                        }
                    }
                }
            }
        }

        return $class;
    }
}