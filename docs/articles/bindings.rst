.. _bindings:

Bindings
========

What Are AMQP 0.9.1 Bindings
----------------------------

Bindings are rules that exchanges use (among other things) to route
messages to queues. To instruct an exchange E to route messages to a
queue Q, Q has to *be bound* to E. Bindings may have an optional
*routing key* attribute used by some exchange types. The purpose of the
routing key is to selectively match only specific (matching) messages
published to an exchange to the bound queue. In other words, the routing
key acts like a filter.

To draw an analogy:

-  Queue is like your destination in New York city
-  Exchange is like JFK airport
-  Bindings are routes from JFK to your destination. There may be no
   way, or more than one way, to reach it

Some exchange types use routing keys while some others do not (routing
messages unconditionally or based on message metadata). If an AMQP
message cannot be routed to any queue (for example, because there are no
bindings for the exchange it was published to), it is either dropped or
returned to the publisher, depending on the message attributes that the
publisher has set.

If an application wants to connect a queue to an exchange, it needs to
*bind* them. The opposite operation is called *unbinding*.

Binding Queues to Exchanges
---------------------------

In order to receive messages, a queue needs to be bound to at least one
exchange. Most of the time binding is explcit (done by applications).

Example:

.. code-block:: php

    .. code-block:: php

    <?php

    return array(
        'humus_amqp_module' => array(
            'exchanges' => array(
                'demo-exchange' => array(
                    'name' => 'demo-exchange',
                    'type' => 'direct'
                )
            ),
            'queues' => array(
                'my-queue' => array(
                    'name' => 'my-queue',
                    'exchange' => 'demo-exchange'
                )
            )
        )
    );

Unbinding queues from exchanges
-------------------------------

.. code-block:: php

    <?php

    $queue->unbind('exchange-name');

Exchange-to-Exchange Bindings
-----------------------------

Exchange-to-Exchange bindings is a RabbitMQ extension to AMQP 0.9.1. It
is covered in the `RabbitMQ extensions
guide </articles/extensions.html>`_.

Bindings, Routing and Returned Messages
---------------------------------------

How RabbitMQ Routes Messages
~~~~~~~~~~~~~~~~~~~~~~~~~~~~

After a message reaches RabbitMQ and before it reaches a consumer,
several things happen:

-  RabbitMQ needs to find one or more queues that the message needs to
   be routed to, depending on type of exchange
-  RabbitMQ puts a copy of the message into each of those queues or
   decides to return the message to the publisher
-  RabbitMQ pushes message to consumers on those queues or waits for
   applications to fetch them on demand

A more in-depth description is this:

-  RabbitMQ needs to consult bindings list for the exchange the message
   was published to in order to find one or more queues that the message
   needs to be routed to (step 1)
-  If there are no suitable queues found during step 1 and the message
   was published as mandatory, it is returned to the publisher (step 1b)
-  If there are suitable queues, a *copy* of the message is placed into
   each one (step 2)
-  If the message was published as mandatory, but there are no active
   consumers for it, it is returned to the publisher (step 2b)
-  If there are active consumers on those queues and the basic.qos
   setting permits, message is pushed to those consumers (step 3)

The important thing to take away from this is that messages may or may
not be routed and it is important for applications to handle unroutable
messages.

Handling of Unroutable Messages
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Unroutable messages are either dropped or returned to producers.
RabbitMQ extensions can provide additional ways of handling unroutable
messages: for example, RabbitMQ's `Alternate Exchanges
extension <http://www.rabbitmq.com/ae.html>`_ makes it possible to route
unroutable messages to another exchange. Bunny support for it is
documented in the `RabbitMQ Extensions
guide </articles/extensions.html>`_.

`Exchanges and Publishing </articles/exchanges.html>`_ documentation
guide provides more information on the subject, including full code
examples.

What to Read Next
-----------------

The documentation is organized as `a number of
guides </articles/guides.html>`_, covering various topics.

We recommend that you read the following guides first, if possible, in
this order:

-  `RabbitMQ Extensions to AMQP 0.9.1 </articles/extensions.html>`_
-  `Durability and Related Matters </articles/durability.html>`_
-  `Error Handling and Recovery </articles/error_handling.html>`_
-  `Troubleshooting </articles/troubleshooting.html>`_

Tell Us What You Think!
-----------------------

Please take a moment to tell us what you think about this guide: `Send an e-mail
<saschaprolic@googlemail.com>`_ or raise an issue on `Github <https://www.github.com/prolic/HumusAmqpModule/issues>`_.

Let us know what was unclear or what has not been covered. Maybe you
do not like the guide style or grammar or discover spelling
mistakes. Reader feedback is key to making the documentation better.
