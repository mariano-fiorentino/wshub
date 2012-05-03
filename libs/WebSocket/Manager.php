<?php
/**
 * WsHub
 * Copyright 2012 Mariano Fiorentino <mariano.fiorentino at gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Library General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public
 * License along with this program; if not, write to the
 * Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 */

/**
 * Web Socket Manager
 * Manage new connection and dispach to handlers
 *
 * @class        Manager
 * @package      WebSocket
 * @version      $Id$
 * @author       [MF]
 */

/**
 * @namespace
 */
namespace WebSocket;

use Handlers as Handlers;

class Manager {

    const BYTE_READS = '2048';

    /**
    * Listening socket
    */
    private $_master;

    /**
    * host
    */
    private $_host = 'localhost';

    /**
    * Destination port
    */
    private $_port = 10100;

    /**
    * Global max connections
    */
    private $_maxConnection = 50;

    /**
    * Time interval for checking new data
    */
    private $_polling = 5;

    /**
    * List of available Handlers
    */
    private $_handlersAvailable = array();

    /**
     * List of connected sockets
     *
     * @var array
     */
    private $_sockets  = array();

    /**
    * Maps instance/Handlers
    */
    private $_nodes;//private $_nodes = array();
    private $_readers;

    const HANDLERS_NS = '\Handlers\\';

    /**
    * Setup
    */
    public function __construct(Array $conf, Array $handlerConf)
    {
        if (isset($conf['host'])) {
            $this->_host = $conf['host'];
        }

        if (isset($conf['port'])) {
            $this->_port = $conf['port'];
        }

        if ($conf['maxConnection']) {
            $this->_maxConnection = $conf['maxConnection'];
        }

        if ($conf['polling']) {
            $this->_polling = $conf['polling'];
        }

        $this->_master = $this->__createMaster($this->_host, $this->_port);
        $this->_sockets[(int)$this->_master] = $this->_master;
        $this->_readers = array();
        $this->_nodes = array();

        $iterator = new \DirectoryIterator($handlerConf['path']);
        foreach ($iterator as $fileinfo) {

            if ($fileinfo->isFile()) {

                require_once ($handlerConf['path'] . '/' .$fileinfo->getFilename());
                $this->_handlersAvailable[] = self::HANDLERS_NS . substr(
                    $fileinfo->getFilename(),
                    0,
                    strpos($fileinfo->getFilename(), '.')
                );
            }
        }
    }

    /**
    * Create master socket
    */
    protected function __createMaster ($host, $port)
    {
        $master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($master, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($master, $host, $port);
        return $master;
    }

    /**
    * Listen for a new status of connected sockets
    */
    public function listen()
    {
        // Listen for connections
        socket_listen($this->_master, $this->_maxConnection);

        while (true) {

            $read = $this->_sockets;
            if (socket_select($read, $_w =  NULL, $_e = NULL, $this->_polling) < 1) continue;

            if (in_array($this->_master, $read)) {

                $read = $this->__connect($read);
            }
            foreach ($read as $active) {

                $instance = $this->_readers[(int)$active];
                $read_sock = $instance->getClient($active);
                // check if the client is disconnected
                if ($read_sock->eof()) {

                    $this->__disconnect($read_sock);
                    continue;
                }
                // read buffer
                if ($data = new Frame($read_sock->read(self::BYTE_READS))) {

                      $instance->dispatch($read_sock, $data);
                }
            } // end of reading foreach
        }
    }

    /**
    * Add client to pool of connections
    */
    protected function __connect (Array $read)
    {
        // accept the client, and add him to the $clients array
        $newsock = socket_accept($this->_master);
        $newUser = new Client($newsock);
        $this->_sockets[(int)$newsock] = $newsock;

        $objHeader = new Handshake($newUser->read(self::BYTE_READS));
        $handler = $objHeader->getHandler();
        $instance = $objHeader->getInstance();

        if (in_array($handler, $this->_handlersAvailable) && $newUser->write($objHeader->getResponseHeaders())) {

            if (isset($this->_nodes[$handler.'/'.$instance])) {

                $handlerObj = $this->_nodes[$handler.'/'.$instance]->getInstance($newsock, $newUser);
            } else {

                $handlerObj = new $handler($instance, $newsock, $newUser);
                $this->_nodes[$handler.'/'.$instance] = $handlerObj;
            }

            $this->_readers[(int)$newsock] = $handlerObj;
            $newUser->handshakeDone();
            socket_getpeername($newsock, $ip);
            echo "New client connected: {$ip}\n";
            // remove the listening socket from the clients-with-data array
            unset($read[array_search($newsock, $read)]);

        } else {

            echo "Unable to handle handshake";
        }
        // remove master
        unset($read[array_search($this->_master, $read)]);
        return $read;
    }

    /**
    * Remove client from pool of connections
    */
    protected function __disconnect (Client $socket)
    {
        $idx = (int)$socket->getResource();
        $this->_readers[$idx]->removeClient($idx);
        unset($this->_sockets[$idx]);
        unset($this->_readers[$idx]);
        $socket->close();
        echo "client disconnected.\n";
    }
}