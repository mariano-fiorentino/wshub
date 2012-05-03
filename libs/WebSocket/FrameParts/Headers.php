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

namespace WebSocket\FrameParts;

class Headers {

    const LENGTH_16 = 126;
    const LENGTH_16_BYTES = 2;
    const MAX_LEN_16 = 65535;

    const LENGTH_64 = 127;
    const LENGTH_64_BYTES = 8;
    const MAX_LEN_7 = 125;

    const OCTAL = 8;
    const BOOLEAN = 2;
    const DECIMAL = 10;

    const TEXT_FRAME = 1;

    private $_framePayloadLength;//   ; either 7, 7+16, or 7+64 bits in length
    private $_frameMasked;
    private $_extended = 0;

    private $_frameFin;//           ; 1 bit in length
    private $_frameRsv1;//          ; 1 bit in length
    private $_frameRsv2;//          ; 1 bit in length
    private $_frameRsv3;//          ; 1 bit in length
    private $_frameOpcode;//        ; 4 bits in length

    public function __construct() {}

    public function setFirstByte($data)
    {
        $firstByte = base_convert($data, 10, 2);
        $this->_frameFin = $firstByte[0];
        $this->_frameRsv1 = $firstByte[1];
        $this->_frameRsv2 = $firstByte[2];
        $this->_frameRsv3 = $firstByte[3];
        $this->_frameOpcode = substr($firstByte, 4);
    }

    public function setSecondByte($data)
    {
        $secondByte = base_convert($data, self::DECIMAL, self::BOOLEAN);
        $this->_frameMasked = $secondByte[0];
        $this->_framePayloadLength = base_convert(substr($secondByte, 1), self::BOOLEAN, self::DECIMAL);

        switch ($this->_framePayloadLength){

            case self::LENGTH_16:{

                $this->_extended = self::LENGTH_16_BYTES;
            }
            break;
            case self::LENGTH_64:{

                $this->_extended = self::LENGTH_64_BYTES;
            }
            break;
        }
    }

    public function getExtendedChunks()
    {
        return $this->_extended;
    }

    public function isMasked()
    {
        return $this->_frameMasked;
    }

    public function getLength()
    {
        return $this->_framePayloadLength;
    }

    public function isExtendedLength()
    {
        return $this->_framePayloadLength >= self::LENGTH_16;
    }

    public function is64BitLength()
    {
        return $this->_framePayloadLength === self::LENGTH_64;
    }

    public function setExtendedLength(Array $chunks)
    {
        $binary = '';
        foreach ($chunks as $chunk) {

            $binary .= base_convert($chunk, self::DECIMAL, self::BOOLEAN);
        }
        $this->_framePayloadLength = base_convert($binary, self::BOOLEAN, self::DECIMAL);
    }

    public function buildChunkedHeader($finalFrame, $frameType, $length)
    {
        $chunks = array();
        $hasExtension = 0;

        $this->_frameFin = $finalFrame;
        $this->_frameOpcode = str_pad(
            base_convert($frameType, self::DECIMAL, self::BOOLEAN), 4, 0, STR_PAD_LEFT
        );

        $chunks[] = bindec(
            "{$this->_frameFin}{$this->_frameRsv1}{$this->_frameRsv2}{$this->_frameRsv3}{$this->_frameOpcode}"
        );

        if ($length >= self::MAX_LEN_16) {

            $payloadLength = self::LENGTH_64;
            $hasExtension = self::LENGTH_64_BYTES;

        } else if ($length >= self::MAX_LEN_7) {

            $payloadLength = self::LENGTH_16;
            $hasExtension = self::LENGTH_16_BYTES;
        } else {

            $payloadLength = $length;
        }

        $secondPart = str_pad(
            base_convert($payloadLength, self::DECIMAL, self::BOOLEAN),
        7, 0, STR_PAD_LEFT);

        $chunks[] = bindec($this->isMasked() . "$secondPart");

        if ($hasExtension) {

            $extension = str_pad(
                base_convert($length, self::DECIMAL, self::BOOLEAN),
                $hasExtension, 0, STR_PAD_LEFT
            );

            $bytes = str_split($extension, self::OCTAL);
            foreach ($bytes as $byte) {

                $chunks[] = bindec($byte);
            }
        }
        return $chunks;
    }
}