<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;

function date($format)
{
    return 'date';
}

class ResourceGeneratorTest extends \TestCase
{
    /** @test */
    public function it_generates_a_new_model()
    {
        // When I run php artisan make:resource Animal
        $this->artisan('make:resource', ['name' => 'Animal']);

        // Then I see a new Model has been created in the App namespace
        // And it has the correct name
        $this->seeFileWasCreated(app_path('/Animal.php'));

        // Clear up files created in the test
        $this->removeCreatedFile(app_path('Animal.php'));
        $this->removeCreatedFile(database_path('migrations/date_create_animals_table.php'));
        $this->resetRoutes();
    }

    /** @test */
    public function it_does_not_overwrite_an_existing_model()
    {
        // Given there is an existing model called 'Animal'.
        $this->runArtisanCommand(GenerateResource::class, ['name' => 'Animal']);

        // And I try to create another model named 'Animal'.
        $console = $this->runArtisanCommand(GenerateResource::class, ['name' => 'Animal']);

        // Then I see an error
        $this->seeInConsoleOutput('Model already exists!', $console);

        // Clear up files created in the test
        $this->removeCreatedFile(app_path('Animal.php'));
        $this->removeCreatedFile(database_path('migrations/date_create_animals_table.php'));
        $this->resetRoutes();
    }

    /** @test */
    public function it_creates_a_model_with_the_correct_class_name()
    {
        // When I create a new model called 'Animal'.
        $this->runArtisanCommand(GenerateResource::class, ['name' => 'Animal']);

        // Then the model is created and the model has the correct class name
        $this->seeInFile('class Animal', app_path('/Animal.php'));

        // Clear up files created in the test
        $this->removeCreatedFile(app_path('Animal.php'));
        $this->removeCreatedFile(database_path('migrations/date_create_animals_table.php'));
        $this->resetRoutes();
    }

    /** @test */
    public function it_creates_a_model_that_can_be_instantiated()
    {
        $class = 'Animal';

        // When I create a new model called 'Animal'.
        $this->runArtisanCommand(GenerateResource::class, ['name' => $class]);

        // Then I can instantiate the created Animal model
        $class = 'App\\' . $class;
        $this->assertInstanceOf('App\\Animal', new $class);

        // Clear up files created in the test
        $this->removeCreatedFile(app_path('Animal.php'));
        $this->removeCreatedFile(database_path('migrations/date_create_animals_table.php'));
        $this->resetRoutes();
    }

    /** @test */
    public function it_splits_a_pipe_separated_list_of_attributes_to_an_array_of_attributes()
    {
        $command = $this->app->make(GenerateResource::class);
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
        $command = $this->app->make(GenerateResource::class);

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
        $model = $this->generateRandomClassName();

        // When I create a new model called 'Animal'.
        $this->runArtisanCommand(GenerateResource::class, [
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

        // Clear up files created in the test
        $this->removeCreatedFile(app_path($model . '.php'));
        $this->removeCreatedFile(database_path('migrations/date_create_'. strtolower(Str::plural($model)).'_table.php'));
        $this->resetRoutes();
    }

    /** @test */
    public function it_adds_attributes_that_are_tagged_as_fillable_to_the_fillable_attributes_array()
    {
        $model = $this->generateRandomClassName();

        // When I create a new model called 'Animal'.
        $this->runArtisanCommand(GenerateResource::class, [
            'name' => $model,
            'attributes' => 'name:string,100,fillable|age:integer,fillable|colour:string|nickname',
        ]);

        // Then the model is created and the model has the correct namespace
        $instance = $this->createClassInstance($model);

        $this->assertEquals([
            'name', 'age'
        ], $instance->getFillable());

        // Clear up files created in the test
        $this->removeCreatedFile(app_path($model . '.php'));
        $this->removeCreatedFile(database_path('migrations/date_create_'. strtolower(Str::plural($model)).'_table.php'));
        $this->resetRoutes();
    }

    /** @test */
    public function it_adds_attributes_that_are_tagged_as_hidden_to_the_hidden_attributes_array()
    {
        $model = $this->generateRandomClassName();

        // When I create a new model called 'Animal'.
        $this->runArtisanCommand(GenerateResource::class, [
            'name' => $model,
            'attributes' => 'name:string,100,fillable|age:integer,hidden|colour:string,hidden|nickname',
        ]);

        // Then the model is created and the model has the correct namespace
        $instance = $this->createClassInstance($model);

        $this->assertEquals([
            'age', 'colour'
        ], $instance->getHidden());

        // Clear up files created in the test
        $this->removeCreatedFile(app_path($model . '.php'));
        $this->removeCreatedFile(database_path('migrations/date_create_'. strtolower(Str::plural($model)).'_table.php'));
        $this->resetRoutes();
    }

    /** @test */
    public function it_creates_a_migration_based_on_the_generated_model()
    {
        $model = 'Chimp';

        // When I create the following model
        $console = $this->runArtisanCommand(GenerateResource::class, ['name' => $model,]);

        // Then the a migration called yyyy_mm_dd_hhmmss_create_models_table.php is created.
        $expectedFileName = 'date_create_chimps_table.php';

        $this->seeFileWasCreated(
            database_path('/migrations/' . $expectedFileName)
        );

        $this->seeInConsoleOutput('Created migration date_create_chimps_table.php', $console);

        // Clear up files created in the test
        $this->removeCreatedFile(app_path($model . '.php'));
        $this->removeCreatedFile(database_path('/migrations/' . $expectedFileName));
        $this->resetRoutes();
    }

    /** @test */
    public function it_names_the_migration_correctly()
    {
        $model = $this->generateRandomClassName();

        // When I create the following model
        $this->runArtisanCommand(GenerateResource::class, ['name' => $model,]);

        // Then the a migration called yyyy_mm_dd_hhmmss_create_models_table.php is created.
        $migrationFile =
            date('Y_m_d_his') . '_create_' . strtolower($model) . 's_table.php';

        $expectedClassName = 'Create' . Str::plural($model) . 'Table';

        $this->seeInFile(
            'class ' . $expectedClassName . ' extends Migration',
            database_path('migrations/' . $migrationFile)
        );

        // Clear up files created in the test
        $this->removeCreatedFile(app_path($model . '.php'));
        $this->removeCreatedFile(database_path('/migrations/' . $migrationFile));
        $this->resetRoutes();
    }

    /** @test */
    public function it_creates_the_correct_table_in_the_migration()
    {
        // Given there is a model called 'Monkey'.
        $model = 'Monkey';

        // When I generate the migration for the model.
        $this->runArtisanCommand(GenerateResource::class, ['name' => $model]);

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

        $this->removeCreatedFile(app_path('Monkey.php'));
        $this->removeCreatedFile(database_path('/migrations/date_create_monkeys_table.php'));
        $this->resetRoutes();
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
        $columns = $this->app->make(GenerateResource::class)->buildTableColumns($attributes);

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
        $this->runArtisanCommand(GenerateResource::class, [
            'name' => $model,
            'attributes' => 'name:string,100,fillable|age:integer,unsigned,index,hidden|colour:string,nullable,hidden|nickname',
        ]);

        $this->removeCreatedFile(app_path('Badger.php'));
        $this->resetRoutes();

        // Then I see the migration file contains the following:
        $this->seeInFile(
            file_get_contents(base_path('tests/stubs/date_create_badgers_table.stub')),
            database_path('migrations/date_create_badgers_table.php')
        );

        $this->removeCreatedFile(database_path('/migrations/date_create_badgers_table.php'));
    }

    /** @test */
    public function it_creates_a_new_controller_for_the_model()
    {
        // Given I have a model named 'Tiger'
        $model = 'Tiger';

        // When I create a resource for Tiger.
        $this->runArtisanCommand(GenerateResource::class, ['name' => $model]);

        $this->removeCreatedFile(app_path('Tiger.php'));
        $this->removeCreatedFile(database_path('/migrations/date_create_tigers_table.php'));
        $this->resetRoutes();

        // Then I see there is a new controller called TigerController.php
        $this->seeFileWasCreated(app_path('Http/Controllers/TigerController.php'));

        // And it contains the following.
        $this->assertEquals(
            file_get_contents(base_path('tests/stubs/controller.stub')),
            file_get_contents(app_path('Http/Controllers/TigerController.php'))
        );

        $this->removeCreatedFile(app_path('Http/Controllers/TigerController.php'));
    }

    /** @test */
    public function it_adds_routes_to_the_existing_routes_file()
    {
        // Given I have a model named 'Snake'
        $model = 'Snake';

        // When I create a resource for Snake.
        $this->runArtisanCommand(GenerateResource::class, ['name' => $model]);

        // Then the App\Http\routes.php file contains new routes for the model.
        $this->seeInFile(
            file_get_contents(base_path('tests/stubs/routes.stub')),
            app_path('Http/routes.php')
        );

        $this->removeCreatedFile(app_path('Snake.php'));
        $this->removeCreatedFile(database_path('/migrations/date_create_snakes_table.php'));
        $this->resetRoutes();

    }

    /** @test */
    public function it_creates_a_model_factory_for_the_model()
    {
        // Given there is a model called 'Elephant'
        $model = 'Elephant';

        // When I create a resource for Elephant.
        $this->runArtisanCommand(GenerateResource::class, ['name' => $model]);

        // Then a model factory is created for Elephant.
        $this->seeInFile(
            file_get_contents(base_path('tests/stubs/factory.stub')),
            file_get_contents(database_path('factories/ModelFactory.php'))
        );

        $this->removeCreatedFile(app_path('Snake.php'));
        $this->removeCreatedFile(database_path('/migrations/date_create_snakes_table.php'));
        $this->resetRoutes();
    }

    private function resetRoutes()
    {
        file_put_contents(
            app_path('Http/routes.php'),
            file_get_contents(base_path('tests/stubs/routes.stub'))
        );
    }

    private function generateRandomClassName()
    {
        return 'Animal' . uniqid();
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


}
