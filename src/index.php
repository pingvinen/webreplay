<?php

$config = array(
	"db_host" => "localhost",
	"db_user" => "webreplay",
	"db_pass" => "123456",
	"db_name" => "webreplay"
);



function handler_documentation()
{
	echo "<h1>Web Replay</h1>";
	echo "<p><a href=\"https://github.com/pingvinen/webreplay/\" target=\"_blank\">Github</a></p>";
}


function handler_debug_streams($db)
{
	header("Content-Type: text/json", true);
	echo "[{ \"stream_id\":\"somestream\" }]";
}


function handler_add($db, $path)
{
	/**
	 * http://www.phpliveregex.com/
	 * http://www.phpliveregex.com/p/AB
	 */
	if (preg_match("/\/add\/(?<id>[a-z0-9]+)(?:\/.*)*/i", $path, $matches) === 1)
	{
		$streamid = $matches["id"];
		$description = "empty description";
		$payload = file_get_contents("php://input");
		
		#
		# create the stream
		#
		if ($q = $db->prepare("insert into `streams` (`id`,`description`) values(?,?)"))
		{
			$q->bind_param("ss", $streamid, $description);
			$q->execute();
		}


		#
		# add the entry
		#
		if ($q = $db->prepare("insert into `entries` (`stream_id`,`content`) values(?,?)"))
		{
			$q->bind_param("ss", $streamid, $payload);
			$q->execute();
		}


		return;
	}

	header('400 Missing a stream ID');
}



function starts_with($haystack, $needle)
{
    return !strncmp($haystack, $needle, strlen($needle));
}





$db = new mysqli($config["db_host"], $config["db_user"], $config["db_pass"], $config["db_name"]);
if (mysqli_connect_errno())
{
	header("500 DB connect failed");
	printf("Connect failed: %s\n", mysqli_connect_error());
	exit();
}


$requestMethod = strtoupper($_SERVER["REQUEST_METHOD"]);

/**
 * GET "/" => documentation
 * GET "/debug/phpinfo/" => phpinfo
 * GET "/debug/streams/" => list of all streams
 * POST "/add/streamid/" => adds the payload to the stream (and creates the stream if needed)
 * GET/POST "/streamid/" => returns the payload of the current (or last) entry in the stream
 * GET/POST "/streamid/entryid/" => returns the payload from the specific entry
 */
$path = $_SERVER["SCRIPT_NAME"];

if ($path == "/" || $path == "")
{
	handler_documentation();
}

elseif ($path == "/debug/phpinfo/" || $path == "/debug/phpinfo")
{
	echo phpinfo();
}

elseif ($path == "/debug/streams/" || $path == "/debug/streams")
{
	handler_debug_streams($db);
}

elseif ($requestMethod == "POST" && starts_with($path, "/add/"))
{
	handler_add($db, $path);
}

else
{
	header('HTTP/1.0 404 Not Found');
}


$db->close();

?>