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

class Mask {

    private $frameMask = array();
    const MASK_LENGTH = 4;

    public function __construct(array $array)
    {
        $this->frameMask = $array;
    }

    public function getMask()
    {
        return $this->frameMask;
    }

    public function buildChunkedMask()
    {
        for ($i = 0; $i < self::MASK_LENGTH; $i++) {

            $this->frameMask[] = rand(0,255);
        }
        return $this->frameMask;
    }
}