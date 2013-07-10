<?php

/**
 * Additional tests
 *
 * get a non-existant stream
 * get with invalid streamid
 */

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
}

?>
