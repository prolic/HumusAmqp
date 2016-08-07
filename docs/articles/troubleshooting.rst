.. _troubleshooting:

Troubleshooting
===============

First steps
-----------

Whenever something doesn't work, check the following things before asking on the mailing list:

 - RabbitMQ log.
 - List of users in a particular vhost you are trying to connect.
 - Network connectivity, firewall settings, DNS host resolution.

Inspecting RabbitMQ log file
----------------------------

In this section we will cover typical problems that can be tracked down by reading RabbitMQ log.

RabbitMQ logs abrupt TCP connection failures, timeouts, protocol version mismatches and so on.
If you are running RabbitMQ, log file location depends on the operating systems and installation method.
See `RabbitMQ installation guide <https://www.rabbitmq.com/download.html>`_ for more information.

OS X with Homebrew
~~~~~~~~~~~~~~~~~~

On Mac OS X, RabbitMQ installed via Homebrew logs to $HOMEBREW_HOME/var/log/rabbitmq/rabbit@$HOSTNAME.log.
For example, if you have Homebrew installed at ``/usr/local`` and your hostname is giove, the log will be
at ``/usr/local/var/log/rabbitmq/rabbit@giove.log``.

Authentication Failures
~~~~~~~~~~~~~~~~~~~~~~~

Here is what authentication failure looks like in a RabbitMQ log:

.. code-block:: bash

    =ERROR REPORT==== 12-Jul-2013::16:49:03 ===
    closing AMQP connection <0.31567.1> (127.0.0.1:50458 -> 127.0.0.1:5672):
    {handshake_error,starting,0,
                     {amqp_error,access_refused,
                                 "PLAIN login refused: user 'pipeline_agent' - invalid credentials",
                                 'connection.start_ok'}}

This means that the connection attempt with the username pipeline_agent failed because the credentials
were invalid. If you are seeing this message, make sure username, password and vhost are correct.

The following entry:

.. code-block:: bash

    =ERROR REPORT==== 17-May-2011::17:26:28 ===
    exception on TCP connection <0.4201.62> from 10.8.0.30:57990
    {bad_header,<<65,77,81,80,0,0,9,1>>}

means that an old RabbitMQ version (pre-2.0) is used. Those versions are not supported by HumusAmqp.
It is recommended to use the latest stable release.

Handling Channel-level Exceptions
---------------------------------

A broad range of problems result in AMQP channel exceptions: an indication by the broker that there was an
issue that the application needs to be aware of. Channel-level exceptions are typically not fatal and can
be recovered from. Some examples are:

 - Exchange is re-declared with attributes different from the original declaration. For example, a non-durable
   exchange is being re-declared as durable.
 - Queue is re-declared with attributes different from the original declaration. For example, an auto-deletable
   queue is being re-declared as non-auto-deletable.
 - Queue is bound to an exchange that does not exist.

and so on. These will result in a reasonably descriptive exception ``Humus\Amqp\Exception\ChannelException``.
Handling and logging them will likely reveal an issue when it arises.

Network connection issues
-------------------------

Testing Network Connection with RabbitMQ using Telnet
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

One simple way to check network connection between a particular network node and a RabbitMQ node is to use telnet:

.. code-block:: bash

    telnet [host or ip] 5672

then enter any random string of text and hit Enter. RabbitMQ should immediately close down the connection.
Here is an example session:

.. code-block:: bash

    telnet localhost 5672
    Connected to localhost.
    Escape character is '^]'.
    adjasd
    AMQP    Connection closed by foreign host.

If Telnet exits after printing instead

.. code-block:: bash

    telnet: connect to address [host or ip]: Connection refused
    telnet: Unable to connect to remote host

then the connection between the machine that you are running Telnet tests on and RabbitMQ fails.
This can be due to many different reasons, but it is a good idea to check these two things first:

 - Firewall configuration for port 5672 or 5671 (if TLS/SSL is used)
 - DNS resolution (if hostname is used)

Connecting to localhost on VPN
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Using VPN almost certainly changes your DNS server configuration which may affect connections to ``localhost``
as well as to remote hosts. If you keep getting
``Got an exception when receiving data: IO timeout when reading 7 bytes (Timeout::Error)``
errors and you're on VPN try switching VPN off.

RabbitMQ Startup Issues
-----------------------

Missing erlang-os-mon on Debian and Ubuntu
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The following error on RabbitMQ startup on Debian or Ubuntu

.. code-block:: bash

    ERROR: failed to load application os_mon: {"no such file or directory","os_mon.app"}

suggests that the erlang-os-mon package is not installed.

asn1 Issue with Erlang R16B01
-----------------------------

.. code-block:: bash

    BOOT FAILED
    ===========

    Error description:
        {error,{cannot_start_application,public_key,{not_started,asn1}}}

is an issue in RabbitMQ 3.1 on Erlang R16B01+. It is resolved in RabbitMQ 3.1.2 and later versions.

What to Read Next
-----------------

The documentation is organized as :ref:`a number of guides <guides>`, covering various topics.

We recommend that you read the following guides first, if possible, in
this order:

-  :ref:`Deployment <deployment>`

Tell Us What You Think!
-----------------------

Please take a moment to tell us what you think about this guide: `Send an e-mail <saschaprolic@googlemail.com>`_,
say hello in the `HumusAmqp gitter <https://gitter.im/prolic/HumusAmqp>`_ chat.
or raise an issue on `Github <https://www.github.com/prolic/HumusAmqp/issues>`_.

Let us know what was unclear or what has not been covered. Maybe you
do not like the guide style or grammar or discover spelling
mistakes. Reader feedback is key to making the documentation better.
