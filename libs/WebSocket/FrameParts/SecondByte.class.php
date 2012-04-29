<?php
namespace WebSocket\FrameParts;

class SecondByte {

    private $_framePayloadLength;//   ; either 7, 7+16, or 7+64 bits in length
    private $_frameMasked;

    public function __construct($data)
    {
        $secondByte = base_convert($data, 10, 2);
        $this->_frameMasked = $secondByte[0];
        $this->_framePayloadLength = base_convert(substr($secondByte, 1), 2, 10);
    }

    public function isMasked()
    {
        return $this->_frameMasked;
    }

    public function getLength()
    {
        return $this->_framePayloadLength;
    }
}