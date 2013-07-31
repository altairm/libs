<?php

$h = fopen('516127_usa_T32_himmelsdorf.wotreplay', 'r');
fseek($h, 8);
$headerSize = unpack('L', fread($h, 4));
$header = fread($h, reset($headerSize));
print_r(json_decode($header));
$statSize = unpack('L', fread($h, 4));
$stat = fread($h, reset($statSize));
print_r(json_decode($stat));
