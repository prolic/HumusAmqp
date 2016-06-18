.. _error_handling:

Error Handling
==============

Client Exceptions
-----------------

Here is the break-down of exceptions that can be raised by the PHP AMQP Extension:

::

    AMQPChannelException
    AMQPConnectionException
    AMQPExchangeException
    AMQPQueueException

Additionally the Humus AMQP Module throws some of the following exceptions

::

    HumusAmqpModule\Exception\BadFunctionCallException
    HumusAmqpModule\Exception\BadMethodCallException
    HumusAmqpModule\Exception\ExtensionNotLoadedException
    HumusAmqpModule\Exception\InvalidArgumentException
    HumusAmqpModule\Exception\RuntimeException

The first 3 exceptions only occur, when you don't have ext-pcntl installed but you try to start
the consumer without the --without-signals switch.

The InvalidArgumentException occurs, when you have a wrong amqp module configuration.

Initial RabbitMQ Connection Failures
------------------------------------

When applications connect to the broker, they need to handle connection
failures. Networks are not 100% reliable, even with modern system
configuration tools like Chef or Puppet misconfigurations happen and the
broker might also be down. Error detection should happen as early as
possible. To handle TCP connection failure, catch the
``AMQPConnection`` exception.

Authentication Failures
-----------------------

Another reason why a connection may fail is authentication failure.
Handling authentication failure is very similar to handling initial TCP
connection failure.

When you try to access RabbitMQ with invalid credentials, you'll get an
'AMQPConnectionException' with message 'Library error: a socket error occurred - Potential login failure.'.

In case you are wondering why the exception name has "potential" in it:
`AMQP 0.9.1
spec <http://www.rabbitmq.com/resources/specs/amqp0-9-1.pdf>`_ requires
broker implementations to simply close TCP connection without sending
any more data when an exception (such as authentication failure) occurs
before AMQP connection is open. In practice, however, when broker closes
TCP connection between successful TCP connection and before AMQP
connection is open, it means that authentication has failed.

Channel-level Exceptions
------------------------

Channel-level exceptions are more common than connection-level ones and
often indicate issues applications can recover from (such as consuming
from or trying to delete a queue that does not exist).

Common channel-level exceptions and what they mean
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

A few channel-level exceptions are common and deserve more attention.

406 Precondition Failed
^^^^^^^^^^^^^^^^^^^^^^^

.. raw:: html

   <dl>
     <dt>

Description

.. raw:: html

   </dt>
     <dd>

The client requested a method that was not allowed because some
precondition failed.

.. raw:: html

   </dd>
     <dt>

What might cause it

.. raw:: html

   </dt>
     <dd>
       <ul>
         <li>

AMQP entity (a queue or exchange) was re-declared with attributes
different from original declaration. Maybe two applications or pieces of
code declare the same entity with different attributes. Note that
different RabbitMQ client libraries historically use slightly different
defaults for entities and this may cause attribute mismatches.

.. raw:: html

   </dt>
     <dd>
       <ul>
         <li>

PRECONDITION\_FAILED - parameters for queue
'examples.channel\_exception' in vhost '/' not equivalent

.. raw:: html

   </li>
         <li>

PRECONDITION\_FAILED - channel is not transactional

.. raw:: html

   </li>
       </ul>
     </dd>
   </dl>

405 Resource Locked
^^^^^^^^^^^^^^^^^^^

.. raw:: html

   <dl>
     <dt>

Description

.. raw:: html

   </dt>
     <dd>

The client attempted to work with a server entity to which it has no
access because another client is working with it.

.. raw:: html

   </dd>
     <dt>

What might cause it

.. raw:: html

   </dt>
     <dd>
       <ul>
         <li>

Multiple applications (or different pieces of
code/threads/processes/routines within a single application) might try
to declare queues with the same name as exclusive.

.. raw:: html

   </li>
         <li>

Multiple consumer across multiple or single app might be registered as
exclusive for the same queue.

.. raw:: html

   </li>
       </ul>
     </dd>
     <dt>

Example RabbitMQ error message

.. raw:: html

   </dt>
     <dd>

RESOURCE\_LOCKED - cannot obtain exclusive access to locked queue
'examples.queue' in vhost '/'

.. raw:: html

   </dd>
   </dl>

404 Not Found
^^^^^^^^^^^^^

.. raw:: html

   <dl>
     <dt>

Description

.. raw:: html

   </dt>
     <dd>

The client attempted to use (publish to, delete, etc) an entity
(exchange, queue) that does not exist.

.. raw:: html

   </dd>
     <dt>

What might cause it

.. raw:: html

   </dt>
     <dd>

Application miscalculates queue or exchange name or tries to use an
entity that was deleted earlier

.. raw:: html

   </dd>
     <dt>

Example RabbitMQ error message

.. raw:: html

   </dt>
     <dd>

NOT\_FOUND - no queue
'queue\_that\_should\_not\_exist0.6798199937619038' in vhost '/'

.. raw:: html

   </dd>
   </dl>

403 Access Refused
^^^^^^^^^^^^^^^^^^

.. raw:: html

   <dl>
     <dt>

Description

.. raw:: html

   </dt>
     <dd>

The client attempted to work with a server entity to which it has no
access due to security settings.

.. raw:: html

   </dd>
     <dt>

What might cause it

.. raw:: html

   </dt>
     <dd>

Application tries to access a queue or exchange it has no permissions
for (or right kind of permissions, for example, write permissions)

.. raw:: html

   </dd>
     <dt>

Example RabbitMQ error message

.. raw:: html

   </dt>
     <dd>

ACCESS\_REFUSED - access to queue 'examples.channel\_exception' in
vhost '_testbed' refused for user '_reader'

.. raw:: html

   </dd>
   </dl>



What to Read Next
-----------------

The documentation is organized as `a number of
guides </articles/guides.html>`_, covering various topics.

We recommend that you read the following guides first, if possible, in
this order:

-  `Troubleshooting </articles/troubleshooting.html>`_

Tell Us What You Think!
-----------------------

Please take a moment to tell us what you think about this guide: `Send an e-mail
<saschaprolic@googlemail.com>`_ or raise an issue on `Github <https://www.github.com/prolic/HumusAmqpModule/issues>`_.

Let us know what was unclear or what has not been covered. Maybe you
do not like the guide style or grammar or discover spelling
mistakes. Reader feedback is key to making the documentation better.
