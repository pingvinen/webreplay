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



class Stream
{
	public $id = null;
	public $description = null;
	public $position = 0;
	
	private $db = null;
	private $is_new = true;

	public function __construct($streamid, $db)
	{
		$this->id = $streamid;
		$this->db = $db;
	}

	public function load()
	{
		$has_match = false;

		$q = $this->db->prepare("select `description`, `position` from `streams` where `id`=? limit 1");
		$q->bind_param("s", $this->id);
		$q->execute();
		$q->bind_result($col_description, $col_position);
		$has_match = $q->fetch();
		$q->close();

		if ($has_match !== true)
		{
			throw new Exception("No such stream");
		}

		$this->description = $col_description;
		$this->position = (int)$col_position;

		$this->is_new = false;
	}

	public function save()
	{
		if ($this->is_new)
		{
			throw new Exception("Saving a new stream has not been implemented yet");
		}
		else
		{
			$q = $this->db->prepare("update `streams` set `description`=?, `position`=? where `id`=? limit 1");
			$q->bind_param("sis", $this->description, $this->position, $this->id);
			$q->execute();
		}
	}

	public function get_specific_entry($entryid)
	{
		$q = $this->db->prepare("select `id`, `stream_id`, `content` from `entries` where `id`=? limit 1");
		$q->bind_param("i", $entryid);
		$q->execute();
		$q->bind_result($col_id, $col_streamid, $col_content);
		$q->fetch();
		$q->close();

		if ($col_streamid != $this->id)
		{
			return null;
		}

		return new StreamEntry($col_id, $col_streamid, $col_content);
	}

	public function get_next_or_last_entry()
	{
		$result = null;
		//
		// get the next entry
		//
		$q = $this->db->prepare("select `id`, `stream_id`, `content` from `entries` where `stream_id`=? && `id`>? order by `id` asc limit 1");
		$q->bind_param("si", $this->id, $this->position);
		$q->execute();
		$q->bind_result($col_id, $col_streamid, $col_content);
		$q->fetch();
		$result = new StreamEntry($col_id, $col_streamid, $col_content);
		$q->close();

		if ($col_id == null)
		{
			// we are at the end of the stream
			// return the last entry (should be the original position)
			return $this->get_specific_entry($this->position);
		}

		// update the stream position
		$this->position = $result->id;
		$this->save();

		return $result;
	}
}



class StreamEntry
{
	public $id = null;
	public $streamid = null;
	public $content = null;

	public function __construct($id = null, $streamid = null, $content = null)
	{
		$this->id = $id;
		$this->streamid = $streamid;
		$this->content = $content;
	}
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
	$db->autocommit(FALSE);

	$db->query("set foreign_key_checks = 0");
	$db->query("truncate table `entries`");
	$db->query("truncate table `streams`");
	$db->query("set foreign_key_checks = 1");

	$db->commit();
	$db->autocommit(TRUE);
}




function handler_add($db, $path)
{
	/**
	 * http://www.phpliveregex.com/
	 * http://www.phpliveregex.com/p/CL
	 */
	if (preg_match("/\/add\/(?<id>[^\/]+)(?:\/.*)*/i", $path, $matches) === 1)
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
	 * http://www.phpliveregex.com/p/CQ
	 */
	if (preg_match("/\/(?<streamid>[^\/?]+)\/*(?<entryid>[^\/?]+)*\/?/i", $path, $matches) === 1)
	{
		$streamid = $matches["streamid"];

		$stream = new Stream($streamid, $db);

		try
		{
			$stream->load();
		}

		catch (Exception $ex)
		{
			header('HTTP/1.0 404 Not Found');
			return;
		}

		//
		// handle requests for specific entries
		//
		if (array_key_exists("entryid", $matches) === TRUE)
		{
			//
			// make sure the entryID has the valid format
			// (the regex finds invalid entryIDs on purpose
			// to make it easier to spot requests with invalid
			// entryIDs)
			//
			if (!is_numeric($matches["entryid"]))
			{
				header('HTTP/1.0 400 Invalid entry ID');
				return;
			}

			// get the entry from the stream
			$result = $stream->get_specific_entry($matches["entryid"]);
			if ($result === null)
			{
				header('HTTP/1.0 404 Not Found');
				return;
			}

			return $result->content;
		}


		//
		// handle requests for next/last entry
		//
		return $stream->get_next_or_last_entry()->content;
	}


	header('HTTP/1.0 400 Invalid stream or entry ID');
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
	echo handler_get($db, $path);
}


$db->close();

?>
