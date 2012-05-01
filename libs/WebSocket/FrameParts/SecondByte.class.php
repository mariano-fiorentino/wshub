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