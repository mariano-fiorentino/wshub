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

class Client {

    private $fd;
    private $handshake = false;
    private $name;
    private $buffer = '';

    public function __construct ($fd)
    {
        $this->fd = $fd;
    }

    public function setName ($string)
    {
        $this->name = $string;
    }

    public function read($length)
    {
        return fread($this->fd, $length);
    }

    public function write($msg)
    {
        return fwrite($this->fd, $msg, strlen($msg));
    }

    public function close()
    {
        fclose($this->fd);
    }

    public function eof()
    {
        return feof($this->fd);
    }

    public function getResource()
    {
        return $this->fd;
    }

    /**
    * Check if client need handshake
    */
    public function needHandshake()
    {
        return !$this->handshake;
    }

    /**
    * handshake
    */
    public function handshakeDone()
    {
        $this->handshake = true;
    }
}