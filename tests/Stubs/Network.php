<?php


namespace Makeable\ApiEndpoints\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

class Network extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function firewalls()
    {
        return $this->hasMany(Firewall::class);
    }
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function server()
    {
        return $this->belongsTo(Server::class);
    }
}
