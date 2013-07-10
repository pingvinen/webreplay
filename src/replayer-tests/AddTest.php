<?php

/**
 * Additional tests
 *
 * add without stream
 * add with invalid streamid
 * add with no payload
 */

class AddTest extends FixtureBase
{
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
}

?>
