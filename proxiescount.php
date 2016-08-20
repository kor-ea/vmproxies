<?php
$memcache = new Memcache;
$memcache->connect('localhost', 11211) or die ("Could not connect");

echo count($memcache->get('all'));
//var_dump($memcache->get('us'));

?>


