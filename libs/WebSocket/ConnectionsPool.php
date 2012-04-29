<?php
namespace WebSocket;

class ConnectionsPool extends \ArrayObject {

    public function addClient ($index, Client $client)
    {
        parent::offsetSet((int)$index, $client);
    }
}