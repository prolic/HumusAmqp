.. _guides:

Guides overview
===============

We recommend that you read these guides, if possible, in this order:

:ref:`Getting started <getting-started>`
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

An overview of HumusAmqpModule with a quick tutorial that helps you to get started
with it. It should take about 20 minutes to read and study the provided
code examples.

`AMQP 0.9.1 Model Concepts <http://www.rabbitmq.com/tutorials/amqp-concepts.html>`_
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This guide covers:

-  AMQP 0.9.1 model overview
-  What are channels
-  What are vhosts
-  What are queues
-  What are exchanges
-  What are bindings
-  What are AMQP 0.9.1 classes and methods

:ref:`Connecting to RabbitMQ from Zend Framework 2 with Humus AMQP Module <connecting>`
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This guide covers:

-  How to connect to RabbitMQ with HumusAmqpModule
-  How to use connection URI to connect to RabbitMQ (also: in PaaS
   environments such as Heroku and CloudFoundry)
-  How to open a channel
-  How to close a channel
-  How to disconnect

:ref:`Queues and Consumers <queues>`
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This guide covers:

-  How to declare AMQP queues with HumusAmqpModule
-  Queue properties
-  How to declare server-named queues
-  How to declare temporary exclusive queues
-  How to consume messages ("push API")
-  How to fetch messages ("pull API")
-  Message and delivery properties
-  Message acknowledgements
-  How to purge queues
-  How to delete queues
-  Other topics related to queues

:ref:`Exchanges and Publishing <exchanges>`
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This guide covers:

-  Exchange types
-  How to declare AMQP exchanges with HumusAmqpModule
-  How to publish messages
-  Exchange properties
-  Fanout exchanges
-  Direct exchanges
-  Topic exchanges
-  Default exchange
-  Message and delivery properties
-  Message routing
-  Bindings
-  How to delete exchanges
-  Other topics related to exchanges and publishing

:ref:`Bindings <bindings>`
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This guide covers:

-  How to bind exchanges to queues
-  How to unbind exchanges from queues
-  Other topics related to bindings

:ref:`Durability and Related Matters <durability>`
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This guide covers:

-  Topics related to durability of exchanges and queues
-  Durability of messages

:ref:`RabbitMQ Extensions to AMQP 0.9.1 <extensions>`
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This guide covers `RabbitMQ
extensions <http://www.rabbitmq.com/extensions.html>`_ and how they are
used in Humus AMQP Module:

-  How to use exchange-to-exchange bindings
-  How to the alternate exchange extension
-  How to set per-queue message TTL
-  How to set per-message TTL
-  What are consumer cancellation notifications and how to use them
-  Message *dead lettering* and the dead letter exchange

:ref:`Error Handling and Recovery <error_handling>`
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This guide covers:

-  AMQP 0.9.1 protocol exceptions
-  How to deal with network failures
-  Other things that may go wrong


:ref:`Troubleshooting <troubleshooting>`
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

This guide covers:

-  What to check when your apps that use Humus AMQP Module and RabbitMQ misbehave

Tell Us What You Think!
-----------------------

Please take a moment to tell us what you think about this guide: `Send an e-mail
<saschaprolic@googlemail.com>`_ or raise an issue on `Github <https://www.github.com/prolic/HumusAmqpModule/issues>`_.

Let us know what was unclear or what has not been covered. Maybe you
do not like the guide style or grammar or discover spelling
mistakes. Reader feedback is key to making the documentation better.

