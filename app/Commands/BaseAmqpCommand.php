<?php

namespace App\Commands;

use Anik\Amqp\AmqpConnectionFactory;
use Anik\Amqp\Exchanges\Exchange;
use LaravelZero\Framework\Commands\Command;
use PhpAmqpLib\Connection\AbstractConnection;

abstract class BaseAmqpCommand extends Command
{
    final protected function isInteractive(): bool
    {
        return $this->option('i') ? true : false;
    }

    protected function keepAskingQuestion($question, $allowEmpty = false)
    {
        while (true) {
            $answer = $this->ask($question);
            if ($allowEmpty && empty($answer)) {
                return $answer;
            }

            if ($answer) {
                return $answer;
            }

            $this->output->warning('You must provide a value');
        }
    }

    public function getExchangeType(): string
    {
        if ($this->isInteractive()) {
            $type = $this->keepAskingQuestion('What is the type of the exchange?');
        } elseif (!($type = $this->option('type'))) {
            $type = 'direct';
        }

        return $type;
    }

    protected function getExchangeName(): ?string
    {
        if ($this->isInteractive()) {
            return $this->keepAskingQuestion('What is the exchange name?');
        }

        if (!is_null($name = $this->option('exchange'))) {
            return $name;
        }

        return null;
    }

    protected function getExchange(): Exchange
    {
        $type = $this->getExchangeType();
        $name = $this->getExchangeName() ?? sprintf('example.%s', $type);

        return Exchange::make(
            [
                'name' => $name,
                'type' => $type,
                'declare' => !$this->option('dde'),
            ]
        );
    }

    final protected function getAmqpConnection(): AbstractConnection
    {
        $config = config(sprintf('amqp.connections.%s.connection.hosts.0', config('amqp.default')));

        return AmqpConnectionFactory::make(
            $config['host'],
            $config['port'],
            $config['user'],
            $config['password'],
            $config['vhost']
        );
    }

    abstract public function handle();
}
