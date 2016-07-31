.. _connecting:

Connecting to RabbitMQ with HumusAmqp
=====================================

Connection configuration
------------------------

Map options that HumusAmqp will recognize are

-  ``:host``            - amqp.host The host to connect too. Note: Max 1024 characters.
-  ``:port``            - amqp.port Port on the host.
-  ``:vhost``           - amqp.vhost The virtual host on the host. Note: Max 128 characters.
-  ``:login``           - amqp.login The login name to use. Note: Max 128 characters.
-  ``:password``        - amqp.password Password. Note: Max 128 characters.
-  ``:persistent``      - Establish a persistent connection with the AMQP broker, if set to true.
-  ``:connect_timeout`` - Connection timeout. Note: 0 or greater seconds. May be fractional.
-  ``:read_timeout``    - Timeout in for income activity. Note: 0 or greater seconds. May be fractional.
-  ``:write_timeout``   - Timeout in for outcome activity. Note: 0 or greater seconds. May be fractional.
-  ``:heartbeat``       - The heartbeat to use. Should be approx half the read_timeout.
-  ``:cacert``          - CA Cert for SSL Connections
-  ``:cert``            - Cert for SSL Connections
-  ``:key``             - Key for SSL Connections
-  ``:verify``          - Verify SSL Certs (true or false)

Default parameters
------------------

Default connection parameters are

.. code-block:: php

    [
      'host'         => "localhost",
      'port'         => 5672,
      'vhost'        => "/",
      'login'        => "guest",
      'password'     => "guest",
      'persistent'   => false,
      'connect_timeout' => 1.0,
      'read_timeout'  => 1.0,
      'write_timeout' => 1.0,
      'heartbeat' => 0,
    ]

Creating a connection
---------------------

.. code-block:: php

    $options = new Humus\Amqp\ConnectionOptions();
    $options->setLogin('username');
    $options->setPassword('password');

    $connection = new Humus\Amqp\Driver\AmqpExtension\Connection($options);
    $connection->connect();

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

    $connection = new Humus\Amqp\Driver\PhpAmqpLib\SocketConnection(new Humus\Amqp\ConnectionOptions());
    $channel    = $connection->newChannel();

Channels are typically long lived: you open one or more of them and use
them for a period of time, as opposed to opening a new channel for each
published message, for example.

Using configuration and factory
-------------------------------

You can simply configure as many connection as needed and simply give them a name. You can also set a default
connection, using the ``default_connection`` configuration key.

.. code-block:: php

    <?php

    return [
        'dependencies' => [
            'factories' => [
                Driver::class => Humus\Amqp\Container\DriverFactory::class,
                'default-amqp-connection' => [Humus\Amqp\Container\ConnectionFactory::class, 'default'],
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
                        'persistent' => true,
                        'read_timeout' => 3, //sec, float allowed
                        'write_timeout' => 1, //sec, float allowed
                    ],
                ],
            ]
        ]
    );

Getting a connection
--------------------

.. code-block:: php

    <?php

    $defaultConnection = $container->get('default-amqp-connection');


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

-  :ref:`Exchanges and Publishing <exchanges>`
-  :ref:`HumusAmqp Producer's <producers>`
-  :ref:`Queues and Consumers <queues>`
-  :ref:`Bindings <bindings>`
-  :ref:`Consumers <consumers>`
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
