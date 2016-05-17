<?php

use App\Console\Commands\GenerateResource;
use Illuminate\Console\Command;
use Symfony\Component\Console\Tester\CommandTester;

class ResourceGeneratorTest extends TestCase
{
    /** @test */
    public function it_generates_a_new_model()
    {
        // When I run php artisan make:resource Animal
        $this->artisan('make:resource', ['name' => 'Animal']);

        // Then I see a new Model has been created in the App namespace
        // And it has the correct name
        $this->seeFileWasCreated(app_path('/Animal.php'));

        // Clear files created in the test
        $this->removeCreatedFile(app_path('Animal.php'));
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
    }

}
