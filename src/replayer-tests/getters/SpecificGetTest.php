<?php

/**
 * Additional tests
 *
 * get specific non-existant entry
 * get with invalid entryid
 */

class SpecificGetTest extends FixtureBase
{
	/**
	 * Test specific get replaying with 2 entries.
	 * Requests are made using HTTP GET
	 */
	public function test_valid_using_get()
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


		/**
		 * lets start by incrementing the stream position
		 **/

		//
		// get entry 1
		//
		$e1 = new HttpRequest(get_endpoint("/$streamid/"), HttpRequest::METH_GET);
		$e1->send();
		$this->assertEquals(200, $e1->getResponseCode(), "The response code for the first entry should have been 404");
		$this->assertEquals($e1content, $e1->getResponseBody(), "The response for the first entry is wrong");

		//
		// get entry 2
		//
		$e2 = new HttpRequest(get_endpoint("/$streamid/"), HttpRequest::METH_GET);
		$e2->send();
		$this->assertEquals(200, $e2->getResponseCode(), "The response code for the second entry should have been 404");
		$this->assertEquals($e2content, $e2->getResponseBody(), "The response for the second entry is wrong");


		/**
		 * now let us get specific entries
		 **/

		//
		// get entry 2
		//
		$e2 = new HttpRequest(get_endpoint("/$streamid/2"), HttpRequest::METH_GET);
		$e2->send();
		$this->assertEquals(200, $e2->getResponseCode(), "Specific: The response code for the second entry should have been 404");
		$this->assertEquals($e2content, $e2->getResponseBody(), "Specific: The response for entryid 2 is wrong");

		//
		// get entry 1
		//
		$e1 = new HttpRequest(get_endpoint("/$streamid/1/"), HttpRequest::METH_GET);
		$e1->send();
		$this->assertEquals(200, $e1->getResponseCode(), "Specific: The response code for the first entry should have been 404");
		$this->assertEquals($e1content, $e1->getResponseBody(), "Specific: The response for entryid 1 is wrong");
	}




	/**
	 * Test specific get replaying with 2 entries.
	 * Requests are made using HTTP POST
	 */
	public function test_valid_using_post()
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


		/**
		 * lets start by incrementing the stream position
		 **/

		//
		// get entry 1
		//
		$e1 = new HttpRequest(get_endpoint("/$streamid/"), HttpRequest::METH_POST);
		$e1->send();
		$this->assertEquals($e1content, $e1->getResponseBody(), "The response for the first entry is wrong");

		//
		// get entry 2
		//
		$e2 = new HttpRequest(get_endpoint("/$streamid/"), HttpRequest::METH_POST);
		$e2->send();
		$this->assertEquals($e2content, $e2->getResponseBody(), "The response for the second entry is wrong");


		/**
		 * now let us get specific entries
		 **/

		//
		// get entry 2
		//
		$e2 = new HttpRequest(get_endpoint("/$streamid/2"), HttpRequest::METH_POST);
		$e2->send();
		$this->assertEquals($e2content, $e2->getResponseBody(), "The response for entryid 2 is wrong");

		//
		// get entry 1
		//
		$e1 = new HttpRequest(get_endpoint("/$streamid/1/"), HttpRequest::METH_POST);
		$e1->send();
		$this->assertEquals($e1content, $e1->getResponseBody(), "The response for entryid 1 is wrong");
	}



	/**
	 * Test a specific get, where the entryID do not belong to the named stream
	 * Requests are made using HTTP GET
	 */
	public function test_entry_and_stream_mismatch_using_get()
	{
		$stream1 = "teststream";
		$stream2 = "notthis";
		$e1content = "{\"name\":\"entry1\",\"int\":1}";
		$e2content = "{\"name\":\"entry2\",\"int\":2}";

		//
		// add stream1
		//
		$add = new HttpRequest(get_endpoint("/add/$stream1/"), HttpRequest::METH_POST);
		$add->addRawPostData($e1content);
		$add->send();

		//
		// add stream2
		//
		$add = new HttpRequest(get_endpoint("/add/$stream2/"), HttpRequest::METH_POST);
		$add->addRawPostData($e2content);
		$add->send();

		//
		// get mismatched entry
		//
		$e1 = new HttpRequest(get_endpoint("/$stream2/1/"), HttpRequest::METH_GET);
		$e1->send();
		$this->assertEquals(404, $e1->getResponseCode(), "The response code should have been 404");
		$this->assertEquals("", $e1->getResponseBody(), "The response body should be empty");
	}
}

?>
