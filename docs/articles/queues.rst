.. _queues:

Queues
======

Queues in AMQP 0.9.1: Overview
------------------------------

What are AMQP Queues?
~~~~~~~~~~~~~~~~~~~~~

*Queues* store and forward messages to consumers. They are similar to
mailboxes in SMTP. Messages flow from producing applications to
:ref:`Exchanges <exchanges>` that route them to queues and
finally, queues deliver the messages to consumer applications (or
consumer applications fetch messages as needed).

.. note:: Note that unlike some other messaging protocols/systems, messages
    are not delivered directly to queues. They are delivered to exchanges
    that route messages to queues using rules known as *bindings*.

AMQP 0.9.1 is a programmable protocol, so queues and bindings alike are
declared by applications.

Concept of Bindings
~~~~~~~~~~~~~~~~~~~

A *binding* is an association between a queue and an exchange. Queues
must be bound to at least one exchange in order to receive messages from
publishers. Learn more about bindings in the :ref:`Bindings guide <bindings>`.

Queue Attributes
~~~~~~~~~~~~~~~~

Queues have several attributes associated with them:

-  Name
-  Exclusivity
-  Durability
-  Whether the queue is auto-deleted when no longer used
-  Other metadata (sometimes called *X-arguments*)

These attributes define how queues can be used, their life-cycle, and
other aspects of queue behavior.

Queue Names and Declaring Queues
--------------------------------

Every AMQP queue has a name that identifies it. Queue names often
contain several segments separated by a dot ".", in a similar fashion to
URI path segments being separated by a slash "/", although almost any
string can represent a segment (with some limitations - see below).

Before a queue can be used, it has to be *declared*. Declaring a queue
will cause it to be created if it does not already exist. The
declaration will have no effect if the queue does already exist and its
attributes are the *same as those in the declaration*. When the existing
queue attributes are not the same as those in the declaration a
channel-level exception is raised. This case is explained later in this
guide.

Explicitly Named Queues
~~~~~~~~~~~~~~~~~~~~~~~

Applications may pick queue names or ask the broker to generate a name
for them. The configure a queue with an explicit name:

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


Server-named queues
~~~~~~~~~~~~~~~~~~~

To ask an AMQP broker to generate a unique queue name for you, pass an
*empty string* as the queue name argument. A generated queue name (like
*amq.gen-JZ46KgZEOZWg-pAScMhhig*) will be assigned to the queue instance
that the method returns:

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
                    'name' => '',
                    'exchange' => 'demo-exchange'
                )
            )
        )
    );

.. note:: While it is common to declare server-named queues as
    ``:exclusive``, it is not necessary.

Reserved Queue Name Prefix
~~~~~~~~~~~~~~~~~~~~~~~~~~

Queue names starting with "amq." are reserved for server-named queues
and queues for internal use by the broker. Attempts to declare a queue
with a name that violates this rule will result in an AMQPExchangeException
with reply code ``403`` and an exception message
similar to this:

::

    Server channel error: 403, message: ACCESS_REFUSED - exchange name 'amq.queue' contains reserved prefix 'amq.*'

This error results in the channel that was used for the declaration
being forcibly closed by RabbitMQ. If the program subsequently tries to
communicate with RabbitMQ using the same channel without re-opening it
then the AMQP Extension will throw an ``AMQPChannelException' with message
'Could not create exchange. No channel available``.

Queue Re-Declaration With Different Attributes
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

When queue declaration attributes are different from those that the
queue already has, a channel-level exception with code
``406`` will be raised. The reply text will be
similar to this:

::

    Server channel error: 406, message: PRECONDITION_FAILED - cannot redeclare exchange 'foo' in vhost '/'
    with different type, durable, internal or autodelete value

This error results in the channel that was used for the declaration
being forcibly closed by RabbitMQ. If the program subsequently tries to
communicate with RabbitMQ using the same channel without re-opening it
then Bunny will throw an ``AMQPChannelException' with message
'Could not create exchange. No channel available``. In order
to continue communications in the same program after such an error, a
different channel would have to be used.

Queue Life-cycle Patterns
-------------------------

According to the AMQP 0.9.1 specification, there are two common message
queue life-cycle patterns:

-  Durable queues that are shared by many consumers and have an
   independent existence: i.e. they will continue to exist and collect
   messages whether or not there are consumers to receive them.
-  Temporary queues that are private to one consumer and are tied to
   that consumer. When the consumer disconnects, the message queue is
   deleted.

There are some variations of these, such as shared message queues that
are deleted when the last of many consumers disconnects.

Let us examine the example of a well-known service like an event
collector (event logger). A logger is usually up and running regardless
of the existence of services that want to log anything at a particular
point in time. Other applications know which queues to use in order to
communicate with the logger and can rely on those queues being available
and able to survive broker restarts. In this case, explicitly named
durable queues are optimal and the coupling that is created between
applications is not an issue.

Another example of a well-known long-lived service is a distributed
metadata/directory/locking server like `Apache
Zookeeper <http://zookeeper.apache.org>`_, `Google's
Chubby <http://labs.google.com/papers/chubby.html>`_ or DNS. Services
like this benefit from using well-known, not server-generated, queue
names and so do any other applications that use them.

A different sort of scenario is in "a cloud setting" when some kind of
worker/instance might start and stop at any time so that other
applications cannot rely on it being available. In this case, it is
possible to use well-known queue names, but a much better solution is to
use server-generated, short-lived queues that are bound to topic or
fanout exchanges in order to receive relevant messages.

Imagine a service that processes an endless stream of events — Twitter
is one example. When traffic increases, development operations may start
additional application instances in the cloud to handle the load. Those
new instances want to subscribe to receive messages to process, but the
rest of the system does not know anything about them and cannot rely on
them being online or try to address them directly. The new instances
process events from a shared stream and are the same as their peers. In
a case like this, there is no reason for message consumers not to use
queue names generated by the broker.

In general, use of explicitly named or server-named queues depends on
the messaging pattern that your application needs. `Enterprise
Integration Patterns <http://www.eaipatterns.com/>`_ discusses many
messaging patterns in depth and the RabbitMQ FAQ also has a section on
`use cases <http://www.rabbitmq.com/faq.html#scenarios>`_.

Declaring a Durable Shared Queue
--------------------------------

To declare a durable shared queue, you pass a queue name that is a
non-blank string and use the ``:durable`` option:

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
                    'name' => 'demo-queue',
                    'exchange' => 'demo-exchange',
                    'durable' => true
                )
            )
        )
    );

Declaring a Temporary Exclusive Queue
-------------------------------------

To declare a server-named, exclusive, auto-deleted queue, pass "" (an
empty string) as the queue name and use the ``:exclusive`` option:

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
                    'name' => '',
                    'exchange' => 'demo-exchange',
                    'exclusive' => true
                )
            )
        )
    );

Exclusive queues may only be accessed by the current connection and are
deleted when that connection closes. The declaration of an exclusive
queue by other connections is not allowed and will result in a
channel-level exception with the code ``405 (RESOURCE_LOCKED)``

Exclusive queues will be deleted when the connection they were declared
on is closed.

Checking if a Queue Exists
--------------------------

Sometimes it's convenient to check if a queue exists. To do so, at the
protocol level you use ``queue.declareQueue`` with ``passive`` set to
``true``. In response RabbitMQ responds with a channel exception if the
queue does not exist. This will lead to an 'AMQPQueueException' with message
'Server channel error: 404, message: NOT_FOUND - no queue 'test-queue' in vhost '/'

Binding Queues with Routing Keys
--------------------------------

In order to receive messages, a queue needs to be bound to at least one
exchange. Most of the time binding is explcit (done by applications).
**Please note:** All queues are automatically bound to the default
unnamed RabbitMQ direct exchange with a routing key that is the same as
the queue name (see `Exchanges and
Publishing </articles/exchanges.html>`_ guide for more details).

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
                    'name' => 'demo-queue',
                    'exchange' => 'demo-exchange',
                    'routingKeys => array(
                        'v1.0.*',
                        'v1.1.0',
                        'v2.0.0'
                    )
                )
            )
        )
    );

Unbinding Queues From Exchanges
-------------------------------

To unbind a queue from an exchange use the ``AMQPQueue#unbind``
function:

.. code-block:: php

    <?php

    $queue->unbind('exchange-name');

.. note:: Trying to unbind a queue from an exchange that the queue
    was never bound to will result in a channel-level exception.

Purging queues
--------------

It is possible to purge a queue (remove all of the messages from it)
using the ``AMQPQueue#purge`` method:

.. code-block:: php

    <?php

    $queue->purge();

.. note:: When a server named queue is declared, it is empty, so for
    server-named queues, there is no need to purge them before they are used.

Deleting Queues
---------------

Queues can be deleted either indirectly or directly. To delete a queue
indirectly you can include either of the following two arguments in the
queue declaration:

-  ``:exclusive => true``
-  ``:auto_delete => true``

If the *exclusive* flag is set to true then the queue will be deleted
when the connection that was used to declare it is closed.

If the *auto\_delete* flag is set to true then the queue will be deleted
when there are no more consumers subscribed to it. The queue will remain
in existence until at least one consumer accesses it.

To delete a queue directly, use the ``AMQPQueue#delete`` method:

.. code-block:: php

    <?php

    $queue->delete();

When a queue is deleted, all of the messages in it are deleted as well.

Queue Durability vs Message Durability
--------------------------------------

See `Durability guide </articles/durability.html>`_

RabbitMQ Extensions Related to Queues
-------------------------------------

See `RabbitMQ Extensions guide </articles/rabbitmq_extensions.html>`_

Wrapping Up
-----------

In RabbitMQ, queues can be client-named or server-named.
For messages to be routed to queues, queues need to be bound to
exchanges.

What to Read Next
-----------------

The documentation is organized as `a number of
guides </articles/guides.html>`_, covering various topics.

We recommend that you read the following guides first, if possible, in
this order:

-  `Exchanges and Publishing </articles/exchanges.html>`_
-  `Bindings </articles/bindings.html>`_
-  `RabbitMQ Extensions to AMQP
   0.9.1 </articles/rabbitmq_extensions.html>`_
-  `Durability and Related Matters </articles/durability.html>`_
-  `Error Handling and Recovery </articles/error_handling.html>`_
-  `Concurrency Considerations </articles/concurrency.html>`_
-  `Troubleshooting </articles/troubleshooting.html>`_
-  `Using TLS (SSL) Connections </articles/tls.html>`_

Tell Us What You Think!
-----------------------

Please take a moment to tell us what you think about this guide `on
Twitter <http://twitter.com/rubyamqp>`_ or the `Bunny mailing
list <https://groups.google.com/forum/#!forum/ruby-amqp>`_

Let us know what was unclear or what has not been covered. Maybe you do
not like the guide style or grammar or discover spelling mistakes.
Reader feedback is key to making the documentation better.
