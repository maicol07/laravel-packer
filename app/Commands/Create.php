<?php

namespace App\Commands;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use LaravelZero\Framework\Commands\Command;

class Create extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'new
        {package_name? : The name of the package}
        {vendor? : The vendor of the package}
        {author_name? : The name of the author of the package}
        {author_email? : The email of the author of the package}
        {path? : The path where to create the package}
        {keywords? : Comma-separated list of keywords that describes the package}
        {--readme : If set, it will create a README file in the package folder}
        {--composer : If set, it will initialize Composer in the package folder}
        {--phpunit : If set, it will create PHPUnit config and tests in the package folder}
        {--license : If set, it will create a LICENSE file (MIT) in the package folder}
        {--contributing : If set, it will create a CONTRIBUTING file in the package folder}
        {--styleci : If set, it will create StyleCI files in the package folder}
        {--codecov : If set, it will create CodeCov files in the package folder}
        {--gitignore : If set, it will create a README in the package folder}
        {--gitattributes : If set, it will create a .gitattributes file in the package folder}
        {--travis : If set, it will create TravisCI in the package folder}
        {--facade : If set, it will create Laravel facades scaffolding in the package folder}
        {--git : If set, it will initialize Git in the package folder}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Create scaffolding of your package';

    private array $arguments = [
        'package_name' => 'Enter package name',
        'vendor' => 'Enter vendor name',
        'author_name' => 'Enter package author name',
        'author_email' => 'Enter package author email',
        'path' => 'Enter the package folder where it will be created',
        'keywords' => 'Enter package keywords (comma separated)'
    ];

    private array $options = [
        'readme' => 'Do you want to create a README file in the project folder?',
        'composer' => 'Do you want to initialize Composer in the project folder?',
        'phpunit' => 'Do you want to create PHPUnit tests in the project folder?',
        'license' => 'Do you want to create a LICENSE file (MIT) in the project folder?',
        'contributing' => 'Do you want to create a CONTRIBUTING file in the project folder?',
        'styleci' => 'Do you want to create StyleCI files in the project folder?',
        'codecov' => 'Do you want to create CodeCov files in the project folder?',
        'gitignore' => 'Do you want to create a .gitignore file in the project folder?',
        'gitattributes' => 'Do you want to create a.gitattributes file in the project folder?',
        'travis' => 'Do you want to create TravisCI files in the project folder?',
        'facade' => 'Do you want to create Laravel facades scaffolding in the project folder?',
        'git' => 'Do you want to initialize Git in the package folder?'
    ];

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void
    {
        $ask = false;
        if (!$this->argument('package_name') || !$this->argument('vendor')) {
            $ask = true;
        }

        foreach (array_keys($this->arguments) as $argument) {
            $this->setArgument($argument, $ask);
        }
        \cache()->get('package_name');
        foreach (array_keys($this->options) as $option) {
            $this->setOption($option, $ask);
        }
        $this->setPath();

        $this->task('Creating helper files', function () {
            foreach (collect($this->options)->except('git')->keys() as $option) {
                if ($this->option($option)) {
                    $this->callSilent("create:$option");
                }
            }
        });

        $this->task('Creating Service Provider for package', function () {
            $this->callSilent('create:provider');
        });

        if ($this->option('phpunit')) {
            $this->task('Creating tests directory and test files', function () {
                $this->callSilent('create:testcase');
                $this->callSilent('create:featuretest');
                $this->callSilent('create:unittest');
            });
        }

        $this->task('Creating configuration file', function () {
            $this->callSilent('create:config');
        });

        if ($this->option('git')) {
            chdir(Cache::get('package_path'));
            $this->info(shell_exec('git init'));
        }
    }

    protected function setArgument(string $name, bool $ask = false): void
    {
        $argument = $this->argument($name);
        if (!$argument && $ask) {
            $argument = $this->ask($this->arguments[$name]);
        }
        $argument = match ($name) {
            'vendor' => Str::kebab($argument),
            'keywords' => str_replace(', ', ',', $argument),
            default => trim($argument)
        };
        Cache::forever($name, $argument);
    }

    protected function setOption(string $name, bool $ask = false): void
    {
        $option = $this->option($name);
        if (!$option && $ask) {
            $option = $this->confirm($this->options[$name]);
        }
        $this->input->setOption($name, $option);
    }

    protected function setPath(): void
    {
        $path = Cache::get('path');
        if (app()->environment() === 'development' && realpath($path) === getcwd()) {
            $path .= '/package';
        }
        $package_name = Str::studly(Cache::get('package_name'));
        Cache::forever('package_path', "$path/$package_name");
    }
}
