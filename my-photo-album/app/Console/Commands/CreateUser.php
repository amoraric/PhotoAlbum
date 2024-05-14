<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class CreateUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mpa:adduser {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new user on database';

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

        if (User::where('email', $this->argument('email'))->first()) {
            $this->warn('Oh no ! User with email "' . $this->argument('email') . '" already exists.');

            return;
        }

        $user = new User();
        $password = Str::random();

        $user->email = $this->argument('email');
        $user->password = Hash::make($password);
        $user->name = explode('@', $user->email)[0];
        $user->email_verified_at = Carbon::now();

        $user->save();

        Password::sendResetLink(['email' => $user->email]);

        $this->info('User with email "' . $user->email . '" is created.');
    }
}
