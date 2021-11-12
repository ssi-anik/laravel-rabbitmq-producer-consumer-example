<?php

namespace App\Commands;

use Anik\Amqp\Exchanges\Exchange;
use Anik\Amqp\Producer;
use Anik\Amqp\Producible;
use Anik\Amqp\ProducibleMessage;
use Faker\Factory;
use PhpAmqpLib\Channel\AbstractChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class AmqpPublishCommand extends BaseAmqpCommand
{
    protected $signature = 'amqp:publish
                            {--msg= : Message you want to pass}
                            {--exchange= : Exchange to publish the message}
                            {--type= : Exchange Type}
                            {--rk= : Routing key}
                            {--dde : Do not declare exchange}
                            {--i : Input values with interaction}
                            {--h : Include header when passing message}
                            {--np : Transient / Non-persistent message}
                            ';

    protected $description = 'Publish message to RabbitMQ using anik/amqp';

    protected function getMessageProperties(): array
    {
        $properties = [
            'delivery_mode' => $this->option('np') ? AMQPMessage::DELIVERY_MODE_NON_PERSISTENT
                : AMQPMessage::DELIVERY_MODE_PERSISTENT,
        ];

        if ($this->option('h')) {
            $headers = [];
            while (true) {
                $key = $this->ask('What is the header key? [Press enter to exit]');
                if (empty($key)) {
                    break;
                }

                $value = $this->ask(sprintf('What is the header value for key (%s)', $key));
                if ($this->confirm(sprintf('Add header key: "%s" and value: "%s"', $key, $value), true)) {
                    $headers[$key] = $value;
                }
            }
            if (!empty($headers)) {
                $properties['application_headers'] = new AMQPTable($headers);
            }
        }

        return $properties;
    }

    protected function getDefaultMessage(): string
    {
        return json_encode(
            [
                'user' => Factory::create()->firstName,
                'event' => rand(1, 100) % 2 === 0 ? 'login' : 'logout',
                'when' => now()->toDateTimeString(),
            ]
        );
    }

    protected function makeMessage(string $msg, array $properties = []): Producible
    {
        return new ProducibleMessage($msg, $properties);
    }

    protected function getMessage(): string
    {
        if ($this->isInteractive()) {
            return $this->keepAskingQuestion('What message do you want to pass?');
        } else {
            return $this->option('msg') ?? $this->getDefaultMessage();
        }
    }

    protected function getProducer(?AbstractConnection $connection = null, ?AbstractChannel $channel = null): Producer
    {
        return new Producer($connection ?? $this->getAmqpConnection(), $channel);
    }

    final protected function getDefaultRoutingKey(): string
    {
        return '';
    }

    protected function getRoutingKey(): string
    {
        if ($this->isInteractive()) {
            $answer = $this->keepAskingQuestion('What is the routing key?', true);

            return trim($answer);
        }

        return $this->option('rk') ?? $this->getDefaultRoutingKey();
    }

    protected function prepare(): array
    {
        return [
            'routing_key' => $this->getRoutingKey(),
            'properties' => ($properties = $this->getMessageProperties()),
            'message' => $this->makeMessage($this->getMessage(), $properties),
            'exchange' => $this->getExchange(),
        ];
    }

    protected function publishMessageToRabbitMQ(
        Producible $message,
        string $routingKey = '',
        ?Exchange $exchange = null,
        array $options = []
    ): bool {
        return $this->getProducer()->publish($message, $routingKey, $exchange, $options);
    }

    public function handle()
    {
        [
            'routing_key' => $routingKey,
            'message' => $message,
            'exchange' => $exchange,
            'properties' => $properties,
        ] = $this->prepare();

        $this->publishMessageToRabbitMQ($message, $routingKey, $exchange);

        $headers = ($properties['application_headers'] ?? new AMQPTable([]))->getNativeData();

        $table = [
            'exchange.name' => $exchange->getName(),
            'exchange.type' => $exchange->getType(),
            'declared' => $exchange->shouldDeclare() ? 'Yes' : 'No',
            'routing.key' => $routingKey,
            'properties' => implode(
                ', ',
                array_map(
                    function ($key, $value) {
                        return sprintf('%s=%s', $key, $value);
                    },
                    array_keys($headers),
                    array_values($headers)
                )
            ),
            'message' => $message->getMessage(),
        ];

        $this->output->table(['Exchange', 'Type', 'Declared', 'Routing key', 'Headers', 'Message',], [$table]);
    }
}
