<?php
/**
* Web Socket Manager
*
* @class        Manager
* @package      WebSocket
* @version      $Id$
* @author       [MF]
*/
namespace WebSocket;

use Handlers as Handlers;

class Manager {

    const BYTE_READS = '2048';

    private $sockets;
    private $master;
    private $readersMap;
    private $host = 'localhost';
    private $port = 10100;
    private $maxConnection = 50;
    private $polling = 5;
    private $handlersAvailable = array();

    const HANDLERS_NS = '\Handlers\\';

    /**
    * Setup
    */
    public function __construct(Array $conf, Array $handlerConf)
    {
        if (isset($conf['host'])) {
            $this->host = $conf['host'];
        }

        if (isset($conf['port'])) {
            $this->port = $conf['port'];
        }

        if ($conf['maxConnection']) {
            $this->maxConnection = $conf['maxConnection'];
        }

        if ($conf['polling']) {
            $this->polling = $conf['polling'];
        }

        $this->master = $this->__createMaster($this->host, $this->port);
        $this->sockets[(int)$this->master] = $this->master;
        $this->readersMap = array();//new SplObjectStorage();

        $iterator = new \DirectoryIterator($handlerConf['path']);
        foreach ($iterator as $fileinfo) {

            if ($fileinfo->isFile()) {

                require_once ($handlerConf['path'] . '/' .$fileinfo->getFilename());
                $this->handlersAvailable[] = self::HANDLERS_NS . substr(
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
        socket_listen($this->master, $this->maxConnection);

        while (true) {

            $read = $this->sockets;
            if (socket_select($read, $_w =  NULL, $_e = NULL, $this->polling) < 1) continue;

            if (in_array($this->master, $read)) {

                $read = $this->__connect($read);
            }
            foreach ($read as $active) {

                $read_sock = $this->readersMap[(int)$active]->getClient($active);
                // check if the client is disconnected
                if ($read_sock->eof()) {

                    $this->__disconnect($read_sock);
                    continue;
                }
                // read buffer
                if ($data = new Frame($read_sock->read(self::BYTE_READS))) {

                      $this->readersMap[(int)$active]->dispatch($read_sock, $data);
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
        $newsock = socket_accept($this->master);
        $newUser = new Client($newsock);
        $this->sockets[(int)$newsock] = $newsock;

        $objHeader = new Headers($newUser->read(self::BYTE_READS));
        $handler = $objHeader->getHandler();

        if (in_array($handler, $this->handlersAvailable) && $newUser->write($objHeader->getResponseHeaders())) {

            $newUser->handshakeDone();
            $this->readersMap[(int)$newsock] = $handler::getInstance($newsock, $newUser);
            socket_getpeername($newsock, $ip);
            echo "New client connected: {$ip}\n";
            // remove the listening socket from the clients-with-data array
            unset($read[array_search($newsock, $read)]);

        } else {

            echo "Unable to handle handshake";
        }
        // remove master
        unset($read[array_search($this->master, $read)]);
        return $read;
    }

    /**
    * Remove client from pool of connections
    */
    protected function __disconnect (Client $socket)
    {
        $idx = (int)$socket->getResource();
        $this->readersMap[$idx]->removeClient($idx);
        unset($this->sockets[$idx]);
        unset($this->readersMap[$idx]);
        $socket->close();
        echo "client disconnected.\n";
    }
}