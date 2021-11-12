<?php

namespace App\Commands;

use Anik\Amqp\ConsumableMessage;
use Anik\Amqp\Consumer;
use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Qos\Qos;
use Anik\Amqp\Queues\Queue;
use Anik\Laravel\Amqp\Facades\Amqp;
use PhpAmqpLib\Channel\AbstractChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class AmqpLaravelConsumeCommand extends AmqpConsumeCommand
{
    protected $signature = 'amqp:consume:laravel
                            {--exchange= : Exchange to listen}
                            {--type= : Exchange Type}
                            {--queue= : Queue to listen}
                            {--bk= : Binding key}
                            {--dde : Do not declare exchange}
                            {--ddq : Do not declare queue}
                            {--i : Input values with interaction}
                            {--ct= : Consumer tag}
                            {--h : Include header}
                            {--ack : Acknowledge received message}
                            {--reject : Reject received message}
                            {--requeue : Requeue received message}
                            ';

    protected $description = 'Consume message from RabbitMQ using anik/laravel-amqp';

    protected function consume(
        string $bindingKey,
        string $action,
        string $consumerTag,
        Exchange $exchange,
        Queue $queue,
        ?Qos $qos = null,
        array $headers = []
    ) {
        return Amqp::consume(
            $this->getHandler($action),
            $bindingKey,
            $exchange,
            $queue,
            $qos,
            ['bind' => ['arguments' => $headers ? new AMQPTable($headers) : []]]
        );
    }
}
