# Humus Amqp

PHP 7 Amqp library

[![Build Status](https://travis-ci.org/prolic/HumusAmqp.svg?branch=master)](https://travis-ci.org/prolic/HumusAmqp)
[![Coverage Status](https://coveralls.io/repos/github/prolic/HumusAmqp/badge.svg?branch=master)](https://coveralls.io/github/prolic/HumusAmqp?branch=master)

## Overview

still in development and api is still subject to change

## Installation

You can install prolic/humus-amqp via composer by adding `"prolic/humus-amqp": "dev-master"` as requirement to your composer.json.

## Notes

The ext-amqp driver is the most performant. Benchmarks are added soon. 

When using php-amqplib as driver, it's worth point out, that a StreamConnection (same goes for SSLConnection) does not
have the possibility to timeout. If you want to let the consumer timeout, when no more messages are received, you should
use the SocketConnection instead (assuming you don't need an SSL connection).

When using php-amqplib as driver and you're using the LazyConnection, you should not create the channel yourself, call
instead `$channel = $connection->newChannel()`

## Contribute

Please feel free to fork and extend existing or add new plugins and send a pull request with your changes!
To establish a consistent code quality, please provide unit tests for all your changes and may adapt the documentation.

## License

Released under the [MIT](LICENSE).
