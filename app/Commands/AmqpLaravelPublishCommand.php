<?php

namespace App\Commands;

use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Producible;
use Anik\Laravel\Amqp\Facades\Amqp;

class AmqpLaravelPublishCommand extends AmqpPublishCommand
{
    protected $signature = 'amqp:publish:laravel
                            {--msg= : Message you want to pass}
                            {--exchange= : Exchange to publish the message}
                            {--type= : Exchange Type}
                            {--rk= : Routing key}
                            {--dde : Do not declare exchange}
                            {--i : Input values with interaction}
                            {--h : Include header when passing message}
                            {--np : Transient / Non-persistent message}
                            ';

    protected $description = 'Publish message to RabbitMQ using anik/laravel-amqp';

    protected function publishMessageToRabbitMQ(
        Producible $message,
        string $routingKey = '',
        ?Exchange $exchange = null,
        array $options = []
    ): bool {
        return Amqp::/*connection('new')->*/ publish(
            $message,
            $routingKey,
            $exchange,
            $options
        );
    }
}
