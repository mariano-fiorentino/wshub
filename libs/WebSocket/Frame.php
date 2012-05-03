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

/** RFC 6455

frame-fin           ; 1 bit in length
frame-rsv1          ; 1 bit in length
frame-rsv2          ; 1 bit in length
frame-rsv3          ; 1 bit in length
frame-opcode        ; 4 bits in length
frame-masked        ; 1 bit in length
frame-payload-length   ; either 7, 7+16, or 7+64 bits in length
frame-payload-data     ; n*8 bits in length

Octet i of the transformed data ("transformed-octet-i") is the XOR of
octet i of the original data ("original-octet-i") with octet at index
i modulo 4 of the masking key ("masking-key-octet-j"):

j = i MOD 4

transformed-octet-i = original-octet-i XOR masking-key-octet-j

original-octet-i = transformed-octet-i XOR masking-key-octet-j


8185d47a594f9c1f3523bb
Helloframes of this message follow

x81 x85
1000 0001 1000 0101


Mask
xd4 x7a x59 x4f


x9c x1f x35 x23 xbb



$key = array(
    0xd4, 0x7a, 0x59, 0x4f
);

$data = array(
    0x9c, 0x1f, 0x35, 0x23, 0xbb
);


foreach ($data as $i => $char) {

    $res = $char ^ $key[$i % 4];
    var_dump(chr($res));
}

*/
namespace WebSocket;

use WebSocket\FrameParts as Part;

class Frame {

    private $_framePayloadData = array();
    private $_chunks = array();
    private $_frameMask;
    private $_dataOffset = 2;
    private $_decodedText = '';
    private $_binaryData;

    const MASK_LENGTH = 4;
    const TEXT_FRAME = 1;
    const FINAL_FRAME = 1;

    public function __construct($data)
    {
        $this->headers = new Part\Headers();

        $this->_binaryData = $data;
        $this->_chunks = $this->_getChunkedData($data);

        $this->headers->setFirstByte($this->_chunks[0]);
        $this->headers->setSecondByte($this->_chunks[1]);

        if ($this->headers->isExtendedLength()) {

            $this->headers->setExtendedLength(
                array_slice($this->_chunks ,$this->_dataOffset, $this->headers->getExtendedChunks())
            );
            $this->_dataOffset += $this->headers->getExtendedChunks();
        }

        if($this->headers->isMasked()) {

            $this->_frameMask = new Part\Mask(array_slice($this->_chunks ,$this->_dataOffset, self::MASK_LENGTH));
            $this->_dataOffset += self::MASK_LENGTH;
        }

        $this->_framePayloadData = array_slice(
            $this->_chunks,
            $this->_dataOffset,
            $this->headers->getLength()
        );
        foreach ($this->_framePayloadData as $idx => $char) {

            $this->_decodedText .= chr($this->encodeDecodeMask($char, $this->_frameMask->getMask(), $idx));
        }
    }

    private function _getChunkedData($data)
    {
        $result = str_split($data);
        foreach ($result as &$res){
            $res = ord($res);
        }
        return $result;
    }

    public function buildSimpleMaskedFrame($text)
    {
        $frame = '';
        //Hello Zvika,
        //this problem happen usually when we have high load on apache, the only strange
        $bytes = $this->headers->buildChunkedHeader(
            self::FINAL_FRAME,
            self::TEXT_FRAME,
            strlen($text)
        );
        foreach ($bytes as $byte) {

            $frame .= $this->_chatOutput($byte);
        }
        if ("1" === $this->headers->isMasked()) {

            foreach ($this->_frameMask->getMask() as $mask) {

                $frame .= $this->_chatOutput($mask);
            }
        }
        foreach (str_split($text) as $idx => $char) {

            $masked = $this->encodeDecodeMask(ord($char), $this->_frameMask->getMask(), $idx);
            $frame .= $this->_chatOutput($masked);
        }
        return $frame;
    }

    public function encodeDecodeMask($char, $keys, $idx)
    {
        return $char ^ $keys[$idx % self::MASK_LENGTH];
    }

    public function getOriginalData()
    {
        return $this->_binaryData;
    }

    public function getDecodedText()
    {
        return $this->_decodedText;
    }

    protected function _chatOutput($data)
    {
        return chr($data);
    }
}