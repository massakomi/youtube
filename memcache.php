<?php
$memcache_obj = new Memcache;
$memcache_obj->connect('localhost', 11211);

$cacheId = 'some_key';

$var = $memcache_obj->get($cacheId);

if (!$var) {
	$var = date('Y-m-d H:i:s');	
	$memcache_obj->set($cacheId, $var, false, 60);
}

echo '<pre>';
print_r($var);
echo '</pre>';

$memcache_obj->flush();

$memcache_obj->close();
