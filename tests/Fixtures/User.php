<?php

declare(strict_types=1);

namespace Rembon\LaravelAuditorPulse\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    public $timestamps = false;

    protected $guarded = [];
}
