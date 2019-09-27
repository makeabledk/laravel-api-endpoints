<?php

namespace Makeable\ApiEndpoints\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

class Database extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
