<?php

namespace Witify\LaravelCart\Tests\Fixtures;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['id', 'name', 'email', 'password'];
    protected $hidden = ['password', 'remember_token'];
}
