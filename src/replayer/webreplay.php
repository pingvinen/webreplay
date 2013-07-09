<?php

//
// get config
//
if (file_exists("config.php"))
{
	require 'config.php';
}
else
{
	$config = array(
		"db_host" => "localhost",
		"db_user" => "webreplay",
		"db_pass" => "123456",
		"db_name" => "webreplay"
	);
}




function handler_documentation()
{
	echo "<h1>Web Replay</h1>";
	echo "<p><a href=\"https://github.com/pingvinen/webreplay/\" target=\"_blank\">Github</a></p>";
}


function handler_debug_streams($db)
{
	header("Content-Type: text/json", true);

	$list = [];

	if ($res = $db->query("select * from `streams` order by `id` asc"))
	{
		while ($row = $res->fetch_assoc())
		{
			$list[] = array(
				"stream_id" => $row["id"],
				"description" => $row["description"],
				"position" =>  (int)$row["position"]
			);
		}
	}

	echo json_encode($list);
}



function handler_debug_deleteallstreams($db)
{
	$db->query("delete from `entries`");
	$db->query("delete from `streams`");
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



function handler_get($db, $path)
{
	/**
	 * http://www.phpliveregex.com/
	 * http://www.phpliveregex.com/p/B6
	 */
	if (preg_match("/\/(?<streamid>[a-z0-9]+)\/*(?<entryid>[0-9]+)*\/?/i", $path, $matches) === 1)
	{
		$do_update_pos = true;
		$streamid = $matches["streamid"];
		$old_pos = null;

		$entryid = null;
		if (array_key_exists("entryid", $matches) === TRUE)
		{
			$entryid = $matches["entryid"];
		}

		//
		// get stream position
		//
		$q = $db->prepare("select `position` from `streams` where `id`=? limit 1");
		$q->bind_param("s", $streamid);
		$q->execute();
		$q->bind_result($streampos);
		$q->fetch();
		$q->close();
		$old_pos = $streampos;


		//
		// get the next entry
		//
		$x = $db->prepare("select `id`, `stream_id`, `content` from `entries` where `stream_id`=? && `id`>? order by `id` asc limit 1");
		$x->bind_param("si", $streamid, $streampos);
		$x->execute();
		$x->bind_result($col_id, $col_streamid, $col_content);
		$x->fetch();
		$x->close();

		if ($col_id == null)
		{
			// we are at the end of the stream
			$x = $db->prepare("select `id`, `stream_id`, `content` from `entries` where `id`=? limit 1");
			$x->bind_param("i", $old_pos);
			$x->execute();
			$x->bind_result($col_id, $col_streamid, $col_content);
			$x->fetch();
			$x->close();

			$do_update_pos = false;
		}

		//
		// update the stream position (if needed)
		//
		if ($do_update_pos)
		{
			$streampos = $col_id;
			$y = $db->prepare("update `streams` set `position`=? where `id`=? limit 1");
			$y->bind_param("is", $streampos, $streamid);
			$y->execute();
			$y->close();
		}

		echo $col_content;
		return;
	}



	header('HTTP/1.0 404 Not Found');
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
 * GET "/debug/deleteallstreams/" => deletes all streams (makes unittesting of webreplayer easier)
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

elseif ($path == "/debug/deleteallstreams/" || $path == "/debug/deleteallstreams")
{
	handler_debug_deleteallstreams($db);
}

elseif ($requestMethod == "POST" && starts_with($path, "/add/"))
{
	handler_add($db, $path);
}

else
{
	handler_get($db, $path);
}


$db->close();

?>
