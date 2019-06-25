<?php

namespace Makeable\ApiEndpoints\Tests;

use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Makeable\ApiEndpoints\Throttling;

class ThrottleConfigTest extends TestCase
{
    /** @test * */
    public function it_lol()
    {
//        echo get_class($this->job());
        $this->assertFalse(true);
    }
//
//    protected function job()
//    {
//        return new class {
//            use InteractsWithQueue, Queueable, SerializesModels, Throttling;
//
//            public function __construct()
//            {
//                $this
//                    ->configure()
//                    ->retryAfterSeconds(5)
//                    ->retryMaxTimes(5);
//            }
//
//            public function handle()
//            {
//                $this->throttle()->run(function () {
//                    throw new \Exception('test');
//                });
//            }
//        };
//    }
}