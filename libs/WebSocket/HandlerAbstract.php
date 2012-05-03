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

abstract class HandlerAbstract {

    private $_users;
    private $_instanceName;

    /**
    * La variabile statica privata che conterrà l'istanza univoca
    * della nostra classe.
    */
    //private static $instance = null;
    public function getInstance($sock, Client $socket)
    {
        $this->addClient($sock, $socket);
        return $this;
    }

    public function __construct($name, $sock, Client $user)
    {
        $this->_users =  new ConnectionsPool();
        $this->_instanceName = $name;
        $this->addClient($sock, $user);
    }

    public function addClient($sock, Client $user)
    {
        $this->_users->addClient($sock, $user);
    }

    public function setInstanceName($name)
    {
        $this->_instanceName = $name;
    }

    public function getInstanceName()
    {
        return $this->_instanceName;
    }

    public function removeClient($idx)
    {
        $this->_users->offsetUnset($idx);
    }

    public function getClient($resource)
    {
        return $this->_users->offsetGet((int)$resource);
    }

    public function dispatch (Client $origin, Frame $data)
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            echo 'could not fork';
        } else if ($pid) {
            //Protect against Zombie children
            pcntl_wait($status);
        } else {
            // we are the child
            $this->process($this->_users, $origin, $data);
            exit(0);
        }
    }

    abstract function process(ConnectionsPool $clients, Client $origin, Frame $data);
}