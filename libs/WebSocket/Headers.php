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

class Headers {

    const BLANK = " ";
    const PROTOCOL = 'HTTP/1.1';
    const ACTION_TYPE = 'Upgrade';
    const ACTION_NAME = 'Connection';
    const SEC_WEBSOCKET_ACCEPT = 'Sec-WebSocket-Accept';
    const SEC_WEBSOCKET_PROTOCOL = 'Sec-WebSocket-Protocol';
    const WS_GUUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
    const HANDSHAKE_STRING = 'Switching Protocols';
    const HANDLER_NS = '\Handlers';

    private $_inputHeaders = array();

    private $_buffer = '';

    private $_handler;
    private $_instance;

    private $_outHeaders = array(
        self::ACTION_TYPE => 'Websocket',
        self::ACTION_NAME => 'Upgrade',
        self::SEC_WEBSOCKET_ACCEPT => '',
        self::SEC_WEBSOCKET_PROTOCOL => 'chat',
    );

    public function __construct ($buffer)
    {
        $this->_inputHeaders = http_parse_headers($buffer);

        $this->_handler = self::HANDLER_NS . str_replace('/', '\\', dirname($this->_inputHeaders['Request Url']));
        $this->_instance = basename($this->_inputHeaders['Request Url']);
    }

    public function getHandler()
    {
        return $this->_handler;
    }

    public function getInstance()
    {
        return $this->_instance;
    }

    public function getResponseHeaders ()
    {
        $this->_outHeaders[self::SEC_WEBSOCKET_ACCEPT] = base64_encode(
            sha1(
                $this->_inputHeaders['Sec-Websocket-Key'].self::WS_GUUID, TRUE
            )
        );

        $this->_addLine(
            self::PROTOCOL.self::BLANK."101".self::BLANK.self::HANDSHAKE_STRING
        );
        $this->_addLine(
            self::ACTION_TYPE.":".self::BLANK.$this->_outHeaders[self::ACTION_TYPE]
        );
        $this->_addLine(
            self::ACTION_NAME.":".self::BLANK.$this->_outHeaders[self::ACTION_NAME]
        );
        $this->_addLine(
            self::SEC_WEBSOCKET_ACCEPT.":".self::BLANK.$this->_outHeaders[self::SEC_WEBSOCKET_ACCEPT]
        );
        $this->_addLine(
            self::SEC_WEBSOCKET_PROTOCOL.":".self::BLANK.$this->_outHeaders[self::SEC_WEBSOCKET_PROTOCOL]
        );

        return $this->_endHandshake();
    }

    private function _addLine ($data)
    {
        $this->_buffer .= $data."\r\n";
    }

    private function _endHandshake()
    {
        return $this->_buffer."\r\n";
    }
}