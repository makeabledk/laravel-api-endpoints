<?php

namespace Makeable\ApiEndpoints\Tests\Stubs;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    /**
     * @var array
     */
    protected $casts = [
        'is_favoured' => 'bool',
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
     * @param Builder $query
     * @return Builder
     */
    public function scopeFavoured($query)
    {
        return $query->where('is_favoured', 1);
    }
}
