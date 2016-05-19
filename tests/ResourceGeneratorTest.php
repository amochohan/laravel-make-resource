<?php

namespace DrawMyAttention\ResourceGenerator\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use Orchestra\Testbench\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

function app_path($suffix)
{
    return __DIR__ . '/generated/app' . (starts_with($suffix, '/') ? $suffix : '/' . $suffix);
}

function database_path($suffix)
{
    return __DIR__ . '/generated/database' . (starts_with($suffix, '/') ? $suffix : '/' . $suffix);
}
function base_path($suffix)
{
    return __DIR__ . '/../' . (starts_with($suffix, '/') ? $suffix : '/' . $suffix);
}

function date($format)
{
    return 'date';
}

class ResourceGeneratorTest extends TestCase
{
    /**
     * Get package service providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            'DrawMyAttention\ResourceGenerator\ResourceGeneratorServiceProvider',
        ];
    }

    /** @test */
    public function it_generates_a_new_model()
    {
        // When I run php artisan make:resource Lion
        $this->artisan('make:resource', ['name' => 'Lion']);

        // Then I see a new Model has been created in the App namespace
        // And it has the correct name
        $this->seeFileWasCreated(app_path('/Lion.php'));

        $this->clearGeneratedFiles('Lion');
    }

    /** @test */
    public function it_does_not_overwrite_an_existing_model()
    {
        // Given there is an existing model called 'Animal'.
        $this->runArtisanCommand(ResourceMakeCommand::class, ['name' => 'Animal']);

        // And I try to create another model named 'Animal'.
        $console = $this->runArtisanCommand(ResourceMakeCommand::class, ['name' => 'Animal']);

        // Then I see an error
        $this->seeInConsoleOutput('Model already exists!', $console);

        $this->clearGeneratedFiles('Animal');
    }

    /** @test */
    public function it_creates_a_model_with_the_correct_class_name()
    {
        // When I create a new model called 'Animal'.
        $this->runArtisanCommand(ResourceMakeCommand::class, ['name' => 'Animal']);

        // Then the model is created and the model has the correct class name
        $this->seeInFile('class Animal', app_path('/Animal.php'));

        $this->clearGeneratedFiles('Animal');
    }

    /** @test */
    public function it_creates_a_model_that_can_be_instantiated()
    {
        $model = 'Animal';

        // When I create a new model called 'Animal'.
        $this->runArtisanCommand(ResourceMakeCommand::class, ['name' => $model]);

        // Then I can instantiate the created Animal model
        $class = 'App\\' . $model;
        $this->assertInstanceOf('App\\Animal', new $class);

        $this->clearGeneratedFiles($model);
    }

    /** @test */
    public function it_splits_a_pipe_separated_list_of_attributes_to_an_array_of_attributes()
    {
        $command = $this->app->make(ResourceMakeCommand::class);
        $attributes = $command->parseAttributesFromInputString('name:string,100|age:integer,unsigned,index|colour:string,20,nullable|nickname');
        $this->assertEquals([
            'name' => ['string', '100'],
            'age' => ['integer', 'unsigned', 'index'],
            'colour' => ['string', '20', 'nullable'],
            'nickname' => [],
        ], $attributes);

    }

    /** @test */
    public function it_converts_a_php_array_to_a_string_representation_that_can_be_used_to_inject_to_a_template()
    {
        $command = $this->app->make(ResourceMakeCommand::class);

        $string = $command->convertArrayToString(
            $command->parseAttributesFromInputString(
                'name:string,100|nickname'
            )
        );

        $this->assertEquals("[['name' => 'name','properties' => ['string', '100']],['name' => 'nickname','properties' => []]]", $string);

    }

    /** @test */
    public function it_accepts_attributes_which_are_saved_to_the_model()
    {
        $model = 'Koala';

        // When I create a new model called 'Animal'.
        $this->runArtisanCommand(ResourceMakeCommand::class, [
            'name' => $model,
            'attributes' => 'name:string,100|age:integer,unsigned,index|colour:string,20,nullable|nickname',
        ]);

        // Then the model is created and the model has the correct namespace
        $instance = $this->createClassInstance($model);

        $this->assertEquals([
            ['name' => 'name', 'properties' => ['string', '100']],
            ['name' => 'age', 'properties' => ['integer', 'unsigned', 'index']],
            ['name' => 'colour', 'properties' => ['string', '20', 'nullable']],
            ['name' => 'nickname', 'properties' => []],

        ], $instance->migrationAttributes());

        $this->clearGeneratedFiles($model);
    }

    /** @test */
    public function it_adds_attributes_that_are_tagged_as_fillable_to_the_fillable_attributes_array()
    {
        $model = 'Penguin';

        // When I create a new model called 'Animal'.
        $this->runArtisanCommand(ResourceMakeCommand::class, [
            'name' => $model,
            'attributes' => 'name:string,100,fillable|age:integer,fillable|colour:string|nickname',
        ]);

        // Then the model is created and the model has the correct namespace
        $instance = $this->createClassInstance($model);

        $this->assertEquals([
            'name', 'age'
        ], $instance->getFillable());

        $this->clearGeneratedFiles($model);
    }

    /** @test */
    public function it_adds_attributes_that_are_tagged_as_hidden_to_the_hidden_attributes_array()
    {
        $model = 'Dog';

        // When I create a new model called 'Animal'.
        $this->runArtisanCommand(ResourceMakeCommand::class, [
            'name' => $model,
            'attributes' => 'name:string,100,fillable|age:integer,hidden|colour:string,hidden|nickname',
        ]);

        // Then the model is created and the model has the correct namespace
        $instance = $this->createClassInstance($model);

        $this->assertEquals([
            'age', 'colour'
        ], $instance->getHidden());

        $this->clearGeneratedFiles($model);
    }

    /** @test */
    public function it_creates_a_migration_based_on_the_generated_model()
    {
        $model = 'Chimp';

        // When I create the following model
        $console = $this->runArtisanCommand(ResourceMakeCommand::class, ['name' => $model,]);

        // Then the a migration called yyyy_mm_dd_hhmmss_create_models_table.php is created.
        $expectedFileName = 'date_create_chimps_table.php';

        $this->seeFileWasCreated(
            database_path('/migrations/' . $expectedFileName)
        );

        $this->seeInConsoleOutput('Created migration date_create_chimps_table.php', $console);

        $this->clearGeneratedFiles($model);
    }

    /** @test */
    public function it_names_the_migration_correctly()
    {
        $model = 'Hippo';

        // When I create the following model
        $this->runArtisanCommand(ResourceMakeCommand::class, ['name' => $model]);

        // Then the a migration called yyyy_mm_dd_hhmmss_create_models_table.php is created.
        $migrationFile =
            date('Y_m_d_his') . '_create_' . strtolower($model) . 's_table.php';

        $expectedClassName = 'Create' . Str::plural($model) . 'Table';

        $this->seeInFile(
            'class ' . $expectedClassName . ' extends Migration',
            database_path('migrations/' . $migrationFile)
        );

        $this->clearGeneratedFiles($model);
    }

    /** @test */
    public function it_creates_the_correct_table_in_the_migration()
    {
        // Given there is a model called 'Monkey'.
        $model = 'Monkey';

        // When I generate the migration for the model.
        $this->runArtisanCommand(ResourceMakeCommand::class, ['name' => $model]);

        // Then the table name should be 'monkeys'.
        $migrationFile =
            date('Y_m_d_his') . '_create_' . strtolower($model) . 's_table.php';

        $this->seeInFile(
            'Schema::create(\'monkeys\', function (Blueprint $table) {',
            database_path('migrations/' . $migrationFile)
        );

        $this->seeInFile(
            'Schema::drop(\'monkeys\');',
            database_path('migrations/' . $migrationFile)
        );

        $this->clearGeneratedFiles($model);
    }

    /** @test */
    public function it_builds_table_columns_based_on_attributes()
    {
        // Given I have the following attributes
        $attributes = [
            ['name' => 'name', 'properties' => ['string', '100', 'fillable']],
            ['name' => 'age', 'properties' => ['integer', 'unsigned', 'index', 'hidden']],
            ['name' => 'colour', 'properties' => ['string', 'nullable', 'hidden']],
            ['name' => 'nickname', 'properties' => []]
        ];

        // When I convert these to Schema Builder columns
        $columns = $this->app->make(ResourceMakeCommand::class)->buildTableColumns($attributes);

        // Then I see the following
        $this->assertEquals(
            $this->removeWhiteSpace(file_get_contents(base_path('tests/stubs/migration_columns.stub'))),
            $this->removeWhiteSpace($columns)
        );
    }

    /** @test */
    public function it_adds_all_of_the_attributes_that_were_provided_as_columns_in_the_migration()
    {
        // Given I have a model named 'Badger'
        $model = 'Badger';

        // When I create a model with the following parameters:
        $this->runArtisanCommand(ResourceMakeCommand::class, [
            'name' => $model,
            'attributes' => 'name:string,100,fillable|age:integer,unsigned,index,hidden|colour:string,nullable,hidden|nickname',
        ]);

        // Then I see the migration file contains the following:
        $this->seeInFile(
            file_get_contents(base_path('tests/stubs/date_create_badgers_table.stub')),
            database_path('migrations/date_create_badgers_table.php')
        );

        $this->clearGeneratedFiles($model);
    }

    /** @test */
    public function it_creates_a_new_controller_for_the_model()
    {
        // Given I have a model named 'Tiger'
        $model = 'Tiger';

        // When I create a resource for Tiger.
        $this->runArtisanCommand(ResourceMakeCommand::class, ['name' => $model]);

        // Then I see there is a new controller called TigerController.php
        $this->seeFileWasCreated(app_path('Http/Controllers/TigerController.php'));

        // And it contains the following.
        $this->assertEquals(
            file_get_contents(base_path('tests/stubs/controller.stub')),
            file_get_contents(app_path('Http/Controllers/TigerController.php'))
        );

        $this->clearGeneratedFiles($model);
    }

    /** @test */
    public function it_adds_routes_to_the_existing_routes_file()
    {
        // Given I have a model named 'Snake'
        $model = 'Snake';

        // When I create a resource for Snake.
        $this->runArtisanCommand(ResourceMakeCommand::class, ['name' => $model]);

        // Then the App\Http\routes.php file contains new routes for the model.
        $this->seeInFile(
            file_get_contents(base_path('tests/stubs/routes.stub')),
            app_path('Http/routes.php')
        );

        $this->clearGeneratedFiles($model);
    }

    /** @test */
    public function it_creates_a_model_factory()
    {
        // Given there is a model called 'Elephant'
        $model = 'Elephant';

        // When I create a resource for Elephant.
        $this->runArtisanCommand(ResourceMakeCommand::class, [
            'name' => $model,
            'attributes' => 'name:string,100,fillable|age:integer,unsigned,index,hidden|colour:string,nullable,hidden|nickname',
        ]);

        // Then a model factory is created for Elephant.
        $this->seeInFile(
            file_get_contents(base_path('tests/stubs/factory.stub')),
            database_path('factories/ModelFactory.php')
        );

        $this->clearGeneratedFiles($model);
    }

    private function clearGeneratedFiles($class)
    {
        $class = ucfirst($class);

        $this->removeCreatedFile(app_path($class . '.php'));
        $this->removeCreatedFile(app_path('Http/Controllers/' . $class . 'Controller.php'));
        $this->removeCreatedFile(database_path('/migrations/date_create_' . strtolower($class) . 's_table.php'));

        $this->resetRoutes();
        $this->resetModelFactories();
    }

    private function resetRoutes()
    {
        file_put_contents(
            app_path('Http/routes.php'),
            file_get_contents(base_path('tests/stubs/routes.stub'))
        );
    }

    private function resetModelFactories()
    {
        file_put_contents(
            database_path('factories/ModelFactory.php'),
            file_get_contents(base_path('tests/stubs/ModelFactory.stub'))
        );
    }

    /**
     * @param $model
     * @return mixed
     */
    private function createClassInstance($model)
    {
        $class = 'App\\' . $model;
        $instance = new $class;

        return $instance;
    }

    public function seeFileWasCreated($filename)
    {
        $this->assertTrue(file_exists($filename));
    }

    public function removeCreatedFile($filename)
    {
        if (file_exists($filename)) {
            unlink($filename);
        }
    }

    /**
     * @param $class Command class to be called.
     * @param array $parameters
     * @return CommandTester
     */
    public function runArtisanCommand($class, $parameters = [])
    {
        $command = $this->app->make($class);

        $command->setLaravel($this->app->getInstance());

        $commandTester = new CommandTester($command);

        $commandTester->execute($parameters);

        return $commandTester;
    }

    public function seeInConsoleOutput($text, $console)
    {
        $this->assertContains($text, $console->getDisplay());
    }

    public function seeInFile($text, $file)
    {
        $this->assertContains($text, file_get_contents($file), 'The file does not contain ' . $text);
    }

    public function removeWhiteSpace($text)
    {
        return str_replace([' ', "\r", "\n", "\r\n", "\t"], '', $text);
    }

}
