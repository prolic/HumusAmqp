<?php
/**
 * Copyright (c) 2016. Sascha-Oliver Prolic <saschaprolic@googlemail.com>.
 *
 *  THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 *  "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 *  LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 *  A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 *  OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 *  SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 *  LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 *  DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 *  THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 *  (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 *  OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *  This software consists of voluntary contributions made by many individuals
 *  and is licensed under the MIT license.
 */
declare(strict_types=1);

namespace  Humus\Amqp;

/**
 * Represents a AMQP connection between PHP and a AMQP server.
 *
 * Interface Connection
 */
interface Connection
{
    /**
     * Check whether the connection to the AMQP broker is still valid.
     *
     * It does so by checking the return status of the last connect-command.
     *
     * @return bool True if connected, false otherwise
     */
    public function isConnected() : bool;

    /**
     * Establish a transient connection with the AMQP broker.
     *
     * This method will initiate a connection with the AMQP broker.
     *
     * @return bool TRUE on success or throw an exception on failure
     */
    public function connect();

    /**
     * Closes the transient connection with the AMQP broker.
     *
     * This method will close an open connection with the AMQP broker.
     *
     * @return bool true if connection was successfully closed, false otherwise
     */
    public function disconnect();

    /**
     * Close any open transient connections and initiate a new one with the AMQP broker.
     *
     * @return bool TRUE on success or FALSE on failure
     */
    public function reconnect() : bool;

    /**
     * @return ConnectionOptions
     */
    public function getOptions() : ConnectionOptions;

    /**
     * @return mixed
     */
    public function getResource();

    /**
     * @return Channel
     */
    public function newChannel() : Channel;
}
