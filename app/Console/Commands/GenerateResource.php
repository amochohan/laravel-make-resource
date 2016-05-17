<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class GenerateResource extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:resource {name : The model name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model, migration, controller and add routes';

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    private $files;

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');

        $modelFilename = ucfirst($name) . '.php';

        if (file_exists(app_path($modelFilename))) {
           $this->error('Model already exists!');
            return false;
        }

        $this->files->put(app_path('/' . $modelFilename), $this->buildClass());

    }

    protected function buildClass()
    {
        return $this->getStub();
    }

    private function getStub()
    {
        return $this->files->get(app_path('/Stubs/model.stub'));
    }

}
