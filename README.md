Laravel Rabbitmq Producer Consumer Example
===

The application is built with [Laravel zero](https://github.com/laravel-zero/laravel-zero).

This repository holds examples for:

- [anik/amqp](https://github.com/ssi-anik/amqp)
- [anik/laravel-amqp](https://github.com/ssi-anik/laravel-amqp)

## Installation

- Clone the repository.
- Run `composer install` to install project dependencies.
- Copy `.env.example` to `.env`.
- Populate your credentials.

If you are not familiar with `.env` file, `.env` holds credentials for the project. The environments values are referred
in the code. For this project, it should be inside the `config/amqp.php` file.

## Publish to RabbitMQ

`php amqp amqp:publish` or `./amqp amqp:publish` command can be used to publish messages to RabbitMQ.

Class that is responsible for the command is in `app/Commands/AmqpPublishCommand.php`. Go through the file if you need
to dig in.

The command has the following options available. `php amqp amqp:publish --help` should show you list of help.

```text
Description:
  Publish message to RabbitMQ

Usage:
  amqp:publish [options]

Options:
  --msg                  Message you want to pass
  --exchange             Exchange to publish the message
  --type                 Exchange Type
  --rk                   Routing key
  --dde                  Do not declare exchange
  --i                    Input values with interaction
  --h                    Include header when passing message
  --np                   Transient / Non-persistent message
```

- `--msg` optional. If not provided, generates random json string.
- `--exchange` optional. Name of the exchange. Default `example.direct`.
- `--type` optional. Type of the exchange. Default `direct`.
- `--rk` optional. Routing key. Default `''` (empty string).
- `--dde` optional switch. Do not declare exchange. Default `false` meaning tries to declare exchange.
- `--i` optional switch. Default `false`. If used, provide values using interaction.
- `--h` optional switch. If you want to use `headers` type of exchange, you must have to provide headers using this
  flag.
- `--np` optional switch. By default, all the messages to the exchange is **PERSISTENT**. If you want to send **
  Non-Persistent** message, you can use this flag.

## Consume from RabbitMQ

`php amqp amqp:consume` or `./amqp amqp:consume` command can be used to retrieve messages from RabbitMQ.

Class that is responsible for the command is in `app/Commands/AmqpConsumeCommand.php`. Go through the file if you need
to dig in.

The command has the following options available. `php amqp amqp:consume --help` should show you list of help.

```text
Description:
  Consume message from RabbitMQ

Usage:
  amqp:consume [options]

Options:
  --exchange             Exchange to listen
  --type                 Exchange Type
  --queue                Queue to listen
  --bk                   Binding key
  --dde                  Do not declare exchange
  --ddq                  Do not declare queue
  --i                    Input values with interaction
  --ct                   Consumer tag
  --h                    Include header
  --ack                  Acknowledge received message
  --reject               Reject received message
  --requeue              Requeue received message
```

- `--exchange` optional. Name of the exchange. Default `example.direct`.
- `--type` optional. Type of the exchange. Default `direct`.
- `--queue` optional. Name of the queue. Default `example.{type}.queue`.
- `--bk` optional. Binding key. Default `''` (empty string).
- `--dde` optional switch. Do not declare exchange. Default `false` meaning tries to declare exchange.
- `--ddq` optional switch. Do not declare queue. Default `false` meaning tries to declare queue.
- `--i` optional switch. Default `false`. If used, provide values using interaction.
- `--ct` optional. Consumer tag. Default `amqp.consumer.tag`.
- `--h` optional switch. If you want to use `headers` type of exchange, you must have to provide headers using this
  flag.
- `--ack` optional switch. If provided, will acknowledge messages upon receive. **PRIORITY 1**
- `--reject` optional switch. If provided, will reject messages upon receive. **PRIORITY 2**
- `--requeue` optional switch. If provided, will nack messages upon receive. **PRIORITY 3**

---

It's possible to try various combinations with the above commands. Try playing with them.
