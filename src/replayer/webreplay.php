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

//
// define the log method
//
if (!function_exists("logthis"))
{
	function logthis($stuff, $from = '')
	{
		// do nothing

		//
		// you should override this in config.php if you need logging
		//
	}
}



/**
 * Represents a replay stream
 */
class Stream
{
	/**
	 * The id of the stream
	 * @var string 
	 */
	public $id = null;

	/**
	 * Description of the stream
	 * @var string
	 */
	public $description = null;

	/**
	 * The last returned entry ID
	 * @var int
	 */
	public $position = 0;
	
	/**
	 * Database instance
	 * @var MySqli
	 */
	private $db = null;

	/**
	 * Whether this is a loaded
	 * or created instance
	 * @var bool
	 */
	private $is_new = true;

	/**
	 * Constructor
	 * @param string $streamid The ID of the stream
	 * @param MySqli $db The database instance to use
	 */
	public function __construct($streamid, $db)
	{
		$this->id = $streamid;
		$this->db = $db;
	}

	/**
	 * Load the stream information from the database
	 */
	public function load()
	{
		$q = (new SqlQuery("select `description`, `position` from `streams` where `id`=@streamid limit 1"))
				->add_param("@streamid", $this->id)
				->prepare($this->db);

		if ($res = $this->db->query($q))
		{
			if ($res->num_rows !== 1)
			{
				throw new Exception("No such stream");
			}

			$row = $res->fetch_assoc();

			$this->description = $row['description'];
			$this->position = (int)$row['position'];
			$this->is_new = false;
			return;
		}

		throw new Exception("No such stream");
	}

	/**
	 * Save the stream
	 */
	public function save()
	{
		if ($this->is_new)
		{
			$q = (new SqlQuery("insert into `streams` (`id`,`description`) values(@streamid,@desc)"))
				->add_param("@streamid", $this->id)
				->add_param("@desc", $this->description)
				->prepare($this->db);

			$this->db->query($q);
			$this->is_new = false;
		}
		else
		{
			$q = (new SqlQuery("update `streams` set `description`=@desc, `position`=@pos where `id`=@streamid limit 1"))
					->add_param("@desc", $this->description)
					->add_param("@pos", $this->position)
					->add_param("@streamid", $this->id)
					->prepare($this->db);
			$this->db->query($q);
		}
	}

	/**
	 * Get a specific entry
	 * @param int $entryid The ID of the entry to load
	 * @return StreamEntry|null The entry or null if the entry does not belong to this stream
	 */
	public function get_specific_entry($entryid)
	{
		$q = (new SqlQuery("select `id`, `stream_id`, `content` from `entries` where `id`=@entryid limit 1"))
				->add_param("@entryid", $entryid)
				->prepare($this->db);

		$res = $this->db->query($q);
		if ($res->num_rows !== 1)
		{
			return null;
		}

		$row = $res->fetch_assoc();
		$entry = new StreamEntry($row['id'], $row['stream_id'], $row['content']);

		// make sure that the entry is actually
		// a part of this stream
		if ($entry->streamid != $this->id)
		{
			return null;
		}

		return $entry;
	}

	/**
	 * Get the next (or last) entry in the stream
	 * @return StreamEntry
	 */
	public function get_next_or_last_entry()
	{
		$result = null;
		//
		// get the next entry
		//
		$q = (new SqlQuery("select `id`, `stream_id`, `content` from `entries` where `stream_id`=@streamid && `id`>@pos order by `id` asc limit 1"))
				->add_param("@streamid", $this->id)
				->add_param("@pos", $this->position)
				->prepare($this->db);

		$res = $this->db->query($q);
		if ($res->num_rows !== 1)
		{
			// we are at the end of the stream
			// return the last entry (should be the original position)
			return $this->get_specific_entry($this->position);
		}

		$row = $res->fetch_assoc();
		$result = new StreamEntry($row['id'], $row['stream_id'], $row['content']);

		//
		// update the stream position
		//
		$this->position = $result->id;
		$this->save();

		return $result;
	}

	/**
	 * Delete this stream and all of its entries
	 */
	public function delete()
	{
		$this->db->autocommit(FALSE);

		// remove all the entries
		$entries = new SqlQuery("delete from `entries` where `stream_id`=@streamid");
		$entries->add_param("@streamid", $this->id);
		$this->db->query($entries->prepare($this->db));

	
		// remove the stream itself
		$stream = new SqlQuery("delete from `streams` where `id`=@streamid");
		$stream->add_param("@streamid", $this->id);
		$this->db->query($stream->prepare($this->db));

		$this->db->commit();
		$this->db->autocommit(TRUE);
	}
}


/**
 * This is a simple DTO representing an entry in a stream
 */
class StreamEntry
{
	/**
	 * The ID of the entry
	 * @var int
	 */
	public $id = null;

	/**
	 * The ID of the stream that this entry belongs to
	 * @var string
	 */
	public $streamid = null;

	/**
	 * The content of this entry
	 * @var string
	 */
	public $content = null;

	/**
	 * Constructor
	 * @param int $id The ID of this entry (defaults to null)
	 * @param string $streamid The ID of the stream that this entry belongs to (defaults to null)
	 * @param string $content The content of this entry (defaults to null)
	 */
	public function __construct($id = null, $streamid = null, $content = null)
	{
		$this->id = $id;
		$this->streamid = $streamid;
		$this->content = $content;
	}
}


/**
 * Sort of a DSL for parameterized SQL queries
 */
class SqlQuery
{
	/**
	 * The raw query (with placeholders)
	 * @var string
	 */
	private $query = null;

	/**
	 * The parameters for the query
	 * @var QueryParam[]
	 */
	private $params = array();

	/**
	 * Construct a new query
	 * @param string $query The raw SQL query with placeholders
	 */
	public function __construct($query)
	{
		$this->query = $query;
	}

	/**
	 * Add a parameter
	 * @param string @name The name of the param (e.g. "@id")
	 * @param mixed @value The value for the param
	 * @return SqlQuery The sql-query instance is returned to allow chaining
	 */
	public function add_param($name, $value)
	{
		$this->params[] = new QueryParam($name, $value);
		return $this;
	}

	/**
	 * Generates the actual SQL that can be run
	 * @param MySqli @db The database instance (used for escaping)
	 * @return string The db-ready sql-query
	 */
	public function prepare($db)
	{
		$sql = $this->query;

		foreach ($this->params as $p)
		{
			$sql = str_replace($p->name, "'" . $db->real_escape_string($p->value) . "'", $sql);
		}

		return $sql;
	}
}

/**
 * Simple DTO carrying parameter information to be used by SqlQuery
 */
class QueryParam
{
	/**
	 * The name of the parameter
	 * @var string
	 */
	public $name = null;

	/**
	 * The value of the parameter
	 * @var mixed
	 */
	public $value = null;

	/**
	 * Construct a parameter
	 * @param string @name The name of the parameter
	 * @param mixed @value The value of the parameter
	 */
	public function __construct($name, $value)
	{
		$this->name = $name;
		$this->value = $value;
	}
}



/**
 * Handler for outputting list of streams
 * @param MySqli @db The database instance
 */
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


/**
 * Handler for deleting all streams and entries
 * @param MySqli @db The database instance
 */
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



/**
 * Handler for adding an entry
 * @param MySqli @db The database instance
 * @param string @path The request path
 */
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
		$stream = new Stream($streamid, $db);
		$stream->description = $description;
		$stream->save();

		#
		# add the entry
		#
		$q = (new SqlQuery("insert into `entries` (`stream_id`,`content`) values(@streamid,@content)"))
				->add_param("@streamid", $streamid)
				->add_param("@content", $payload)
				->prepare($db);
		$db->query($q);

		return;
	}

	header('400 Missing a stream ID');
}


/**
 * Handler for deleting a stream
 * @param MySqli @db The database instance
 * @param string @path The request path
 */
function handler_delete($db, $path)
{
	/**
	 * http://www.phpliveregex.com/
	 * http://www.phpliveregex.com/p/CZ
	 */
	if (preg_match("/\/delete\/(?<id>[^\/?]+)(?:\/.*)*/i", $path, $matches) === 1)
	{
		$streamid = $matches["id"];

		$stream = new Stream($streamid, $db);
		$stream->load();
		$stream->delete();

		return;
	}

	header('400 Missing a stream ID');
}



/**
 * Handler for getting an entry
 * @param MySqli @db The database instance
 * @param string @path The request path
 */
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


/**
 * Defines a parameter for an endpoint.
 * This is used when generating documentation.
 */
class EndpointParameter
{
	public $name = null;
	public $example = null;
	public $description = null;
	public $where = null;
	public $isoptional = false;

	public function isoptional_yesno()
	{
		if ($this->isoptional === true)
		{
			return "yes";
		}

		return "no";
	}
}


/**
 * Handler for the documentation page
 */
function handler_documentation()
{
	echo "<h1>Web Replay</h1>";
	echo "<p><a href=\"https://github.com/pingvinen/webreplay/\" target=\"_blank\">Github</a></p>";
	echo <<<END
<style>
th {
	text-align: left;
}
td {
	vertical-align: top;
}
</style>
END;

	$p_streamid = new EndpointParameter();
	$p_streamid->name = "streamid";
	$p_streamid->example = "mystream";
	$p_streamid->description = "The ID of the stream";
	$p_streamid->where = "Path";
	$p_streamid->isoptional = false;

	$p_payload = new EndpointParameter();
	$p_payload->name = "payload";
	$p_payload->example = "{\"my\": \"json\"}";
	$p_payload->description = "The paylod to add. This can be anything.";
	$p_payload->where = "Request body";
	$p_payload->isoptional = false;

	$p_entryid = new EndpointParameter();
	$p_entryid->name = "entryid";
	$p_entryid->example = "123";
	$p_entryid->description = "The ID of a specific entry in the stream (these are non-linear)";
	$p_entryid->where = "Path";
	$p_entryid->isoptional = false;

	$p_delay = new EndpointParameter();
	$p_delay->name = "delay";
	$p_delay->example = "15";
	$p_delay->description = "Response delay in seconds (integer).<br>The delay is imposed before doing anything with streams or parsing.<br>This can be combined with forced error responses.";
	$p_delay->where = "Query-string or post variables";
	$p_delay->isoptional = true;

	$p_error_code = new EndpointParameter();
	$p_error_code->name = "error_code";
	$p_error_code->example = "456";
	$p_error_code->description = "Forces a response with the given code (and message if 'error_msg' is defined).<br>Note that this disables the actual stream response.";
	$p_error_code->where = "Query-string or post variables";
	$p_error_code->isoptional = true;

	$p_error_msg = new EndpointParameter();
	$p_error_msg->name = "error_msg";
	$p_error_msg->example = "Something terrible happened";
	$p_error_msg->description = "Forced response status (if 'error_code' is defined). Remember to urlencode it.";
	$p_error_msg->where = "Query-string or post variables";
	$p_error_msg->isoptional = true;

	$p_length = new EndpointParameter();
	$p_length->name = "length";
	$p_length->example = "13";
	$p_length->description = "Defines how many characters of the response will be returned.";
	$p_length->where = "Query-string or post variables";
	$p_length->isoptional = true;

	echo get_endpoint_doc(
		"GET /debug/streams",
		"Lists all streams in a json format"
	);

	echo get_endpoint_doc(
		"GET /debug/deleteallstreams",
		"Deletes all streams and their entries. This is only meant to be used for unittests."
	);

	echo get_endpoint_doc(
		"GET /debug/phpinfo",
		"Runs phpinfo. Just a convenience."
	);

	echo get_endpoint_doc(
		"POST /add/{streamid}",
		"Adds an entry to a stream. If the stream does not exist, it is created.",
		array($p_streamid, $p_payload)
	);

	echo get_endpoint_doc(
		"GET or POST /{streamid}",
		"Gets the next or last entry from the stream.",
		array($p_streamid, $p_delay, $p_length, $p_error_code, $p_error_msg)
	);

	echo get_endpoint_doc(
		"GET or POST /{streamid}/{entryid}",
		"Gets the next or last entry from the stream.",
		array($p_streamid, $p_entryid, $p_delay, $p_length, $p_error_code, $p_error_msg)
	);

	echo get_endpoint_doc(
		"DELETE /{streamid}",
		"Delete the stream and all of its entries",
		array($p_streamid)
	);
}


/**
 * Generates html documentation for an endpoint
 *
 * @param string $endpoint The http method and endpoint (e.g. "POST /my/endpoint/{variable}")
 * @param string $description A description of the endpoint
 * @param [EndpointParameter[]] $parameters Optional: An array of endpoint parameters
 * @return string The HTML
 */
function get_endpoint_doc($endpoint, $description, $parameters = array())
{
	$html = "";

	$html .= "<h2>$endpoint</h2>";
	$html .= "<p>$description</p>";

	if (!is_array($parameters) || count($parameters) === 0)
	{
		return $html;
	}

	$html .= <<<END
<table>
	<tr>
		<th>Parameter</th>
		<th>Example</th>
		<th>Description</th>
		<th>Where</th>
		<th>Optional</th>
	</tr>
END;



	foreach ($parameters as $param)
	{
		$yesno = $param->isoptional_yesno();
		$html .= <<<END
	<tr>
		<td>$param->name</td>
		<td>$param->example</td>
		<td>$param->description</td>
		<td>$param->where</td>
		<td>$yesno</td>
	</tr>
END;
	}

	$html .= "</table>";
	return $html;
}



/**
 * Check if a given string starts with a given needle
 * @param string @haystack The string to check
 * @param string @needle The string to check for
 * @return bool True if haystack begins with needle, false otherwise
 */
function starts_with($haystack, $needle)
{
    return !strncmp($haystack, $needle, strlen($needle));
}



//
// open database connection
//
$db = new mysqli($config["db_host"], $config["db_user"], $config["db_pass"], $config["db_name"]);
if ($db->connect_error)
{
	header("500 DB connect failed");
	printf("Connect failed: %s\n", $db->connect_error);
	exit();
}



/**
 * GET "/" => documentation
 * GET "/debug/phpinfo/" => phpinfo
 * GET "/debug/streams/" => list of all streams
 * GET "/debug/deleteallstreams/" => deletes all streams (makes unittesting of webreplayer easier)
 * POST "/add/streamid/" => adds the payload to the stream (and creates the stream if needed)
 * GET/POST "/streamid/" => returns the payload of the current (or last) entry in the stream
 * GET/POST "/streamid/entryid/" => returns the payload from the specific entry
 * DELETE "/streamid/" => deletes the stream and all of its entries
 */
$path = $_SERVER["SCRIPT_NAME"];
$requestMethod = strtoupper($_SERVER["REQUEST_METHOD"]);

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

elseif ($requestMethod == "DELETE" && starts_with($path, "/delete/"))
{
	handler_delete($db, $path);
}

else
{
	$delay_in_seconds = null;
	
	// delay
	if (array_key_exists("delay", $_REQUEST))
	{
		$delay_in_seconds = $_REQUEST["delay"];

		//
		// delay
		//
		if (is_numeric($delay_in_seconds) && $delay_in_seconds > 0)
		{
			sleep($delay_in_seconds);
		}
	}

	// forced error response
	if (array_key_exists("error_code", $_REQUEST))
	{
		$error_code = $_REQUEST["error_code"];
		$error_msg = "";

		if (array_key_exists("error_msg", $_REQUEST))
		{
			$error_msg = urldecode($_REQUEST["error_msg"]);
		}

		header("HTTP/1.0 $error_code $error_msg");
		return;
	}


	// call the handler and get the response
	$response = handler_get($db, $path, $delay_in_seconds);

	// partial response
	if (array_key_exists("length", $_REQUEST))
	{
		$length = $_REQUEST["length"];

		if (!is_numeric($length))
		{
			header("HTTP/1.0 400 Length must be a number");
			return;
		}

		if ($length < 0)
		{
			header("HTTP/1.0 400 Length must be 0 or positive");
			return;
		}

		echo substr($response, 0, $length);
		return;
	}

	echo $response;
}


$db->close();

?>
