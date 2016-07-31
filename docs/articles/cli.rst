.. _cli:

Running from CLI
================

In order to run cli commands, you need to setup your connection, exchange and queue configuration.
See here on how to do this:

You can run cli commands like this:

.. code-block:: bash

    $ ./vendor/bin/humus-amqp

Setup-Fabric
------------

To setup all exchanges and queues configured:

.. code-block:: bash

    $ ./vendor/bin/humus-amqp setup-fabric

This will create all exchanges and queues.

Running consumers
-----------------

To start a consumer:

.. code-block:: bash

    $ ./vendor/bin/humus-amqp consumer -n myconsumer -a 100

This will start the myconsumer and consume 100 messages until if stops or times out.

Running JSON-RPC servers
------------------------

To start a JSON-RPC server

.. code-block:: bash

    $ ./vendor/bin/humus-amqp json_rpc_server -n myserver -a 100

This will start the myserver and consume 100 messages until if stops or times out.

List amqp types
---------------

Show availables connections, exchanges, queues, callback_consumers, producers, json_rpc_clients and json_rpc_servers

.. code-block:: bash

    $ ./vendor/bin/humus-amqp show -t exchanges

This will list all known exchanges.

Purge queues
------------

To purge a queue:

.. code-block:: bash

    $ ./vendor/bin/humus-amqp purge-queue -c myqueue

This will remove all messages from the given queue.

Publishing from CLI
-------------------

To publish a message to an exchane via CLI:

.. code-block:: bash

    $ ./vendor/bin/humus-amqp publish-message -p myproducer -m "my text" -c -r my.routing.key

This will send a message with body "my text" and routing key "my.routing.key" via the "myproducer"-producer.

Troubleshooting
---------------

If you have read this guide and still have issues with connecting, check
our :ref:`Troubleshooting guide <troubleshooting>` and feel
free to raise an issue at `Github <https://www.github.com/prolic/HumusAmqp/issues>`_.

What to Read Next
-----------------

The documentation is organized as :ref:`a number of guides <guides>`, covering various topics.

We recommend that you read the following guides first, if possible, in
this order:

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
