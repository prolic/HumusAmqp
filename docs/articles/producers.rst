.. _producers:

HumusAmqp Producers
===================

What are producers?
~~~~~~~~~~~~~~~~~~~

The concept of producers has nothing to do with the AMQP protocol itself. It's a feature added in
HumusAmqp to allow sending messages with default attributes in a very comfortable way.

Concept of producers
~~~~~~~~~~~~~~~~~~~~

The producer wraps the exchange and corresponding channel and allows to set default attributes and parameters
for all messages sent.

Producer types
--------------

There are two built-in exchange types in HumusAmqp

-  JSON
-  Plain

The JSON Producer takes a message as first argument and json encodes it for you. Additional the following
attributes are added by default:

.. code-block:: php

    [
        'content_type' => 'application/json',
        'content_encoding' => 'UTF-8',
        'delivery_mode' => 2,
    ]

This means all messages are persisted by the broker (delivery mode) and the content-type and content-encoding
is already set for you.

The plain producer takes a message as first arguments and does nothing with it (so it should be a string or
at least it should be possible to cast it to string, like integer and float). Additional the following
attributes are added by default:

.. code-block:: php

    [
        'content_type' => 'text/plain',
        'content_encoding' => 'UTF-8',
        'delivery_mode' => 2,
    ]

Creating a producer
-------------------

.. code-block:: php

    <?php

    $connection = new \Humus\Amqp\Driver\AmqpExtension\Connection();
    $connection->connect();

    $channel = $connection->newChannel();

    $exchange = $channel->newExchange();
    $exchange->setName('my-exchange');

    $producer = new \Humus\Amqp\JsonProducer($exchange);

Using custom default headers
----------------------------

.. code-block:: php

    <?php

    $producer = new \Humus\Amqp\JsonProducer($exchange, [
        'content_type' => 'application/json',
        'content_encoding' => 'UTF-8',
        'delivery_mode' => 2 // persistent,
        'app_id' => 'DemoApplication',
        'expiration' => 10000,
    ]);

This will additionally add the app_id and message expiration attributes.

Using configuration and factory
-------------------------------

.. code-block:: php

    <?php

    return [
        'dependencies' => [
            'factories' => [
                Driver::class => \Humus\Amqp\Container\DriverFactory::class,
                'default-amqp-connection' => [\Humus\Amqp\Container\ConnectionFactory::class, 'default'],
                'my-producer' => [\Humus\Amqp\Container\ProducerFactory::class, 'my-producer'],
            ],
        ],
        'humus' => [
            'amqp' => [
                'driver' => 'php-amqplib',
                'connection' => [
                    'default' => [
                        'type' => 'socket',
                        'host' => 'localhost',
                        'port' => 5672,
                        'login' => 'guest',
                        'password' => 'guest',
                        'vhost' => '/',
                        'persistent' => false,
                        'read_timeout' => 3, //sec, float allowed
                        'write_timeout' => 1, //sec, float allowed
                    ],
                ],
                'exchange' => [
                    'my-exchange' => [
                        'name' => 'my-exchange',
                        'type' => 'direct',
                        'connection' => 'default-amqp-connection',
                        'auto_setup_fabric' => true,
                    ],
                ],
                'producer' => [
                    'my-producer' => [
                        'type' => 'json',
                        'exchange' => 'my-exchange',
                    ],
                ],
            ],
        ],
    ];

    $producer = $container->get('my-producer');

Publishing messages
-------------------

.. code-block:: php

    <?php

    $exchange->publish(
        'some message',
        'routing_key',
        Constants::AMQP_NOPARAM,
        [
            'arguments' => [
                'arg1' => 'value'
            ],
        ]
    );


Publishing messages
~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php

    $producer->publish('my message', 'routing_key');

Publishing messages transactional
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php

    $producer->startTransaction();

    $producer->publish('my message', 'routing_key');

    $producer->commitTransaction();

Publishing messages with confirm select
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php

    $producer->confirmSelect();

    $producer->setConfirmCallback(
        function (
            int $deliveryTag,
            bool $multiple = false
        ) use (&$cnt, &$result): bool {
            $result[] = 'Message acked';
            $result[] = func_get_args();
            return --$cnt > 0;
        },
        function (
            int $deliveryTag,
            bool $multiple,
            bool $requeue
        ) use (&$result): bool {
            $result[] = 'Message nacked';
            $result[] = func_get_args();
            return false;
        }
    );

    $producer->publish('my message', 'routing_key');

    $producer->waitForConfirm();

    var_dump($result);

Publishing messages as mandatory
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: php

    <?php

    $producer->setReturnCallback(
        function (
            int $replyCode,
            string $replyText,
            string $exchange,
            string $routingKey,
            Envelope $envelope,
            string $body
        ): void {
            throw new \RuntimeException('Message returned: ' . $replyText);
        }
    );

    $producer->publish(
        'my message',
        'routing_key',
        Constants::AMQP_MANDATORY
    );

    $producer->waitForBasicReturn();

Wrapping Up
-----------

Using a producer simplifies the client code when working with exchanges a lot by adding your needed
default message attributes. Use them whenever possible instead of handling with the exchange directly.

What to Read Next
-----------------

The documentation is organized as :ref:`a number of guides <guides>`, covering various topics.

We recommend that you read the following guides first, if possible, in
this order:

-  :ref:`Queues and Consumers <queues>`
-  :ref:`Bindings <bindings>`
-  :ref:`Consumers <consumers>`
-  :ref:`CLI <cli>`
-  :ref:`Durability and Related Matters <durability>`
-  :ref:`JSON RPC Server & Client <rpc>`
-  :ref:`RabbitMQ Extensions to AMQP 0.9.1 <extensions>`
-  :ref:`Error Handling and Recovery <error_handling>`
-  :ref:`Troubleshooting <troubleshooting>`
-  :ref:`Deployment <deployment>`

Tell Us What You Think!
-----------------------

Please take a moment to tell us what you think about this guide: `Send an e-mail <saschaprolic@googlemail.com>`_,
say hello in the `HumusAmqp gitter <https://gitter.im/prolic/HumusAmqp>`_ chat.
or raise an issue on `Github <https://www.github.com/prolic/HumusAmqp/issues>`_.

Let us know what was unclear or what has not been covered. Maybe you
do not like the guide style or grammar or discover spelling
mistakes. Reader feedback is key to making the documentation better.
