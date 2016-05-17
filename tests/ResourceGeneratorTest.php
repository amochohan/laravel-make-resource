<?php

use Illuminate\Support\Facades\Artisan;

class ResourceGeneratorTest extends TestCase
{
    /** @test */
    public function it_generates_a_new_model()
    {
        // Given I am on the command line

        // When I run php artisan make:resource Animal
        Artisan::call('make:resource', [
            'name' => 'Animal'
        ]);

        // Then I see a new Model has been created in the App namespace
        // And it has the correct name
        $this->assertTrue(file_exists(app_path('Animal.php')));

        // And it has the correct fillable attributes
    }
}
