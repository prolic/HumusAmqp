# Humus Amqp

PHP 7.4 AMQP library

[![Build Status](https://travis-ci.org/prolic/HumusAmqp.svg?branch=master)](https://travis-ci.org/prolic/HumusAmqp)
[![Coverage Status](https://coveralls.io/repos/github/prolic/HumusAmqp/badge.svg?branch=master)](https://coveralls.io/github/prolic/HumusAmqp?branch=master)
[![Gitter](https://badges.gitter.im/prolic/HumusAmqp.svg)](https://gitter.im/prolic/HumusAmqp?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge)
[![Documentation Status](https://readthedocs.org/projects/humusamqp/badge/?version=latest)](https://readthedocs.org/projects/humusamqp/badge/?version=latest)

## [Documentation](https://humusamqp.readthedocs.io/) powered by Read the Docs.

## Overview

PHP 7.4 AMQP libray supporting multiple drivers and providing full-featured Consumer, Producer, and JSON-RPC Client / Server implementations.

The JSON-RPC part implements [JSON-RPC 2.0 Specification](http://www.jsonrpc.org/specification).

Current supported drivers are: [php-amqp](https://github.com/pdezwart/php-amqp) and [PhpAmqpLib](https://github.com/php-amqplib/php-amqplib).

php-amqp needs at least to be v1.9.3
php-amqplib needs at least to be v2.11.0

This library ships with `container-interop` factories that help you setting up everything.

## Installation

You can install prolic/humus-amqp via composer by adding `"prolic/humus-amqp": "^2.0"` as requirement to your composer.json.

## Usage

### Exchange

    $exchangeName = 'test-exchange';
    $exchange = ExchangeFactory::$exchangeName($container);
    
    $exchange->publish('test-message');


### Queue

    $queueName = 'test-queue';
    $queue = QueueFactory::$queueName($container);
    
    $message = $queue->get();
    $queue->ack($message->getDeliveryTag());

### Producer

    $producerName = 'test-producer';
    $producer = ProducerFactory::$producerName($container);
    
    $producer->confirmSelect();
    $producer->publish(['foo' => 'bar'], 'my-routing-key');
    $producer->waitForConfirm();

### JSON RPC Client

    $clientName = 'my-client';
    $client = JsonRpcClientFactory::$clientName($container);
    $client->addRequest(new JsonRpcRequest('my-server', 'method', ['my' => 'params'], 'id-1'));
    $client->addRequest(new JsonRpcRequest('my-server', 'method', ['my' => 'other_params'], 'id-2'));
    $responseCollection = $client->getResponseCollection();

### JSON RPC Server

    $serverName = 'my-server';
    $server = JsonRpcServerFactory::$serverName($container);
    $server->consume();

## Notes

### Upgrade from 1.x to 1.4

Before: If `auto_setup_fabric` was enabled, the queue and exchange binding was done automatically. The exchanges would also get created. Anonymous queues required to have `auto_setup_fabric` set to true.

New: If `auto_setup_fabric` is enabled or the queue is anonymous (has no name), the queue gets created and binding is done, but no exchanges are created. If you want to enable creating of depending exchanges (including exchange binding), set the new argument `auto_setup_exchanges` to true.

This is a slight BC break, but the old behaviour was so problematic, that I had to make this change either way.

### AMQP-Extension

1) We recommend using php-amqp >=v1.9.3 or compiling it from master, if you encounter any problems with the amqp extension, check
their issue tracker, first. 

The ext-amqp driver is the most performant.


### PhpAmqpLib

1) When using php-amqplib as driver, it's worth point out, that a StreamConnection (same goes for SSLConnection) does not
have the possibility to timeout. If you want to let the consumer timeout, when no more messages are received, you should
use the SocketConnection instead (assuming you don't need an SSL connection).

2) When using php-amqplib as driver and you're using the LazyConnection, you should not create the channel yourself, call
instead `$channel = $connection->newChannel()`

## Support

- File issues at [https://github.com/prolic/HumusAmqp/issues](https://github.com/prolic/HumusAmqp/issues).
- Say hello in the [HumusAmqp gitter](https://gitter.im/prolic/HumusAmqp) chat.

## Contribute

Please feel free to fork and extend existing or add new plugins and send a pull request with your changes!
To establish a consistent code quality, please provide unit tests for all your changes and may adapt the documentation.

## License

Released under the [MIT](LICENSE.txt).
