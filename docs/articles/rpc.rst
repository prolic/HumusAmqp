.. _rpc:

JSON RPC Server & Client
========================

Setup JSON RPC Server
---------------------

Assuming you want to use the provided interop-factories, let's start with a sample configuration:

A sample configuration might look like this, more details an explanation will be in the coming chapters.

.. code-block:: php

    return [
        'dependencies' => [
            'factories' => [
                Driver::class => Container\DriverFactory::class,
                'default-amqp-connection' => [Container\ConnectionFactory::class, 'default'],
                'demo-rpc-server' => [Container\JsonRpcServerFactory::class, 'demo-rpc-server'],
                'timestwo' => function () {
                    return function (\Humus\Amqp\JsonRpc\Request $request): \Humus\Amqp\JsonRpc\Response {
                        return \Humus\Amqp\JsonRpc\JsonRpcResponse::withResult($request->id(), $request->params() * 2);
                    };
                },
            ],
        ],
        'humus' => [
            'amqp' => [
                'driver' => 'amqp-extension',
                'exchange' => [
                    'demo-rpc-server' => [
                        'name' => 'demo-rpc-server',
                        'type' => 'direct',
                        'connection' => 'default-amqp-connection',
                    ],
                ],
                'queue' => [
                    'demo-rpc-server' => [
                        'name' => 'demo-rpc-server',
                        'exchanges' => [
                            'demo-rpc-server' => [],
                        ],
                        'connection' => 'default-amqp-connection',
                    ],
                ],
                'connection' => [
                    'default-amqp-connection' => [
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
                'json_rpc_server' => [
                    'demo-rpc-server' => [
                        'callback' => 'timestwo',
                        'queue' => 'demo-rpc-server',
                        'auto_setup_fabric' => true,
                    ],
                ],
            ],
        ],
    ];

So what's important here? The JSON RPC Server needs an exchange and a queue. All messages routed to the exchange, will
get routed to the server queue. In this example we use a direct exchange and a single queue for the server. This is the
pretty much simplest setup we can have.

The second this is, the server needs a callback, we use `timestwo` here. As you can see in the dependencies setup, the
callback is simply turning a Request into a Response like this:

.. code-block:: php

    function (\Humus\Amqp\JsonRpc\Request $request): \Humus\Amqp\JsonRpc\Response {
        return \Humus\Amqp\JsonRpc\JsonRpcResponse::withResult($request->id(), $request->params() * 2);
    }

For the callback function consider this:

- Return an instance of `Humus\Amqp\JsonRpc\Response`
- Just return some value, the server will automatically wrap the result with an instance of `Humus\Amqp\JsonRpc\JsonRpcResponse`
- All thrown exceptions will return an error response to the client

The `Humus\Amqp\JsonRpc\Request` also has a method named `method()` - this allows you to a single callback to return
different results, based on the method. For example:

.. code-block:: php

    function (\Humus\Amqp\JsonRpc\Request $request): \Humus\Amqp\JsonRpc\Response {
        switch ($request->method()) {
            case 'times2':
                return \Humus\Amqp\JsonRpc\JsonRpcResponse::withResult($request->id(), $request->params() * 2);
            case 'times3:
                return \Humus\Amqp\JsonRpc\JsonRpcResponse::withResult($request->id(), $request->params() * 3);
            case 'plus5':
                return \Humus\Amqp\JsonRpc\JsonRpcResponse::withResult($request->id(), $request->params() + 5);
            default:
                return \Humus\Amqp\JsonRpc\JsonRpcResponse::withError($request->id(), new \Humus\Amqp\JsonRpc\JsonRpcError(32601));
        }
    }

Running JSON-RPC servers
------------------------

To start a JSON-RPC server

.. code-block:: bash

    $ ./vendor/bin/humus-amqp json_rpc_server -n demo-rpc-server -a 100

This will start the `demo-rpc-server` and consume 100 messages until if stops or times out.

Set up JSON RPC Client
----------------------

Again, let's start with a sample configuration first (and skip the server config part, to make it easier to read):

.. code-block:: php

    return [
        'dependencies' => [
            'factories' => [
                Driver::class => Container\DriverFactory::class,
                'default-amqp-connection' => [Container\ConnectionFactory::class, 'default'],
                'demo-rpc-client' => [Container\JsonRpcClientFactory::class, 'demo-rpc-client'],
            ],
        ],
        'humus' => [
            'amqp' => [
                'driver' => 'amqp-extension',
                'exchange' => [
                    'demo-rpc-client' => [
                        'name' => 'demo-rpc-client',
                        'type' => 'direct',
                        'connection' => 'default-amqp-connection',
                    ],
                ],
                'queue' => [
                    'demo-rpc-client' => [
                        'name' => '',
                        'exchanges' => [
                            'demo-rpc-client' => [],
                        ],
                        'connection' => 'default-amqp-connection',
                    ],
                ],
                'connection' => [
                    'default-amqp-connection' => [
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
                'json_rpc_client' => [
                    'demo-rpc-client' => [
                        'queue' => 'demo-rpc-client',
                        'auto_setup_fabric' => true,
                        'exchanges' => [
                            'demo-rpc-server'
                        ],
                    ],
                ],
            ],
        ],
    ];

So what's important here: The RPC client needs also an exchange and a queue. But the important thing to note is, that
the queue has no name, an empty string is given as queue name. This will automatically create a queue with a unique name
that will get destroyed, when the client is no longer in use. Also the client needs an array of exchanges, where the client
can send messages to. In this example we use a single exchange `demo-rpc-server`.

Using the JSON RPC client
-------------------------

As an excercise, let's send two requests to our JSON RPC server and see what results we get:

.. code-block:: php

    $request1 = new \Humus\Amqp\JsonRpc\JsonRpcRequest('demo-rpc-server', 'timestwo', 1, 'request-1');
    $request2 = new \Humus\Amqp\JsonRpc\JsonRpcRequest('demo-rpc-server', 'timestwo', 2, 'request-2');

    $client->addRequest($request1);
    $client->addRequest($request2);

    $responses = $client->getResponseCollection();

    $response1 = $responses->getResponse('request-1');
    $response2 = $responses->getResponse('request-2');

    var_dump($response1->isError()); // false
    var_dump($response2->isError()); // false

    var_dump($response1->result()); // 2
    var_dump($response2->result()); // 4

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
