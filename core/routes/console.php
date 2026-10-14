<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\backend\Role;
use App\Models\backend\User;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('admin:create', function () {
    $name = trim($this->ask('Admin name'));
    $email = strtolower(trim($this->ask('Admin email')));

    if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('A name and valid email address are required.');
        return 1;
    }

    if (User::withTrashed()->where('email', $email)->exists()) {
        $this->error('An account with that email already exists.');
        return 1;
    }

    $password = $this->secret('Password (minimum 8 characters)');
    $confirmation = $this->secret('Confirm password');

    if (strlen($password) < 8 || $password !== $confirmation) {
        $this->error('Passwords must match and contain at least 8 characters.');
        return 1;
    }

    DB::transaction(function () use ($name, $email, $password) {
        $role = Role::withTrashed()->firstOrNew(['key' => 'super_admin']);
        if ($role->exists && $role->trashed()) {
            $role->restore();
        }
        $role->name = 'Super Admin';
        $role->is_super = true;
        $role->save();

        $user = new User();
        $user->name = $name;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->role_id = $role->id;
        $user->status = 1;
        $user->save();
    });

    $this->info('Super-admin account created. Sign in with the email and password you entered.');
    return 0;
})->purpose('Create a super-admin login account');
