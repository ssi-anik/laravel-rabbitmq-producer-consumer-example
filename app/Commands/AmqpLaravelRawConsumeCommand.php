<?php

namespace App\Commands;

use Anik\Laravel\Amqp\Facades\Amqp;

class AmqpLaravelRawConsumeCommand extends AmqpConsumeCommand
{
    protected $signature = 'amqp:consume:laravel:raw
                            {--bk= : Binding key}
                            {--i : Input values with interaction}
                            {--ack : Acknowledge received message}
                            {--reject : Reject received message}
                            {--requeue : Requeue received message}
                            ';

    protected $description = 'Consume raw message using anik/laravel-amqp';

    public function handle()
    {
        Amqp::consume($this->getHandler($this->getActionOnMessageReceived() ?? self::ACK), $this->getBindingKey());
    }
}
