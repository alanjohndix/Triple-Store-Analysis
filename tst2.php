<?php
$line = "hello: world";
$res = preg_match( '/^(\\w+)[:\\s](.*)$/', $line, $match );
echo "res = $res \n";
echo "match = ".print_r($match,1)." \n";
?>
