<?php

namespace App\Console\Commands;

use App\Models\Album;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class DeleteUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mpa:deluser {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add user to delete';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $validator = Validator::make(['email' => $this->argument('email')], [
            'email' => 'email',
        ]);
        
        if ($validator->fails()) {
            $errors = $validator->errors();

            $this->warn($errors);

            return;
        }

        $user = User::where('email', $this->argument('email'))->first();

        if ($user) {
            $user->albums()->get()->map(function ($album) {
                $album->deleted_at = Carbon::now();

                $album->save();
            });

            $user->deleted_at = Carbon::now();

            $user->save();

            $this->info('User with email "' . $this->argument('email') . '" will be delete.');
        }
    }
}
