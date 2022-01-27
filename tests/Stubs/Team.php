<?php

namespace Makeable\ApiEndpoints\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Makeable\ApiEndpoints\Tests\Factories\TeamFactory;
use Makeable\LaravelFactory\Factory;
use Makeable\LaravelFactory\HasFactory;

class Team extends Model
{
    use HasFactory;

    public static function newFactory(): Factory
    {
        return TeamFactory::new();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
