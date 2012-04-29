<?php
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