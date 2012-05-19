<?php
/**
 * WsHub
 * Copyright 2012 Mariano Fiorentino <mariano.fiorentino NOSPAMat gmail.com>
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

namespace WebSocket;

class SocketServer {

    private $_socket;

    private $_ctx;

    /**
    * host
    */
    private $_host = '0.0.0.0';

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

    public function __construct (Array $config)
    {

        if (isset($config['server']['host'])) {
            $this->_host = $config['server']['host'];
        }

        if (isset($config['server']['port'])) {
            $this->_port = $config['server']['port'];
        }

        if ($config['server']['maxConnection']) {
            $this->_maxConnection = $config['server']['maxConnection'];
        }

        if ($config['server']['polling']) {
            $this->_polling = $config['server']['polling'];
        }

        $opts = array(
            'socket' => array(
                'backlog' => $this->_maxConnection
            )
        );
        if (isset($config['ssl'])) {

            $opts = array('ssl'=>array(
                'local_cert' => $config['ssl']['local_cert'],
                'passphrase' => $config['ssl']['passphrase'],
                'allow_self_signed' => $config['ssl']['allow_self_signed'],
                'verify_peer' => $config['ssl']['verify_peer']
            ));
        }
        $this->_ctx = stream_context_create($opts);
    }

    public function run()
    {
        $this->_socket = stream_socket_server(
            $this->getLocalSocket(),
            $errno,
            $errstr,
            STREAM_SERVER_BIND|STREAM_SERVER_LISTEN,
            $this->_ctx
        );
        return $this->_socket;
    }

    public function getLocalSocket()
    {
        return "{$this->_host}:{$this->_port}";
    }

    public function getPolling()
    {
        return $this->_polling;
    }

    public function getResource()
    {
        return $this->_socket;
    }

    public function getIdx()
    {
        return (int)$this->_socket;
    }
}