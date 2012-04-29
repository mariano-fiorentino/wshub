<?php
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
Hello

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

    private $framePayloadData = array();//     ; n*8 bits in length
    private $hexData = array();
    private $frameMask;
    private $dataOffset = 2;
    private $decodedText = '';
    private $binaryData;

    const MASK_LENGTH = 4;

    public function __construct($data)
    {
        $this->binaryData = $data;
        $this->hexData = $this->_getMatrixData($data);

        $this->firstByte = new Part\FirstByte($this->hexData[0]);
        $this->secondByte = new Part\SecondByte($this->hexData[1]);

        if($this->secondByte->isMasked()) {

            $this->frameMask = new Part\Mask(array_slice($this->hexData ,$this->dataOffset, self::MASK_LENGTH));
            $this->dataOffset += self::MASK_LENGTH;
        }

        $this->framePayloadData = array_slice(
            $this->hexData,
            $this->dataOffset,
            $this->secondByte->getLength()
        );
        foreach ($this->framePayloadData as $idx => $char) {

            $this->decodedText .= chr($this->encodeDecodeMask($char, $this->frameMask->getMask(), $idx));
        }
    }

    private function _getMatrixData($data)
    {
        $hex = bin2hex($data);
        preg_match_all("/\w{2}/", $hex, $result);
        foreach ($result[0] as &$res){

            $res = (int)base_convert($res,16,10);
        }
        return $result[0];
    }

    public function buildSimpleMaskedFrame($text)
    {
        $frame = '';
        $frame .=  $this->_chatOutput(
            bindec("10000001")
        );
        $length = str_pad(
            base_convert(strlen($text), 10, 2),
        7, 0, STR_PAD_LEFT);

        $frame .= $this->_chatOutput(
            bindec($this->secondByte->isMasked() . "$length")
        );

        if ("1" === $this->secondByte->isMasked()) {

            foreach ($this->frameMask->getMask() as $mask) {

                $frame .= $this->_chatOutput($mask);
            }
        }
        foreach (str_split($text) as $idx => $char) {

            $masked = $this->encodeDecodeMask(ord($char), $this->frameMask->getMask(), $idx);
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
        return $this->binaryData;
    }

    public function getDecodedText()
    {
        return $this->decodedText;
    }

    public function _chatOutput($data)
    {
        return chr($data);
    }
}