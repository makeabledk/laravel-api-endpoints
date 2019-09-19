<?php


namespace Makeable\ApiEndpoints\Tests\Stubs;

class Firewall extends Network
{
    public function network()
    {
        return $this->belongsTo(Network::class);
    }
}
