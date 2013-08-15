<?php

$config = array(
	"default" => array(
		"hostname" => "127.0.0.1",
		"port" => 11211,
		"weight" => 1,
	),
	"socket" => array(
		"hostname" => FCPATH.'/memcached.sock',
		"port" => 0,
		"weight" => 2,
	),
);


?>
