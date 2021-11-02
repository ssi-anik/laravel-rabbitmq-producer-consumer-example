<?php

namespace App\Commands;

use Anik\Amqp\ConsumableMessage;
use Anik\Amqp\Consumer;
use Anik\Amqp\Queues\Queue;
use PhpAmqpLib\Channel\AbstractChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class AmqpConsumeCommand extends BaseAmqpCommand
{
    private const ACK = 'ack';
    private const REJECT = 'reject';
    private const REQUEUE = 'requeue';

    protected $signature = 'amqp:consume
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

    protected $description = 'Consume message from RabbitMQ';

    final protected function getDefaultBindingKey(): string
    {
        return '';
    }

    protected function getBindingKey(): string
    {
        if ($this->isInteractive()) {
            $answer = $this->keepAskingQuestion('What is the binding key?', true);

            return trim($answer);
        }

        return $this->option('bk') ?? $this->getDefaultBindingKey();
    }

    protected function getConsumerTag(): string
    {
        if ($this->isInteractive()) {
            $answer = $this->keepAskingQuestion('What is the consumer tag?');

            return trim($answer);
        }

        return $this->option('ct') ?? 'amqp.consumer.tag';
    }

    protected function getQueueName(): ?string
    {
        if ($this->isInteractive()) {
            return $this->keepAskingQuestion('What is the queue name?');
        }

        if ($name = $this->option('queue')) {
            return $name;
        }

        return null;
    }

    protected function getQueue(): Queue
    {
        $exchangeType = $this->getExchangeType();
        $name = $this->getQueueName() ?? sprintf('example.%s.queue', $exchangeType);

        return Queue::make(
            [
                'name' => $name,
                'declare' => !$this->option('ddq'),
            ]
        );
    }

    protected function getQos(): ?Qos
    {
        return null;
    }

    protected function getActionOnMessageReceived(): ?string
    {
        if ($this->option('ack')) {
            return self::ACK;
        } elseif ($this->option('reject')) {
            return self::REJECT;
        } elseif ($this->option('requeue')) {
            return self::REQUEUE;
        }

        return null;
    }

    protected function getHeaders(): array
    {
        $headers = [];
        if ($this->option('h')) {
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
        }

        return $headers;
    }

    protected function prepare(): array
    {
        return [
            'binding_key' => $this->getBindingKey(),
            'exchange' => $this->getExchange(),
            'queue' => $this->getQueue(),
            'consumer_tag' => $this->getConsumerTag(),
            'action' => $this->getActionOnMessageReceived(),
            'qos' => $this->getQos(),
            'headers' => $this->getHeaders(),
        ];
    }

    protected function getConsumer(
        ?AbstractConnection $connection = null,
        ?AbstractChannel $channel = null,
        array $options = []
    ): Consumer {
        return new Consumer($connection ?? $this->getAmqpConnection(), $channel, $options);
    }

    public function handle()
    {
        [
            'binding_key' => $bindingKey,
            'exchange' => $exchange,
            'queue' => $queue,
            'consumer_tag' => $consumerTag,
            'action' => $action,
            'qos' => $qos,
            'headers' => $headers,
        ] = $this->prepare();

        $table = [
            'exchange' => sprintf(
                '%s [%s] [%s]',
                $exchange->getName(),
                $exchange->getType(),
                $exchange->shouldDeclare() ? 'Yes' : 'No'
            ),
            'queue' => sprintf('%s [%s]', $queue->getName(), $queue->shouldDeclare() ? 'Yes' : 'No'),
            'consumer_tag' => $consumerTag,
            'binding.key' => $bindingKey,
            'headers' => implode(
                ', ',
                array_map(
                    function ($k, $v) {
                        return sprintf('%s=%s', $k, $v);
                    },
                    array_keys($headers),
                    array_values($headers)
                )
            ),
            'action' => $action ?? 'UNDEFINED',
        ];

        $this->output->table(
            ['Exchange - Type - Declared', 'Queue - Declared', 'Consumer Tag', 'Binding key', 'Headers', 'Action'],
            [$table]
        );

        $consumable = new ConsumableMessage(
            function (ConsumableMessage $message, AMQPMessage $original) use ($action) {
                $this->output->text(
                    sprintf('[%s][Message]: %s', now()->toDateTimeString(), $message->getMessageBody())
                );

                if ($action === self::REJECT) {
                    $message->reject();
                } elseif ($action === self::REQUEUE) {
                    $message->nack();
                } elseif ($action === self::ACK) {
                    $message->ack();
                }
            }
        );

        $this->getConsumer(null, null, ['tag' => $consumerTag])->consume(
            $consumable,
            $bindingKey,
            $exchange,
            $queue,
            $qos,
            ['bind' => ['arguments' => $headers ? new AMQPTable($headers) : []]]
        );
    }
}
