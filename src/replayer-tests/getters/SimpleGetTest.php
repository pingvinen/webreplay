<?php

class SimpleGetTest extends FixtureBase
{
	/**
	 * Test simple get replaying with 2 entries.
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
	 * Test simple get replaying with 2 entries.
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

		//
		// get last entry (should be entry 2)
		//
		$last = new HttpRequest(get_endpoint("/$streamid/"), HttpRequest::METH_POST);
		$last->send();
		$this->assertEquals($e2content, $last->getResponseBody(), "The response for the last entry is wrong");
	}




	/**
	 * Test simple get for a non-existant stream.
	 * Requests are made using HTTP GET
	 */
	public function test_nonexistant_stream_using_get()
	{
		$streamid = "iamaghostidonotexist";

		//
		// the get
		//
		$e1 = new HttpRequest(get_endpoint("/$streamid/"), HttpRequest::METH_GET);
		$e1->send();
		$this->assertEquals(404, $e1->getResponseCode(), "The response code should have been a 404");
		$this->assertEquals("", $e1->getResponseBody(), "The response body should be empty");
	}





	/**
	 * Test simple get with query-string parameters
	 * Requests are made using HTTP GET
	 */
	public function test_valid_with_querystring_no_end_slash_using_get()
	{
		$streamid = "teststream";
		$e1content = "{\"name\":\"entry1\",\"int\":1}";

		//
		// create stream
		//
		$add = new HttpRequest(get_endpoint("/add/$streamid/"), HttpRequest::METH_POST);
		$add->addRawPostData($e1content);
		$add->send();


		//
		// get entry 1
		//
		$e1 = new HttpRequest(get_endpoint("/$streamid?q=x"), HttpRequest::METH_POST);
		$e1->send();
		$this->assertEquals(200, $e1->getResponseCode(), "The response code should have been 200");
		$this->assertEquals($e1content, $e1->getResponseBody(), "The response for the first entry is wrong");
	}



	/**
	 * Test simple get with query-string parameters
	 * Requests are made using HTTP GET
	 */
	public function test_valid_with_querystring_with_end_slash_using_get()
	{
		$streamid = "teststream";
		$e1content = "{\"name\":\"entry1\",\"int\":1}";

		//
		// create stream
		//
		$add = new HttpRequest(get_endpoint("/add/$streamid/"), HttpRequest::METH_POST);
		$add->addRawPostData($e1content);
		$add->send();


		//
		// get entry 1
		//
		$e1 = new HttpRequest(get_endpoint("/$streamid/?q=x"), HttpRequest::METH_POST);
		$e1->send();
		$this->assertEquals(200, $e1->getResponseCode(), "The response code should have been 200");
		$this->assertEquals($e1content, $e1->getResponseBody(), "The response for the first entry is wrong");
	}




	/**
	 * Test simple get replaying with 2 entries with
	 * 2 streams having interleaved entries
	 * Requests are made using HTTP GET
	 */
	public function test_interleaved_streams_using_get()
	{
		$streamid = "teststream";
		$e1content = "{\"name\":\"entry1\",\"int\":1}";
		$e2content = "{\"name\":\"entry2\",\"int\":2}";

		$otherstreamid = "otherstream";
		$othere1content = "{\"name\":\"other entry1\",\"int\":1}";
		$othere2content = "{\"name\":\"other entry2\",\"int\":2}";

		//
		// add entry 1.1
		//
		$add = new HttpRequest(get_endpoint("/add/$streamid/"), HttpRequest::METH_POST);
		$add->addRawPostData($e1content);

		$add->send();


		//
		// add entry 2.1
		//
		$add = new HttpRequest(get_endpoint("/add/$otherstreamid/"), HttpRequest::METH_POST);
		$add->addRawPostData($othere1content);

		$add->send();


		//
		// add entry 1.2
		//
		$add = new HttpRequest(get_endpoint("/add/$streamid/"), HttpRequest::METH_POST);
		$add->addRawPostData($e2content);

		$add->send();


		//
		// add entry 2.2
		//
		$add = new HttpRequest(get_endpoint("/add/$otherstreamid/"), HttpRequest::METH_POST);
		$add->addRawPostData($othere2content);

		$add->send();



		//
		// get entry 1.1
		//
		$e1 = new HttpRequest(get_endpoint("/$streamid/"), HttpRequest::METH_GET);
		$e1->send();
		$this->assertEquals($e1content, $e1->getResponseBody(), "The response for the first entry is wrong");

		//
		// get entry 1.2
		//
		$e2 = new HttpRequest(get_endpoint("/$streamid/"), HttpRequest::METH_GET);
		$e2->send();
		$this->assertEquals($e2content, $e2->getResponseBody(), "The response for the second entry is wrong");

		//
		// get last entry (should be entry 1.2)
		//
		$last = new HttpRequest(get_endpoint("/$streamid/"), HttpRequest::METH_GET);
		$last->send();
		$this->assertEquals($e2content, $last->getResponseBody(), "The response for the last entry is wrong");
	}
}

?>
