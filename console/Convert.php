<?php namespace Xitara\MediaConverter\Console;

use Illuminate\Console\Command;

// use Symfony\Component\Console\Input\InputOption;
// use Symfony\Component\Console\Input\InputArgument;
use Xitara\MediaConverter\Classes\Convert as ConvertClass;

class Convert extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'mediaconverter:convert';

    /**
     * @var string The console command description.
     */
    protected $description = 'No description provided yet...';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $convert = new ConvertClass;
        $convert->convert();
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }
}
