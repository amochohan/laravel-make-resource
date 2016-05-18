<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Illuminate\Support\Composer;
use Illuminate\Filesystem\Filesystem;

class GenerateResource extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:resource {name : The model name} {attributes?}';

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
     * @var Composer
     */
    private $composer;

    /**
     * @var array The data types that can be created in a migration.
     */
    private $dataTypes = [
        'string', 'integer', 'boolean', 'bigIncrements', 'bigInteger',
        'binary', 'boolean', 'char', 'date', 'dateTime', 'float', 'increments',
        'json', 'jsonb', 'longText', 'mediumInteger', 'mediumText', 'nullableTimestamps',
        'smallInteger', 'tinyInteger', 'softDeletes', 'text', 'time', 'timestamp',
        'timestamps', 'rememberToken',
    ];

    /**
     * @var array $columnProperties Properties that can be applied to a table column.
     */
    private $columnProperties = [
        'unsigned', 'index', 'nullable'
    ];

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     * @param Composer $composer
     */
    public function __construct(Filesystem $files, Composer $composer)
    {
        parent::__construct();

        $this->files = $files;

        $this->composer = $composer;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = trim($this->input->getArgument('name'));

        $this->createModel($name);

        $this->attemptToCreateMigration($name);

        $this->attemptToAddController($name);

        $this->attemptToAddRoutes($name);
    }

    /**
     * Create and store a new Model to the filesystem.
     *
     * @param string $name
     * @return bool
     */
    private function createModel($name)
    {
        $modelName = $this->modelName($name);

        $filename = $modelName . '.php';

        if ($this->files->exists(app_path($filename))) {
            $this->error('Model already exists!');
            return false;
        }

        $model = $this->buildModel($name);

        $this->files->put(app_path('/' . $filename), $model);

        $this->info($modelName . ' Model created');

        return true;
    }

    private function attemptToCreateMigration($name)
    {
        $filename = $this->buildMigrationFilename($name);

        if ($this->files->exists(database_path($filename))) {
            $this->error('Migration already exists!');
            return false;
        }

        $migration = $this->buildMigration($name);

        $this->files->put(
            database_path('/migrations/' . $filename),
            $migration
        );

        if (env('APP_ENV') != 'testing') {
            $this->composer->dumpAutoloads();
        }

        $this->info('Created migration ' . $filename);

        return true;
    }

    private function attemptToAddController($modelName)
    {
        $filename = ucfirst($modelName) . 'Controller.php';

        if ($this->files->exists(app_path('Http/' . $filename))) {
            $this->error('Controller already exists!');
            return false;
        }

        $stub = $this->files->get(app_path('Stubs/controller.stub'));

        $stub = str_replace('MyModelClass', ucfirst($modelName), $stub);
        $stub = str_replace('myModelInstance', Str::camel($modelName), $stub);
        $stub = str_replace('template', strtolower($modelName), $stub);

        $this->files->put(app_path('Http/Controllers/' . $filename), $stub);

        $this->info('Created controller ' . $filename);

        return true;
    }

    private function attemptToAddRoutes($modelName)
    {
        $modelTitle = ucfirst($modelName);

        $modelName = strtolower($modelName);

        $newRoutes = $this->files->get(app_path('Stubs/routes.stub'));

        $newRoutes = str_replace('|MODEL_TITLE|', $modelTitle, $newRoutes);

        $newRoutes = str_replace('|MODEL_NAME|', $modelName, $newRoutes);

        $newRoutes = str_replace('|CONTROLLER_NAME|', $modelTitle . 'Controller', $newRoutes);

        $this->files->append(
            app_path('Http/routes.php'),
            $newRoutes
        );

        $this->info('Added routes for ' . $modelTitle);
    }

    protected function buildMigration($name)
    {
        $stub = $this->files->get(app_path('/Stubs/migration.stub'));

        $className = 'Create' . Str::plural($name). 'Table';

        $stub = str_replace('MIGRATION_CLASS_PLACEHOLDER', $className, $stub);

        $table = strtolower(Str::plural($name));

        $stub = str_replace('TABLE_NAME_PLACEHOLDER', $table, $stub);

        $class = 'App\\' . $name;
        $model = new $class;

        $stub = str_replace('MIGRATION_COLUMNS_PLACEHOLDER', $this->buildTableColumns($model->migrationAttributes()), $stub);

        return $stub;
    }

    protected function buildModel($name)
    {
        $stub = $this->files->get(app_path('/Stubs/model.stub'));

        $stub = $this->replaceClassName($name, $stub);

        $stub = $this->addMigrationAttributes($this->argument('attributes'), $stub);

        $stub = $this->addModelAttributes('fillable', $this->argument('attributes'), $stub);

        $stub = $this->addModelAttributes('hidden', $this->argument('attributes'), $stub);

        return $stub;
    }

    public function convertModelToTableName($model)
    {
        return Str::plural(Str::snake($model));
    }

    public function buildMigrationFilename($model)
    {
        $table = $this->convertModelToTableName($model);

        return date('Y_m_d_his') . '_create_' . $table . '_table.php';
    }

    private function replaceClassName($name, $stub)
    {
        return str_replace('NAME_PLACEHOLDER', $name, $stub);
    }

    private function addMigrationAttributes($text, $stub)
    {
        $attributesAsArray = $this->parseAttributesFromInputString($text);
        $attributesAsText = $this->convertArrayToString($attributesAsArray);

        return str_replace('MIGRATION_ATTRIBUTES_PLACEHOLDER', $attributesAsText, $stub);
    }

    /**
     * Convert a pipe-separated list of attributes to an array.
     *
     * @param string $text The Pipe separated attributes
     * @return array
     */
    public function parseAttributesFromInputString($text)
    {
        $parts = explode('|', $text);

        $attributes = [];

        foreach ($parts as $part) {
            $components = explode(':', $part);
            $attributes[$components[0]] =
                isset($components[1]) ? explode(',', $components[1]) : [];
        }

        return $attributes;

    }

    /**
     * Convert a PHP array into a string version.
     *
     * @param $array
     *
     * @return string
     */
    public function convertArrayToString($array)
    {
        $string = '[';

        foreach ($array as $name => $properties) {
            $string .= '[';
            $string .= "'name' => '" . $name . "',";

            $string .= "'properties' => [";
            foreach ($properties as $property) {
                $string .= "'".$property."', ";
            }
            $string = rtrim($string, ', ');
            $string .= ']';

            $string .= '],';
        }

        $string = rtrim($string, ',');

        $string .= ']';


        return $string;
    }

    public function addModelAttributes($name, $attributes, $stub)
    {
        $attributes = '[' . collect($this->parseAttributesFromInputString($attributes))
            ->filter(function($attribute) use ($name) {
                return in_array($name, $attribute);
            })->map(function ($attributes, $name) {
                return "'" . $name . "'";
            })->values()->implode(', ') . ']';


        return str_replace(strtoupper($name) . '_PLACEHOLDER', $attributes, $stub);
    }

    public function buildTableColumns($attributes)
    {

        return rtrim(collect($attributes)->reduce(function($column, $attribute) {

            $fieldType = $this->getFieldTypeFromProperties($attribute['properties']);

            if ($length = $this->typeCanDefineSize($fieldType)) {
                $length = $this->extractFieldLengthValue($attribute['properties']);
            }

            $properties = $this->extractAttributePropertiesToApply($attribute['properties']);

            return $column . $this->buildSchemaColumn($fieldType, $attribute['name'], $length, $properties);

        }));

    }

    /**
     * Get the column field type based from the properties of the field being created.
     *
     * @param array $properties
     * @return string
     */
    private function getFieldTypeFromProperties($properties)
    {
        $type = array_intersect($properties, $this->dataTypes);

        // If the properties that were given in the command
        // do not explicitly define a data type, or there
        // is no matching data type found, the column
        // should be cast to a string.

        if (! $type) {
            return 'string';
        }

        return $type[0];
    }

    /**
     * Can the data type have it's size controlled within the migration?
     *
     * @param string $type
     * @return bool
     */
    private function typeCanDefineSize($type)
    {
        return $type == 'string' || $type == 'char';
    }

    /**
     * Extract a numeric length value from all properties specified for the attribute.
     *
     * @param array $properties
     * @return int $length
     */
    private function extractFieldLengthValue($properties)
    {
        foreach ($properties as $property) {
            if (is_numeric($property)) {
                return $property;
            }
        }

        return 0;
    }

    /**
     * Get the column properties that should be applied to the column.
     *
     * @param $properties
     * @return array
     */
    private function extractAttributePropertiesToApply($properties)
    {
        return array_intersect($properties, $this->columnProperties);
    }

    /**
     * Create a Schema Builder column.
     *
     * @param string $fieldType The type of column to create
     * @param string $name Name of the column to create
     * @param int $length Field length
     * @param array $traits Additional properties to apply to the column
     * @return string
     */
    private function buildSchemaColumn($fieldType, $name, $length = 0, $traits = [])
    {
        return sprintf("\$table->%s('%s'%s)%s;" . PHP_EOL . '            ',
            $fieldType,
            $name,
            $length > 0 ? ", $length" : '',
            implode('', array_map(function ($trait) {
                return '->' . $trait . '()';
            }, $traits))
        );
    }

    /**
     * Build a Model name from a word.
     *
     * @param string $name
     * @return string
     */
    private function modelName($name)
    {
        return ucfirst($name);
    }

}
