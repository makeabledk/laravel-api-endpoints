<?php

namespace Makeable\ApiEndpoints\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

class Team extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
