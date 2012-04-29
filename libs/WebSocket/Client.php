<?php
namespace WebSocket;

class Client {

    private $fd;
    private $handshake = false;

    private $buffer = '';

    public function __construct ($fd)
    {
        $this->fd = $fd;
    }

    public function read($length, $type = PHP_BINARY_READ)
    {
        return socket_read ($this->fd, $length, $type);
    }

    public function write($msg)
    {
       return socket_write($this->fd, $msg, strlen($msg));
    }

    public function close()
    {
       socket_close($this->fd);
    }

    public function eof()
    {
        return !socket_recv ($this->fd, $buf = NULL , 1, MSG_PEEK);
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