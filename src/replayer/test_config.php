<?php

$config = array(
	"db_host" => "localhost",
	"db_user" => "webreplay",
	"db_pass" => "123456",
	"db_name" => "webreplay_test"
);


function logthis($stuff, $from = '')
{
	global $config;

	$conn = new mysqli($config["db_host"], $config["db_user"], $config["db_pass"], $config["db_name"]);

	if ($conn->connect_error)
	{
		error_log("logthis cannot connect: "+$conn->connect_error, 4);
		exit();
	}

	$from = $conn->real_escape_string($from);
	$stuff = $conn->real_escape_string($stuff);

	$conn->query("insert into `log` (`from`,`message`) values('$from', '$stuff')");
	$conn->close();
}

?>