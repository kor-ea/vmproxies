<?php
$memcache = new Memcache;
$memcache->connect('localhost', 11211) or die ("Could not connect");

var_dump($memcache->get('all'));
var_dump($memcache->get('us'));

?>


