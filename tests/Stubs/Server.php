<?php

namespace Makeable\ApiEndpoints\Tests\Stubs;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Makeable\ApiEndpoints\Tests\Factories\ServerFactory;
use Makeable\LaravelFactory\Factory;
use Makeable\LaravelFactory\HasFactory;

class Server extends Model
{
    use HasFactory;

    public static function newFactory(): Factory
    {
        return ServerFactory::new();
    }

    /**
     * @var array
     */
    protected $casts = [
        'is_favorite' => 'bool',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function databases()
    {
        return $this->hasMany(Database::class);
    }

    /**
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeFavorite($query)
    {
        return $query->where('is_favorite', 1);
    }
}
