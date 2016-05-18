<?php

use Illuminate\Console\Command;
use Symfony\Component\Console\Tester\CommandTester;

class TestCase extends Illuminate\Foundation\Testing\TestCase
{
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
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
