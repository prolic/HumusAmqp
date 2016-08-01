.. _consumers:

Consumers
=========

HumusAmqp provides a default consumer implementation that suites most use-cases.
If you have a special use-case, you can extend the abstract class or implement the consumer
interface yourself.

Consumer Callbacks
------------------

In order do reduce extending consumer classes and avoid factory duplication, the consumer
expects a delivery callback. This callback get executed every time a new message is
delivered to the consumer. The consumer expects the callback to take 2 arguments (the envelope
and the queue) and returns a delivery result. A very simple callback would look like this:

.. code-block:: php

    <?php

    $callback = function(\Humus\Amqp\Envelope $envelope, \Humus\Amqp\Queue $queue) {
        echo $envelope->getBody();
        return \Humus\Amqp\DeliveryResult::MSG_ACK();
    }

The delivery result will signal the consumer whether it should ack, nack, reject, reject and
requeue or defer the message until the block size is reached or an timeout occurs. So you can
handle blocks of messages (reducing network overhead).

Handling Messages in Batches
----------------------------

If you have collected messages (returned DeliveryResult::MSG_DEFER() in the delivery callback)
and the block size or timeout is reached, the flush callback will get executed. If you did not
specify a flush callback, it will FlushDeferredResult::MSG_ACK() leading to all messages
collected being acknowledged at once. You have the possibility to add a custom flush callback
where you have to take care whether to ack, nack or reject all messages.

Message Acknowledgements & Rejecting
------------------------------------

Consumer applications — applications that receive and process messages - may occasionally fail to
process individual messages, or will just crash. There is also the possibility of network issues
causing problems. This raises a question — "When should the AMQP broker remove messages from queues?"

The AMQP 0.9.1 specification proposes two choices:

-  After broker sends a message to an application (using either
   basic.deliver or basic.get-ok methods).
-  After the application sends back an acknowledgement (using basic.ack
   AMQP method).

The former choice is called the *automatic acknowledgement model*, while the latter is called the
*explicit acknowledgement model*. With the explicit model, the application chooses when it is time
to send an acknowledgement. It can be right after receiving a message, or after persisting it to
a data store before processing, or after fully processing the message (for example, successfully
fetching a Web page, processing and storing it into some persistent data store).

.. note:: Acknowledgements are channel-specific. Applications MUST NOT receive messages on one
    channel and acknowledge them on another.

Logging
-------

The consumer expects you to inject a logger instance (\Psr\Log\LoggerInterface).

Error-Handling
--------------

By default, all errors are logged on the configured logger. If you want to, you can specify your own error
callback that will get executed instead.

QoS — Prefetching messages
--------------------------

For cases when multiple consumers share a queue, it is useful to be able to specify how many messages
each consumer can be sent at once before sending the next acknowledgement. This can be used as a simple
load balancing technique to improve throughput if messages tend to be published in batches. For example,
if a producing application sends messages every minute because of the nature of the work it is doing.

Imagine a website that takes data from social media sources like Twitter or Facebook during the Champions
League (european soccer) final (or the Superbowl), and then calculates how many tweets mentioned a particular
team during the last minute. The site could be structured as 3 applications:

-  A crawler that uses streaming APIs to fetch tweets/statuses, normalizes them and sends them in JSON
   for processing by other applications ("app A").
-  A calculator that detects what team is mentioned in a message, updates statistics and pushes an update
   to the Web UI once a minute ("app B").
-  A Web UI that fans visit to see the stats ("app C").

In this imaginary example, the "tweets per second" rate will vary, but to improve the throughput of the
system and to decrease the maximum number of messages that the AMQP broker has to hold in memory at once,
applications can be designed in such a way that application "app B", the "calculator", receives 5000
messages and then acknowledges them all at once. The broker will not send message 5001 unless it receives an
acknowledgement.

In AMQP 0.9.1 parlance this is known as *QoS* or *message prefetching*.
Prefetching is configured on a per-channel basis.

The default implementation of the HumusAmqp's consumer works with prefetch count, so if you set the prefetch
count to 50, a block size of 50 messages will be used.

Timeouts
--------

The idle timeout takes effect, when there are no more messages coming in and you expect a block size > 1.
The wait timeout applies every time the consumer tries to fetch a message from the queue but doesn't receive any.

Set up the consumer
-------------------

.. code-block:: php

    <?php

    $logger = new \Psr\Log\NullLogger();

    $connection = new \Humus\Amqp\Driver\AmqpExtension\Connection();
    $connection->connect();

    $channel = $connection->newChannel();

    $queue = $channel->newQueue();
    $queue->setName('test-queue');

    $consumer = new \Humus\Amqp\CallbackConsumer(
        $queue,
        $logger,
        12.5, // idle timeout, float in seconds
        function (\Humus\Amqp\Envelope $envelope, \Humus\Amqp\Queue $queue) {
            echo $envelope->getBody();
            return \Humus\Amqp\DeliveryResult::MSG_DEFER();
        },
        function (\Humus\Amqp\Queue $queue) {
            return \Humus\Amqp\FlushDeferredResult::MSG_ACK();
        },
        null, // no custom error callback
        'demo-consumer-tag',
        20 // handle 20 messages or wait for timeout until flush deferred callback is executed
    );

    $consumer->consume(2000); // consume 2000 messages

Set up the consumer using config and factory
--------------------------------------------

.. code-block:: php

    <?php

    // declare callbacks as invokable classes first

    namespace My
    {
        class EchoCallback
        {
            public function __invoke(\Humus\Amqp\Envelope $envelope, \Humus\Amqp\Queue $queue)
            {
                echo $envelope->getBody();
                return \Humus\Amqp\DeliveryResult::MSG_DEFER();
            }
        }

        class FlushDeferredCallback
        {
            public function (\Humus\Amqp\Queue $queue)
            {
                return \Humus\Amqp\FlushDeferredResult::MSG_ACK();
            }
        }
    }

    return [
        'dependencies' => [
            'factories' => [
                Driver::class => Humus\Amqp\Container\DriverFactory::class,
                'default-amqp-connection' => [Humus\Amqp\Container\ConnectionFactory::class, 'default'],
                \My\EchoCallback::class => \Zend\ServiceManager\Factory\InvokableFactory::class,
                \My\FlushDeferredCallback::class => \Zend\ServiceManager\Factory\InvokableFactory::class,
                \Psr\Log\NullLogger => \Zend\ServiceManager\Factory\InvokableFactory::class,
            ],
        ],
        'humus' => [
            'amqp' => [
                'driver' => 'amqp-extension',
                'connection' => [
                    'default' => [
                        'host' => 'localhost',
                        'port' => 5672,
                        'login' => 'guest',
                        'password' => 'guest',
                        'vhost' => '/',
                        'persistent' => false,
                        'read_timeout' => 3,
                        'write_timeout' => 1,
                    ],
                ],
                'queue' => [
                    'my-queue' => [
                    'name' => 'demo-queue',
                    'connection' => 'default-amqp-connection',
                    'exchanges' => [
                        'demo-exchange' => [
                            [
                                'routing_keys => [
                                    'v1.0.*',
                                    'v1.1.0',
                                    'v2.0.0'
                                ],
                            ],
                        ],
                    ],
                ],
                'callback_consumer' => [
                    'demo-consumer' => [
                        'queue' => 'demo-queue',
                        'delivery_callback' => \My\EchoCallback::class,
                        'flush_callback' => \My\FlushDeferredCallback::class,
                        'logger' => \Psr\Log\NullLogger::class,
                        'idle_timeout' => 12.5,
                        'block_size' => 50,
                        'consumer_tag' => 'demo-consumer-tag',
                    ]
                ],
            ],
        ],
    ];

    $consumer = $container->get('demo-consumer');
    $consumer->consume(2000);

Using Multiple Consumers Per Queue
----------------------------------

It is possible to have multiple non-exclusive consumers on queues. In that case, messages will
be distributed between them according to prefetch levels of their channels (more on this later
in this guide). If prefetch values are equal for all consumers, each consumer will get about
the same number of messages.

Starting a consumer from CLI
----------------------------

This requires setting up the consumer via config and container factory.

.. code-block:: bash

    $ ./vendor/bin/humus-amqp consumer -n demo-consumer -a 2000

See: :ref:`cli` for more informations.

Killing a Consumer gracefully
-----------------------------

You can send a SIGUSER1 signal to gracefully shutdown the consumer.

.. code-block:: bash

    kill -10 23453

Where 23453 is the process id of the consumer process.

What to Read Next
-----------------

The documentation is organized as :ref:`a number of guides <guides>`, covering various topics.

We recommend that you read the following guides first, if possible, in
this order:

-  :ref:`CLI <cli>`
-  :ref:`Durability and Related Matters <durability>`
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
