<?php
namespace WebSocket;

abstract class HandlerAbstract {

    private $users;

    /**
    * La variabile statica privata che conterrà l'istanza univoca
    * della nostra classe.
    */
    private static $instance = null;

    public function __construct()
    {
        $this->users =  new ConnectionsPool();
    }

    protected function _addClient($sock, Client $user)
    {
        $this->users->addClient($sock, $user);
    }

    public function removeClient($idx)
    {
        $this->users->offsetUnset($idx);
    }

    public function getClient($resource)
    {
        return $this->users->offsetGet((int)$resource);
    }

    public static function getInstance($sock, Client $socket)
    {
        if(self::$instance == null){

            $class =  get_called_class();
            self::$instance = new $class();
        }
        self::$instance->_addClient($sock, $socket);
        return self::$instance;
    }

    public function dispatch (Client $origin, Frame $data)
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            die('could not fork');
        } else if ($pid) {
            // we are the parent
            pcntl_wait($status); //Protect against Zombie children
        } else {
            // we are the child
            $this->process($this->users, $origin, $data);
            exit(0);
        }
    }

    abstract function process(ConnectionsPool $clients, Client $origin, Frame $data);
}