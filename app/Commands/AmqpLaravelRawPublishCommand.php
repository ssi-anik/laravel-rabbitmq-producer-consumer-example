<?php

namespace App\Commands;

use Anik\Laravel\Amqp\Facades\Amqp;

class AmqpLaravelRawPublishCommand extends AmqpPublishCommand
{
    protected $signature = 'amqp:publish:laravel:raw
                            {--msg= : Message you want to pass}
                            {--rk= : Routing key}
                            {--i : Input values with interaction}
                            ';

    protected $description = 'Publish raw message using anik/laravel-amqp';

    public function handle()
    {
        Amqp::publish($this->getMessage(), $this->getRoutingKey());
    }
}
