<?php

namespace App\Console\Commands;

use App\Jobs\DeleteUserInformations as JobDeleteUserInformations;
use Illuminate\Console\Command;

class DeleteUserInformations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mpa:deluserinfos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete user informations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        JobDeleteUserInformations::dispatch();
    }
}
