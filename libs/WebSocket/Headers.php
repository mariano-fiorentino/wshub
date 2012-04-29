<?php
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

    private $_outHeaders = array(
        self::ACTION_TYPE => 'Websocket',
        self::ACTION_NAME => 'Upgrade',
        self::SEC_WEBSOCKET_ACCEPT => '',
        self::SEC_WEBSOCKET_PROTOCOL => 'chat',
    );

    public function __construct ($buffer)
    {
        $this->_inputHeaders = http_parse_headers($buffer);
    }

    public function getHandler()
    {
        return self::HANDLER_NS . str_replace('/','\\',$this->_inputHeaders['Request Url']);
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