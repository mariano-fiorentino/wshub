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

class FirstByte {

    private $frameFin;//           ; 1 bit in length
    private $frameRsv1;//          ; 1 bit in length
    private $frameRsv2;//          ; 1 bit in length
    private $frameRsv3;//          ; 1 bit in length
    private $frameOpcode;//        ; 4 bits in length

    public function __construct($data)
    {

        $firstByte = base_convert($data, 10, 2);

        $this->frameFin = $firstByte[0];
        $this->frameRsv1 = $firstByte[1];
        $this->frameRsv2 = $firstByte[2];
        $this->frameRsv3 = $firstByte[3];
        $this->frameOpcode = substr($firstByte, 4);
    }
}