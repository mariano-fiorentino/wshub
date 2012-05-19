<?php
namespace WebSocket;

interface Stream {

    function read($length);

    function write($msg);

    function close();

    function eof();

    function needHandshake();

    function handshakeDone();

    function setName ($string);
}