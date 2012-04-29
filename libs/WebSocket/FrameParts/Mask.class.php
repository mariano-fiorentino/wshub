<?php
namespace WebSocket\FrameParts;

class Mask {

    private $frameMask = array();

    public function __construct(array $array)
    {
        $this->frameMask = $array;
    }

    public function getMask()
    {
        return $this->frameMask;
    }
}