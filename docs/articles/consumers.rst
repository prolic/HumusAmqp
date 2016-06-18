.. _consumers:

Consumers
=========

The Humus AMQP Module provides a default consumer implementation that suites most use-cases.
If you have a special use-case, you can extend this class or implement the consumer interface
yourself.

Consumer Callbacks
------------------

In order do reduce extending consumer classes and avoid factory duplication, the consumer
expects a delivery callback. This callback get executed every time a new message gets
delivered to the consumer. The consumer expects the callback to take 3 arguments:
The envelope, the queue and used consumer. A very simple callback would look like this:

.. code-block:: php

    <?php

    $callback = function(AMQPEnvelope $envelope, AMQPQueue $queue, ConsumerInterface $consumer) {
        echo $envelope->getBody();
        return true;
    }

or


.. code-block:: php

    <?php

    class MyCallback
    {
        public function __invoke(AMQPEnvelope $envelope, AMQPQueue $queue, ConsumerInterface $consumer)
        {
            echo $envelope->getBody();
            return true;
        }
    }

The "return true" at the end of the callback will signal the consumer that the message was processed
correctly and the consumer will send an ack. If you return false, the messages will get rejected and
requeued once. When the same message gets rejected the second time, the message will not be requeued
again. If you don't return anything (or return null) the message will get deferred, until the block
size is reache or an timeout occurs. So you can handle blocks of messages.

Another possiblity of returning is to return ConsumerInterace::MSG_ACK, ::MSG_DEFER, ::MSG_REJECT,
or ::MSG_REJECT_REQUEUE.

Handling Messages in Batches
----------------------------

If you have collected messages (returned null or ConsumerIntrace::MSG_DEFER in the delivery callback)
and the block size or timeout is reached, the flush callback will get executed. If you did not specify
a flush callback, it will return true leading to all messages collected being acknowleged at once.
You have the possibility to add a custom flush callback where you have to take care whether or not
you return true or false. Note that any other value than true, will lead to all messages rejected in
the current block.

Message Acknowledgements & Rejecting
------------------------------------

Consumer applications — applications that receive and process messages ‚
may occasionally fail to process individual messages, or will just
crash. There is also the possibility of network issues causing problems.
This raises a question — "When should the AMQP broker remove messages
from queues?"

The AMQP 0.9.1 specification proposes two choices:

-  After broker sends a message to an application (using either
   basic.deliver or basic.get-ok methods).
-  After the application sends back an acknowledgement (using basic.ack
   AMQP method).

The former choice is called the *automatic acknowledgement model*, while
the latter is called the *explicit acknowledgement model*. With the
explicit model, the application chooses when it is time to send an
acknowledgement. It can be right after receiving a message, or after
persisting it to a data store before processing, or after fully
processing the message (for example, successfully fetching a Web page,
processing and storing it into some persistent data store).

.. note:: Acknowledgements are channel-specific. Applications
    MUST NOT receive messages on one channel and acknowledge them on
    another.

Logging
-------

The consumer expects you to inject a logger instance, if you don't provide one, a null-logger will be
created and injected for you.

Error-Handling
--------------

By default, all errors are logged on the configured logger. If you want to, you can specify your own error
callback that will get executed instead.

QoS — Prefetching messages
--------------------------

For cases when multiple consumers share a queue, it is useful to be able
to specify how many messages each consumer can be sent at once before
sending the next acknowledgement. This can be used as a simple load
balancing technique to improve throughput if messages tend to be
published in batches. For example, if a producing application sends
messages every minute because of the nature of the work it is doing.

Imagine a website that takes data from social media sources like Twitter
or Facebook during the Champions League (european soccer) final (or the
Superbowl), and then calculates how many tweets mentioned a particular
team during the last minute. The site could be structured as 3
applications:

-  A crawler that uses streaming APIs to fetch tweets/statuses,
   normalizes them and sends them in JSON for processing by other
   applications ("app A").
-  A calculator that detects what team is mentioned in a message,
   updates statistics and pushes an update to the Web UI once a minute
   ("app B").
-  A Web UI that fans visit to see the stats ("app C").

In this imaginary example, the "tweets per second" rate will vary, but
to improve the throughput of the system and to decrease the maximum
number of messages that the AMQP broker has to hold in memory at once,
applications can be designed in such a way that application "app B", the
"calculator", receives 5000 messages and then acknowledges them all at
once. The broker will not send message 5001 unless it receives an
acknowledgement.

In AMQP 0.9.1 parlance this is known as *QoS* or *message prefetching*.
Prefetching is configured on a per-channel basis.

The default implementation of the Humus AMQP Module's consumer will
work with prefetch count, so if you set the prefetch count to 50, a block
size of 50 messages will be used.

Timeouts
--------

The idle timeout takes effect, when there are no more messages coming in
and you expect a block size > 1. The wait timeout applies every time
the consumer tries to fetch a message from the queue but doesn't receive any.

Set up the consumer
-------------------

.. code-block:: php

    <?php

    return array(
        'humus_amqp_module' => array(
            'consumers' => array(
                'demo-consumer' => array(
                    'queues' => array(
                        'queue1',
                        'queue2'
                    ),
                    'auto_setup_fabric' => true,
                    'callback' => 'echoCallback',
                    'flush_callback' => 'flushCallback',
                    'error_callback' => 'errorCallback',
                    'idle_timeout' => 5.0, // secs
                    'wait_timeout' => 5000, // microsecs
                    'logger' => 'consumer-logger'
                )
            ),
            'plugin_managers' => array(
                'callback' => array(
                    'invokables' => array(
                        'echoCallback' => 'My\Callback\Echo',
                        'flushCallback' => 'My\Callback\Flush',
                        'errorCallback' => 'My\Callback\Error',
                    )
                )
            )
        )
    );

Using Multiple Consumers Per Queue
----------------------------------

It is possible to have multiple non-exclusive consumers on queues. In
that case, messages will be distributed between them according to
prefetch levels of their channels (more on this later in this guide). If
prefetch values are equal for all consumers, each consumer will get
about the same number of messages.

Starting a consumer
-------------------

.. code-block:: bash

    php public/index.php humus amqp consumer my-consumer

See: :ref:`cli` for more informations.

Killing a Consumer gracefully
-----------------------------

You can send a SIGUSER1 signal to gracefully shutdown the consumer (if started
from the consumer controller in Humus AMQP Module).

.. code-block:: bash

    kill -10 23453

Where 23453 is the process id of the consumer process.

QoS — Prefetching messages
~~~~~~~~~~~~~~~~~~~~~~~~~~

For cases when multiple consumers share a queue, it is useful to be able
to specify how many messages each consumer can be sent at once before
sending the next acknowledgement. This can be used as a simple load
balancing technique to improve throughput if messages tend to be
published in batches. For example, if a producing application sends
messages every minute because of the nature of the work it is doing.

Imagine a website that takes data from social media sources like Twitter
or Facebook during the Champions League (european soccer) final (or the
Superbowl), and then calculates how many tweets mentioned a particular
team during the last minute. The site could be structured as 3
applications:

-  A crawler that uses streaming APIs to fetch tweets/statuses,
   normalizes them and sends them in JSON for processing by other
   applications ("app A").
-  A calculator that detects what team is mentioned in a message,
   updates statistics and pushes an update to the Web UI once a minute
   ("app B").
-  A Web UI that fans visit to see the stats ("app C").

In this imaginary example, the "tweets per second" rate will vary, but
to improve the throughput of the system and to decrease the maximum
number of messages that the AMQP broker has to hold in memory at once,
applications can be designed in such a way that application "app B", the
"calculator", receives 5000 messages and then acknowledges them all at
once. The broker will not send message 5001 unless it receives an
acknowledgement.

In AMQP 0.9.1 parlance this is known as *QoS* or *message prefetching*.
Prefetching is configured on a per-channel basis.

.. note:: The prefetch setting is ignored for consumers that do not
    use explicit acknowledgements.

What to Read Next
-----------------

The documentation is organized as :ref:`a number of guides <guides>`, covering various topics.

We recommend that you read the following guides first, if possible, in
this order:

-  :ref:`Error Handling and Recovery <error_handling>`
-  :ref:`Troubleshooting <troubleshooting>`

Tell Us What You Think!
-----------------------

Please take a moment to tell us what you think about this guide: `Send an e-mail
<saschaprolic@googlemail.com>`_ or raise an issue on `Github <https://www.github.com/prolic/HumusAmqpModule/issues>`_.

Let us know what was unclear or what has not been covered. Maybe you
do not like the guide style or grammar or discover spelling
mistakes. Reader feedback is key to making the documentation better.
