# Laravel artisan make:resource command

[![Build Status](https://travis-ci.org/drawmyattention/laravel-make-resource.svg?branch=master)](https://travis-ci.org/drawmyattention/laravel-make-resource) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/drawmyattention/laravel-make-resource/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/drawmyattention/laravel-make-resource/?branch=master) [![codecov](https://codecov.io/gh/drawmyattention/laravel-make-resource/branch/master/graph/badge.svg)](https://codecov.io/gh/drawmyattention/laravel-make-resource)
[![License](http://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](http://www.opensource.org/licenses/MIT)

This package adds the ```php artisan make:resource command```, allowing 
you to:

> Generate a model, set its attributes, create a migration, controller, 
routes and model factory in a single easy to use command.

This package serves as a way of very quickly getting an idea off the 
ground, reducing the time you need to spend setting up various parts 
of your application so that you can concentrate on the complexity.

## Why use this package?

When starting a new project, typically we'll begin by creating a new
model, and then going into that model and defining its fillable attributes.
Next, we'll set up a migration, and again define which columns the table 
should hold. 

Next we generate a controller, and add methods for ```index```, 
```show```, ```edit```, ```update```, ```create```, and ```store``` and 
finally open up the routes.php file to set up endpoints that relate to the 
methods in the controller.

If you practice test-driven development, or write automated tests, you'll
then need to create a model factory and again define the same attributes.

I found myself going through the same long winded process time and time again,
so decided to build a single command which can:

* Create a model
* Set its fillable and hidden attributes
* Generate a migration, with column definitions based on the model
* Build a restful controller, with the model imported
* Add the corresponding restful routes namespaced under the model name
* A model factory, with the same attributes and sensible faker dummy data 

## Installation

Install MakeResource through Composer.

    "require": {
        "drawmyattention/laravel-make-resource": "~1.0"
    }
    
Next, update your ```config/app.php``` to add the included service provider
to your ```providers``` array:

    'providers' => [
        // other providers
        DrawMyAttention\ResourceGenerator\ResourceGeneratorServiceProvider::class,
    ];


And you're good to go.

## Using the generator

From the command line, run: 

    php artisan make:resource ModelName "attributes"

For the simplest example, let's create a new ```Animal``` resource:

    php artisan make:resource Animal
    
This will create the following:

* app\Animal.php
* app\Http\Controllers\AnimalController.php
* database\migrations\2016_05_19_090000_create_animals_table.php

as well as appending to:

* app\Http\routes.php
* database\factories\ModelFactory.php

## Defining model attributes

It's also possible to provide a pipe-separated list of attributes 
for the model. For example:

    php artisan make:resource Animal "name:string,fillable,100,index|legs:integer,fillable,unsigned|colour|owner:hidden"

The convention to use when passing arguments is, simply a pipe 
separated list: 

> [attribute name]:[comma separated properties]

The order of the properties is *not* important.

If you specify either ```fillable``` or ```hidden```, the property will 
be set accordingly. If neither is provided, the property is not added to 
either.

Take a look at the ```colour``` and ```owner``` properties from the 
example. No data type was provided, so these are automatically cast to
a string type.

## Example Animal model

    <?php

    namespace App;

    use Illuminate\Database\Eloquent\Model;

    class Animal extends Model
    {
        /**
         * The attributes that are used for migration generation.
         *
         * @array
         */
        protected $migrationAttributes = [
            ['name' => 'name', 'properties' => ['string', 'fillable', '100', 'index']],
            ['name' => 'legs', 'properties' => ['integer', 'fillable', 'unsigned']],
            ['name' => 'colour', 'properties' => []],
            ['name' => 'owner', 'properties' => ['hidden']]
        ];

        /**
         * The attributes that are mass assignable.
         *
         * @var array
         */
        protected $fillable = ['name', 'legs'];

        /**
         * The attributes that should be hidden for arrays.
         *
         * @var array
         */
        protected $hidden = ['owner'];

        /**
         * Return the attributes used to generate a migration.
         *
         * @return array
         */
        public function migrationAttributes()
        {
            return $this->migrationAttributes;
        }
    }

The model has the fillable and hidden properties assigned according 
to the input parameters. A new ```migrationAttributes``` array, and 
getter are added, which are used for generating the migration.

## Example create_animals_table.php migration

    <?php

    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Database\Migrations\Migration;

    class CreateAnimalsTable extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
        {
            Schema::create('animals', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 100)->index();
                $table->integer('legs')->unsigned();
                $table->string('colour');
                $table->string('owner');
                $table->timestamps();
            });
        }

        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
        {
            Schema::drop('animals');
        }
    }

It's important to remember that the ```make:resource``` command is 
really useful for rapidly scaffolding an application, and while it 
gets you started, it's wise to check that the migration is exactly
as you want it. For example, you may wish to add more complex 
indexes, which cannot be added directly from the generator.

## Example AnimalController.php

    <?php

    namespace App\Http\Controllers;

    use App\Animal;
    use App\Http\Requests;
    use Illuminate\Http\Request;

    class AnimalController extends Controller
    {
        /**
         * @var Animal
         */
        private $animal;

        /**
         * @param Animal $animal
         */
        public function __construct(Animal $animal)
        {
            $this->animal = $animal;
        }

        /**
         * Return all Animals.
         *
         * @return mixed
         */
        public function index()
        {
            return $animals = $this->animal->paginate();

            // return view('animal.index', compact('animals'));
        }

        /**
         * Display a given Animal.
         *
         * @param int $id Animal identifier
         * @return mixed
         */
        public function show($id)
        {
            return $animal = $this->animal->findOrFail($id);

            // return view('animal.show', compact('animal'));
        }

        /**
         * Display the form to edit an existing Animal instance.
         *
         * @param int $id Animal identifier
         */
        public function edit($id)
        {
            $animal = $this->animal->findOrFail($id);

            // return view('animal.edit', compact('animal'));
        }

        /**
         * Update an existing Animal instance.
         *
         * @param Request $request
         */
        public function update(Request $request)
        {
            //
        }

        /**
         * Display the form to create a new Animal.
         */
        public function create()
        {
            // return view('animal.create');
        }

        /**
         * Store a new Animal.
         *
         * @param Request $request
         */
        public function store(Request $request)
        {
            // $created = $this->animal->create($request->all());

            // return redirect()->route('animal.show')->with(['id' => $created->id]);
        }

    }

The generated has the core restful methods defined, which some 
of the basic logic implemented to fetch and display resources.

A future update will also scaffold some basic views.

The controller wouldn't be any good without some routes, so 
let's take a look at what was generated next.

## app\Http\routes.php amendments
 
    <?php
    
    // Your existing routes remain here
    
    /*
    |--------------------------------------------------------------------------
    | Animal Routes
    |--------------------------------------------------------------------------
    |
    | Here are all routes relating to the Animal model. A restful routing naming
    | convention has been used, to allow index, show, edit, update, create and
    | store actions on the Animal model.
    |
    */
    
    Route::group(['prefix' => 'animal'], function () {
    
        Route::get('/',         ['as' => 'animal.index',    'uses' => 'AnimalController@index']);
        Route::get('/{id}',     ['as' => 'animal.show',     'uses' => 'AnimalController@show']);
        Route::get('/{id}/edit',['as' => 'animal.edit',     'uses' => 'AnimalController@edit']);
        Route::post('/update',  ['as' => 'animal.update',   'uses' => 'AnimalController@update']);
        Route::get('/create',   ['as' => 'animal.create',   'uses' => 'AnimalController@create']);
        Route::get('/store',    ['as' => 'animal.store',    'uses' => 'AnimalController@store']);
    
    });

A nice clean set of routes is generated, which maps nicely with the 
controller that was generated.

## database\factories\ModelFactory.php

    <?php
    
    // Your existing model factories remain here
    
    // Animal model factory
    
    $factory->define(App\Animal::class, function (Faker\Generator $faker) {
        return [
            'name' => $faker->words(2, true),
            'legs' => $faker->randomNumber(),
            'colour' => $faker->words(2, true),
            'owner' => $faker->words(2, true),
        ];
    });
    
It's quite tedious to have to define the same attributes in 
various places, so rather conveniently, the model factory is
generated automatically, the corresponding faker data is added 
to each property. Nice!
 
## Limitations
 
Currently, the package assumes your application lives in the ```App```
namespace, though a future update will make this more flexible.

The way that Faker association in model factories is implemented, 
is not really optimal - but it's a good starting point. Feel free to 
fork and submit a PR. 
 
## Running tests 

A full test suite is included. To execute the tests, from the 
package directory:

    vendor/bin/phpunit tests/ResourceGeneratorTest.php

## Contributing

If you find a bug, or have ideas for an improvement, please submit a 
pull-request, backed by the relevant unit tests.