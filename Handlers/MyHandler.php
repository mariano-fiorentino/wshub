<?php
namespace Handlers;

use WebSocket as WebSocket;

class MyHandler extends WebSocket\HandlerAbstract {

    public function process( WebSocket\ConnectionsPool $clients, WebSocket\Client $origin, WebSocket\Frame $data)
    {
        // send this to all the clients in the $clients array (except the first one, which is a listening socket)
        foreach ($clients as $send_sock) {
            // if its the listening sock or the client that we got the message from,
            //go to the next one in the list
            if ($send_sock == $origin) continue;
            $send_sock->write($data->buildSimpleMaskedFrame($data->getDecodedText()));
        } // end of broadcast foreach
    }
}