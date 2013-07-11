<?php

class DeleteTest extends FixtureBase
{
	/**
	 * Test the delete handler with valid input and empty database (no end slash)
	 */
	public function test_delete_valid_no_end_slash()
	{
		$streamid = "teststream";

		//
		// add the stream
		//
		$add = new HttpRequest(get_endpoint("/add/$streamid/"), HttpRequest::METH_POST);
		$add->addRawPostData("{\"name\":\"value\",\"int\":2}");
		$add->send();
		$this->assertEquals(200, $add->getResponseCode(), "Add request should have been ok");


		//
		// delete the stream
		//
		$del = new HttpRequest(get_endpoint("/delete/$streamid"), HttpRequest::METH_DELETE);
		$del->send();
		$this->assertEquals(200, $del->getResponseCode(), "Delete request should have been ok");


		//
		// make sure that it was deleted
		//
		$list = new HttpRequest(get_endpoint("/debug/streams/"), HttpRequest::METH_GET);
		$list->send();
		$this->assertEquals(200, $list->getResponseCode(), "List request should have been ok");
		$this->assertEquals("[]", $list->getResponseBody(), "The response for the first entry is wrong");
	}



	/**
	 * Test the delete handler with valid input and empty database (with end slash)
	 */
	public function test_delete_valid_with_end_slash()
	{
		$streamid = "teststream";

		//
		// add the stream
		//
		$add = new HttpRequest(get_endpoint("/add/$streamid/"), HttpRequest::METH_POST);
		$add->addRawPostData("{\"name\":\"value\",\"int\":2}");
		$add->send();
		$this->assertEquals(200, $add->getResponseCode(), "Add request should have been ok");


		//
		// delete the stream
		//
		$del = new HttpRequest(get_endpoint("/delete/$streamid/"), HttpRequest::METH_DELETE);
		$del->send();
		$this->assertEquals(200, $del->getResponseCode(), "Delete request should have been ok");


		//
		// make sure that it was deleted
		//
		$list = new HttpRequest(get_endpoint("/debug/streams/"), HttpRequest::METH_GET);
		$list->send();
		$this->assertEquals(200, $list->getResponseCode(), "List request should have been ok");
		$this->assertEquals("[]", $list->getResponseBody(), "The response for the first entry is wrong");
	}
}

?>
