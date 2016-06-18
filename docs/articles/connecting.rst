.. _connecting:

Connecting to RabbitMQ from Zend Framework 2 with Humus AMQP Module
===================================================================

Connection configuration
------------------------

Map options that Humus AMQP Module will recognize are

-  ``:host``          - amqp.host The host to connect too. Note: Max 1024 characters.
-  ``:port``          - amqp.port Port on the host.
-  ``:vhost``         - amqp.vhost The virtual host on the host. Note: Max 128 characters.
-  ``:login``         - amqp.login The login name to use. Note: Max 128 characters.
-  ``:password``      - amqp.password Password. Note: Max 128 characters.
-  ``:persistent``    - Establish a persistent connection with the AMQP broker, if set to true.
-  ``:read_timeout``  - Timeout in for income activity. Note: 0 or greater seconds. May be fractional.
-  ``:write_timeout`` - Timeout in for outcome activity. Note: 0 or greater seconds. May be fractional.

Default parameters
------------------

Default connection parameters are

.. code-block:: php

    [
      'host'         => "127.0.0.1",
      'port'         => 5672,
      'vhost'        => "/",
      'login'        => "guest",
      'password'     => "guest",
      'persistent'   => false,
      'readTimeout'  => 1.0,
      'writeTimeout' => 1.0
    ]

.. note:: The persistent parameter is only used by the Humus AMQP Module's ConnectionAbstractServiceFactory.
Based on this parameter the module will decide, whether to call pconnect() or connect() on the connection.

Creating a connection
---------------------

.. code-block:: php

    <?php

    $conn = new AMQPConnection();
    $conn->setLogin('demouser');
    $conn->setPassword('password');
    ...
    $conn->pconnect();

Opening a Channel
-----------------

Some applications need multiple connections to RabbitMQ. However, it is
undesirable to keep many TCP connections open at the same time because
doing so consumes system resources and makes it more difficult to
configure firewalls. AMQP 0-9-1 connections are multiplexed with
channels that can be thought of as "lightweight connections that share a
single TCP connection".

To open a channel:

.. code-block:: php

    <?php

    $conn = new AMQPConnection();
    $conn->connect();
    $ch   = new AMQPChannel($conn);

Channels are typically long lived: you open one or more of them and use
them for a period of time, as opposed to opening a new channel for each
published message, for example.

Disconnecting
-------------

To close a connection, use the disconnect() method. This
will automatically close all channels of that connection first:

.. code-block:: php

    <?php

    $conn = new AMQPConnection();
    $conn->connect();
    $ch   = new AMQPChannel($conn);
    $conn->disconnect();

Module Configuration
--------------------

You can simply configure as many connection as needed and simply give them a name. You can also set a default
connection, using the ``default_connection`` configuration key.

.. code-block:: php

    <?php

    return array(
        'humus_amqp_module' => array(
            'default_connection' => 'default',
            'connections' => array(
                'default' => array(
                    'host' => 'localhost',
                    'port' => 5672,
                    'login' => 'guest',
                    'password' => 'guest',
                    'vhost' => '/',
                    'persistent' => true,
                )
            )
        )
    );

Getting a connection
--------------------

All connections are handled by the HumusAmqpModule\PluginManager\Connection. To grab a connection simply call:

.. code-block:: php

    <?php

    $connectionManager = $serviceManager->get('HumusAmqpModule\PluginManager\Connection');
    $defaultConnection = $connectionManager->get('default');


Troubleshooting
---------------

If you have read this guide and still have issues with connecting, check
our :ref:`Troubleshooting guide <troubleshooting>` and feel
free to raise an issue at `Github <https://www.github.com/prolic/HumusAmqpModule/issues>`_.

What to Read Next
-----------------

The documentation is organized as :ref:`a number of guides <guides>`, covering various topics.

We recommend that you read the following guides first, if possible, in
this order:

-  :ref:`Queues and Consumers <queues>`
-  :ref:`Exchanges and Publishing <exchanges>`
-  :ref:`Bindings <bindings>`
-  `RabbitMQ Extensions to AMQP
   0.9.1 <rabbitmq_extensions>`_
-  :ref:`Durability and Related Matters <durability>`
-  :ref:`Error Handling and Recovery <error_handling>`
-  :ref:`Troubleshooting <troubleshooting>`

Tell Us What You Think!
-----------------------

Please take a moment to tell us what you think about this guide: `Send an e-mail
<saschaprolic@googlemail.com>`_ or raise an issue on `Github <https://www.github.com/prolic/HumusAmqpModule/issues>`_.

Let us know what was unclear or what has not been covered. Maybe you
do not like the guide style or grammar or discover spelling
mistakes. Reader feedback is key to making the documentation better.
