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
