<?php

namespace Nadi\Laravel\Console\Commands;

use Illuminate\Console\Command;

class VerifyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nadi:verify';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify Nadi Configuration for the Application.';

    public function handle()
    {
        $this->info('Application Verification Status: '.(
            app('nadi')->verify() ? 'OK' : 'Failed')
        );
    }
}
