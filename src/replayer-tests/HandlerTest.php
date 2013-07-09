<?php

require_once "test_utils.php";

class HandlerTest extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		delete_all_streams();
	}

	public function tearDown()
	{
		delete_all_streams();
	}


	/**
	 * Test that the root handler works when request does
	 * not have end slash
	 */
	public function test_root__no_end_slash()
	{
		$r = new HttpRequest(get_endpoint(""), HttpRequest::METH_GET);
		try {
			$r->send();
			if ($r->getResponseCode() == 200)
			{
				$this->assertTrue(starts_with($r->getResponseBody(), "<h1>Web Replay</h1>"), "Wrong beginning of payload");
			}
		}
		catch (HttpException $ex)
		{
			echo $ex;
		}
	}



	/**
	 * Test that the root handler works when request does
	 * have end slash
	 */
	public function test_root_with_end_slash()
	{
		$r = new HttpRequest(get_endpoint("/"), HttpRequest::METH_GET);
		try {
			$r->send();
			if ($r->getResponseCode() == 200)
			{
				$this->assertTrue(starts_with($r->getResponseBody(), "<h1>Web Replay</h1>"), "Wrong beginning of payload");
			}
		}
		catch (HttpException $ex)
		{
			echo $ex;
		}
	}



	/**
	 * Test the streams handler with empty database (no end slash)
	 */
	public function test_streams_empty_no_end_slash()
	{
		$r = new HttpRequest(get_endpoint("/debug/streams"), HttpRequest::METH_GET);
		try {
			$resp = $r->send();
			if ($r->getResponseCode() == 200)
			{
				$this->assertEquals("[]", $r->getResponseBody(), "The streams array should be empty as there should be no streams");
				$this->assertEquals("text/json;charset=UTF-8", $resp->getHeader("Content-Type"), "Response content type is wrong");
			}
		}
		catch (HttpException $ex)
		{
			echo $ex;
		}
	}


	/**
	 * Test the streams handler with empty database (with end slash)
	 */
	public function test_streams_empty_with_end_slash()
	{
		$r = new HttpRequest(get_endpoint("/debug/streams/"), HttpRequest::METH_GET);
		try {
			$resp = $r->send();
			if ($r->getResponseCode() == 200)
			{
				$this->assertEquals("[]", $r->getResponseBody(), "The streams array should be empty as there should be no streams");
				$this->assertEquals("text/json;charset=UTF-8", $resp->getHeader("Content-Type"), "Response content type is wrong");
			}
		}
		catch (HttpException $ex)
		{
			echo $ex;
		}
	}



	/**
	 * Test the add handler with valid input and empty database (no end slash)
	 */
	public function test_add_valid_empty_no_end_slash()
	{
		$streamid = "teststream";

		//
		// add the stream
		//
		$add = new HttpRequest(get_endpoint("/add/$streamid/"), HttpRequest::METH_POST);
		$add->addRawPostData("{\"name\":\"value\",\"int\":2}");

		$add->send();

		// for debugging
		if ($add->getResponseCode() !== 200)
		{
			$this->assertEquals("", $add->getResponseStatus());
		}

		$this->assertEquals(200, $add->getResponseCode(), "Add request should have been ok");




		//
		// make sure that it was added
		//
		$list = new HttpRequest(get_endpoint("/debug/streams/"), HttpRequest::METH_GET);
		$list->send();
		$this->assertEquals(200, $list->getResponseCode(), "List request should have been ok");

		$expected_list = json_encode(array(
			array(
				"stream_id" => "teststream",
				"description" => "empty description",
				"position" => 0
			)
		));

		$actual_list = json_decode($list->getResponseBody());

		$this->assertJsonStringEqualsJsonString($expected_list, $list->getResponseBody(), "Lists do not match");
	}



	/**
	 * Test simple get replaying with 2 entries.
	 * Requests are made using HTTP GET
	 */
	public function test_get_simple_using_get()
	{
		$streamid = "teststream";
		$e1content = "{\"name\":\"entry1\",\"int\":1}";
		$e2content = "{\"name\":\"entry2\",\"int\":2}";

		//
		// add entry 1
		//
		$add = new HttpRequest(get_endpoint("/add/$streamid/"), HttpRequest::METH_POST);
		$add->addRawPostData($e1content);

		$add->send();

		//
		// add entry 2
		//
		$add = new HttpRequest(get_endpoint("/add/$streamid/"), HttpRequest::METH_POST);
		$add->addRawPostData($e2content);

		$add->send();



		//
		// get entry 1
		//
		$e1 = new HttpRequest(get_endpoint("/$streamid/"), HttpRequest::METH_GET);
		$e1->send();
		$this->assertEquals($e1content, $e1->getResponseBody(), "The response for the first entry is wrong");

		//
		// get entry 2
		//
		$e2 = new HttpRequest(get_endpoint("/$streamid/"), HttpRequest::METH_GET);
		$e2->send();
		$this->assertEquals($e2content, $e2->getResponseBody(), "The response for the second entry is wrong");

		//
		// get last entry (should be entry 2)
		//
		$last = new HttpRequest(get_endpoint("/$streamid/"), HttpRequest::METH_GET);
		$last->send();
		$this->assertEquals($e2content, $last->getResponseBody(), "The response for the last entry is wrong");
	}



	/**
	 * Additional tests
	 *
	 * add without stream
	 * add with invalid streamid
	 * add with no payload
	 *
	 * get a non-existant stream
	 * get specific stream entry
	 * get specific stream entry that does not belong to the named stream
	 * get with invalid streamid
	 * get with invalid entryid
	 */
}

?>
