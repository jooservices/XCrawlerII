<?php

namespace Modules\StableDiffusion\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class GenerateText2Image extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'stablediffusion:generate-text2image';

    /**
     * The console command description.
     */
    protected $description = 'Command description.';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
