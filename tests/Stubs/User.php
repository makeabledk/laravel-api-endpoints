<?php

namespace Makeable\ApiEndpoints\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Makeable\ApiEndpoints\Tests\Factories\UserFactory;
use Makeable\LaravelFactory\Factory;
use Makeable\LaravelFactory\HasFactory;

class User extends Model
{
    use HasFactory;

    public static function newFactory(): Factory
    {
        return UserFactory::new();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function administrator()
    {
        return $this->belongsTo(self::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function servers()
    {
        return $this->belongsToMany(Server::class);
    }

    public function favoriteServers()
    {
        return $this->belongsToMany(Server::class)->favorite();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function teams()
    {
        return $this->belongsToMany(Team::class);
    }
}
