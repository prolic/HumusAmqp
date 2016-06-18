.. _getting-started:

Getting Started with Humus AMQP Module, RabbitMQ and Zend Framework 2
=====================================================================

About this guide
----------------

This guide is a quick tutorial that helps you to get started with
RabbitMQ and `HumusAmqpModule <https://www.github.com/prolic/HumusAmqpModule>`_.  It should
take about 20 minutes to read and study the provided code
examples. This guide covers:

 * Installing RabbitMQ, a mature popular messaging broker server.
 * Installing HumusAmqpModule via `Composer <http://www.getcomposer.org/>`_.
 * Installing HumusAmqpDemoModule.
 * Producing and consuming messages from cli.
 * Running a rpc server and and a client.


Installing RabbitMQ
-------------------

The `RabbitMQ site
<http://rabbitmq.com>`_ has a good `installation guide
<http://rabbitmq.com/install.html>`_ that addresses many operating systems.
On Mac OS X, the fastest way to install RabbitMQ is with `Homebrew
<http://mxcl.github.com/homebrew/>`_:

.. code-block:: bash

    $ brew install rabbitmq

then run it:

.. code-block:: bash

    $ rabbitmq-server

On Debian and Ubuntu, you can either `download the RabbitMQ .deb
package
<http://rabbitmq.com/server.html>`_ and install it with
`dpkg
<http://www.debian.org/doc/FAQ/ch-pkgtools.en.html>`_ or make use
of the `apt repository
<http://rabbitmq.com/debian.html#apt_>`_ that
the RabbitMQ team provides.

For RPM-based distributions like RedHat or CentOS, the RabbitMQ team
provides an `RPM package
<http://www.rabbitmq.com/install.html#rpm>`_.


Installing HumusAmqpModule & HumusAmqpDemoModule
------------------------------------------------

a) Make sure you have the `php-amqp extension <https://github.com/pdezwart/php-amqp>`_ installed

b) The minimum required version is 1.4.0

c) Make sure that you have a running
`Zend Framework 2 Skeleton Application <https://github.com/zendframework/ZendSkeletonApplication>`_

d) You can use composer to install HumusAmqpModule

.. code-block:: bash

    $ cd path/to/zf2app
    $ php composer.phar require prolic/humus-amqp-module dev-master
    $ php composer.phar require prolic/humus-amqp-demo-module dev-master

e) Adding HumusAmqpModule & HumusAmqpDemoModule to application configuration

Edit your config/application.config.php

.. code-block:: php

    <?php
        return array(
            // This should be an array of module namespaces used in the application.
            'modules' => array(
                'Application',
        ),

to

.. code-block:: php

    <?php
        return array(
        // This should be an array of module namespaces used in the application.
        'modules' => array(
            'Application',
            'HumusAmqpModule',
            'HumusAmqpDemoModule'
        ),

Running demos from CLI
----------------------

Demo-Consumer and Producer
~~~~~~~~~~~~~~~~~~~~~~~~~~

Open up 2 terminals.

Then run the demo consumer

.. code-block:: bash

    $ php public/index.php humus amqp consumer demo-consumer

Next, open another shell and run the demo producer

.. code-block:: bash

    $ php public/index.php humus amqp stdin-producer demo-producer "demo-message"

You should see the output in the demo consumer's shell. It should look something like this:

.. code-block:: bash

    hallo
    2014-08-27T18:43:30+02:00 DEBUG (7): Acknowledged 1 messages at 0 msg/s

If you run the command multiple times, you can see that it the consumer will also ack bundles of messages.
You noticed perhabs, that you run it with the stdin-producer command, but what does this mean? Try this:

.. code-block:: bash

    $ cat README.md | xargs -0 php public/index.php humus amqp stdin-producer demo-producer
    $ echo "my test message" | xargs -0 php public/index.php humus amqp stdin-producer demo-producer

For now, let's check what a demo consumer looks like and how to configure it.

The `EchoCallback <https://github.com/prolic/HumusAmqpDemoModule/blob/master/src/HumusAmqpDemoModule/Demo/EchoCallback.php>`_
is the implementation part of the consumer. As you can see, you simply provide a callable,
you get the parameters (message and queue) and you're ready to start. You don't need to extend
or even write yourself the consumer implementation.

The required connection configuration can be found at:
`module.config.php#L85-L95 <https://github.com/prolic/HumusAmqpDemoModule/blob/master/config/module.config.php#L85-L95>`_.

The required exchange configuration is also there:
`module.config.php#L27-L37 <https://github.com/prolic/HumusAmqpDemoModule/blob/master/config/module.config.php#L27-L37>`_.

The required queue configuration:
`module.config.php#L56-L64 <https://github.com/prolic/HumusAmqpDemoModule/blob/master/config/module.config.php#L56-L64>`_.

The required consumer configuration:
`module.config.php#L112-L119 <https://github.com/prolic/HumusAmqpDemoModule/blob/master/config/module.config.php#L112-L119>`_.

And finally, the required producer configuration:
`module.config.php#L98-L105 <https://github.com/prolic/HumusAmqpDemoModule/blob/master/config/module.config.php#L98-L105>`_.

More information about the configuration of the Humus AMQP module, check the other sections of the manual.

That's it, send a SIGUSR1-signal (kill -10) to stop the consumer. You probably noticed,
that there is an error-exchange configured for the demo exchange.

That's a nice exercise: Go and change the consumer callback to "return false;",
so the messages get a nack and will be routed to the error exchange. Attach a queue
to that exchange and create the consumer configuration. You can also reuse the already
existing
`EchoErrorCallback <https://github.com/prolic/HumusAmqpDemoModule/blob/master/src/HumusAmqpDemoModule/Demo/EchoErrorCallback.php>`_.


Topic consumer and producer example
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

First, run the consumer again:

.. code-block:: bash

    $ php public/index.php humus amqp consumer topic-consumer-error

This consumer is only interested in routing keys matching #.err, so let's send some messages with different routing keys.

.. code-block:: bash

    $ php public/index.php humus amqp stdin-producer topic-producer --route=level.err err
    $ php public/index.php humus amqp stdin-producer topic-producer --route=level.warn warn
    $ php public/index.php humus amqp stdin-producer topic-producer --route=level.info info
    $ php public/index.php humus amqp stdin-producer topic-producer --route=level.debug debug

As you can see, only the first message in interessting for the consumer, all others are trashed. Go, send a lot of messages:

.. code-block:: bash

    $ php public/index.php humus amqpdemo topic-producer 1000

This will send 1000 messages that will be consumed by the topic-consumer-error. You probably noticed, that by default,
the consumer will never ack more than 3 messages at once, even if you send tons of messages. You can change that, go to the module.config.php file:

.. code-block:: php

   'topic-consumer-error' => array(
        'queues' => array(
            'info-queue',
        ),
        'callback' => 'HumusAmqpDemoModule\Demo\EchoCallback',
        'qos' => array(
            'prefetch_count' => 100
        ),
        'auto_setup_fabric' => true
    ),

If you set the prefetch count to 100, the consumer will ack up to 100 messages at once. For more information, see: `Consumer Prefetch <http://www.rabbitmq.com/consumer-prefetch.html>`_.


Running RPC-client & -server example
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Open up 3 terminals.

Then run 2 rpc-servers

.. code-block:: bash

    $ php public/index.php humus amqp rpc-server demo-rpc-server
    $ php public/index.php humus amqp rpc-server demo-rpc-server2

Before we start the client, let's see, how a rpc-server get's configured what in the demo servers.

First, we need exchanges: `module.config.php#L46-L53 <https://github.com/prolic/HumusAmqpDemoModule/blob/master/config/module.config.php#L46-L53>`_.

Queues, too: `module.config.php#L65-L76 <https://github.com/prolic/HumusAmqpDemoModule/blob/master/config/module.config.php#L65-L76>`_.

And here's how the servers/ clients are configured: `module.config.phpL128-L145 <https://github.com/prolic/HumusAmqpDemoModule/blob/master/config/module.config.php#L128-L145>`_.

You can check the callbacks `here <https://github.com/prolic/HumusAmqpDemoModule/tree/master/src/HumusAmqpDemoModule/Demo>`_.

The PowerOfTwoCallback does a sleep(1) before returning, the RandomIntCallback does a sleep(2); With this, it's more easy to show real parallel processing.

Start the rpc-client

.. code-block:: bash

    $ php public/index.php humus amqpdemo rpc-client 5

This will send 5 messages, you can see in the server output, that the messages are acknowledged and the response in the client afterwards.
No let's send messages to both:

.. code-block:: bash

    $ php public/index.php humus amqpdemo rpc-client 5 --parallel

What? Don't believe it? It's truly parallel!

.. code-block:: bash

    $ time php public/index.php humus amqpdemo rpc-client 5 --parallel

Enjoy!

See :ref:`Running from CLI <cli>` get know more about Humus AMQP Module's CLI commands.

What to read next
-----------------

Documentation is organized as a number of :ref:`guides <guides>`, covering all
kinds of topics including use cases for various exchange types,
fault-tolerant message processing with acknowledgements and error
handling.

We recommend that you read the following guides next, if possible, in this order:

 * `AMQP 0.9.1 Model Explained <http://www.rabbitmq.com/tutorials/amqp-concepts.html>`_. A simple 2 page long introduction to the AMQP Model concepts and features. Understanding the AMQP 0.9.1 Model
   will make a lot of other documentation, both for Bunny and RabbitMQ itself, easier to follow. With this guide, you don't have to waste hours of time reading the whole specification.
 * :ref:`connecting`. This guide explains how to connect to an RabbitMQ and how to integrate Bunny into standalone and Web applications.
 * :ref:`queues`. This guide focuses on features that consumer applications use heavily.
 * :ref:`exchanges`. This guide focuses on features that producer applications use heavily.
 * :ref:`error_handling`. This guide explains how to handle protocol errors, network failures and other things that may go wrong in real world projects.


Tell Us What You Think!
-----------------------

Please take a moment to tell us what you think about this guide: `Send an e-mail
<saschaprolic@googlemail.com>`_ or raise an issue on `Github <https://www.github.com/prolic/HumusAmqpModule/issues>`_.

Let us know what was unclear or what has not been covered. Maybe you
do not like the guide style or grammar or discover spelling
mistakes. Reader feedback is key to making the documentation better.
