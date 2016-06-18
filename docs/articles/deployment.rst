.. _deployment:

Deployment Strategies
=====================

Shut down the system, update and restart
----------------------------------------

While the easiest way to deploy a RabbitMQ environment is to just destroy the old one and create a new one,
that's not always possible. Sometimes the consumers are not part of your application or it's complete unacceptable
to put the payment system offline and not to process messages even for half an hour, things get more complicated.

This guide tries to show some different deployment strategies. Note, that there is no general way to do this, it will
always depend on the use-case you have.


Create a new node and switch configuration
------------------------------------------

Let's say you have a running rabbitmq configuration on a given node (or vhost). An easy way to update configuration
would be to just deploy a new node (or vhost) and switch you application configuration to use that new node (vhost)
instead of the old one.

Message-Versioning
------------------

Message-Versioning with Routing Keys
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

You can use the routing keys for versioning, like this:


.. code-block:: php

    <?php

    $producer->publish('some message', 'v1.0.0');

Then you just bind the queue to the routing key. When you update your system, newer messages (e.g. 2.0.0) are
not delivered to queue that is not able to process them. On the other hand you can write consumers, that are
backwards-compatible, so the queues used will bind to a variety of routing keys (v1.0.0 & v2.0.0 or v1.0.*).

.. note:: Use `semantic versioning <http://semver.org/>`_.

Message-Versioning with extra Attributes
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Use some custom message headers to specify version constraints:


.. code-block:: php

    <?php

    $attribs = new MessageAttributes();
    $attribs->setHeaders(array(
        'x-version' => 'v1.0.0'
    ));
    $producer->publish('some message', '', $attribs);

This way a message with a version not handled by your consumer, will also be at least delivered to him. The consumer
will then need to check for the x-version header and process if possible or throw an exception. Compared to the
versioning strategy with routing keys, you'll always know, that somebody is sending still messages in old
version, as you see the exceptions in the log file. If you just deploy a consumer that is only able to acknowlegde
messages of e.g. v2.0.0, than it's hard to recognize that there are still messages in version 1.0.0 going through
the system. On the other hand, there are messages send over network, that nobody cares, so routing keys are often
preferable.

Updating Exchanges
------------------

Most times it's not neccessary to update an exchange configuration. However if required, you have to remove the old
exchange before you can redeclare the new one with the same name. Keep in mind, that when you put an exchange down,
no messages can be delivered any more. It's also possible to use an exchange name like: "update-stuff-v1-0-0", so you
don't have to deleted the old exchange when deploying the new one.

Updating Queues
---------------

Updating queues can be sometimes a little more tricky then updating an exchange. If you bind the new queue before the
old one is destroyed, you'll get duplicated messages in your system. But if this is done in concert with a new exchange
you can prevent duplicate messages.

Getting queues empty first
--------------------------

Sometimes you might want to upgrade the consumers because of a new message format and you don't want to maintain
backwards compatibility. If you don't want to lose any messages, stop all producers first, untill the queues are empty,
then you can to the switch without losing messages.

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
