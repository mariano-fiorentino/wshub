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
    * List of available Handlers
    */
    private $_handlersAvailable = array();

    /**
     * List of connected sockets
     *
     * @var array
     */
    private $_sockets = array();

    /**
    * Maps instance/Handlers
    */
    private $_nodes = array();//private $_nodes = array();
    private $_readers = array();

    const HANDLERS_NS = '\Handlers\\';

    /**
    * Setup
    */
    public function __construct(Array $conf)
    {
        $this->_master =  new SocketServer($conf);
        $this->_readers = array();
        $this->_nodes = array();

        $iterator = new \DirectoryIterator($conf['handlers']['path']);
        foreach ($iterator as $fileinfo) {

            if ($fileinfo->isFile()) {

                printf('Loaded '. $conf['handlers']['path'] . '/' .$fileinfo->getFilename());

                require_once ($conf['handlers']['path'] . '/' .$fileinfo->getFilename());
                $this->_handlersAvailable[] = self::HANDLERS_NS . substr(
                    $fileinfo->getFilename(),
                    0,
                    strpos($fileinfo->getFilename(), '.')
                );
            }
        }
    }

    /**
    * Listen for a new status of connected sockets
    */
    public function listen()
    {
        $this->_master->run();
        $this->_sockets[$this->_master->getIdx()] = $this->_master->getResource();

        while (true) {

            $read = $this->_sockets;
            if (stream_select($read, $_w =  NULL, $_e = NULL, $this->_master->getPolling()) < 1) continue;

            if (in_array($this->_master->getResource(), $read)) {

                $read = $this->_connect($read);
            }
            foreach ($read as $active) {

                $instance = $this->_readers[(int)$active];
                $read_sock = $instance->getClient($active);
                // check if the client is disconnected
                if ($read_sock->eof()) {

                    $this->_disconnect($read_sock);
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
    * Handshake
    */
    protected function _connect (Array $read)
    {
        // accept the client, and add him to the $clients array
        $newsock = stream_socket_accept($this->_master->getResource());
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
            $ip = stream_socket_get_name($newsock, true);
            $newUser->setName($ip);
            echo "New client connected: {$ip}\n";
            $newUser->handshakeDone();
            // remove the listening socket from the clients-with-data array
            unset($read[array_search($newsock, $read)]);

        } else {

            echo "Unable to handle handshake";
        }
        // remove master
        unset($read[array_search($this->_master->getResource(), $read)]);
        return $read;
    }

    /**
    * Remove client from pool of connections
    */
    protected function _disconnect (Client $socket)
    {
        $idx = (int)$socket->getResource();
        $this->_readers[$idx]->removeClient($idx);
        unset($this->_sockets[$idx]);
        unset($this->_readers[$idx]);
        $socket->close();
        echo "client disconnected.\n";
    }
}