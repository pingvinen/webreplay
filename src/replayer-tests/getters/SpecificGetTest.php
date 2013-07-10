<?php

/**
 * Additional tests
 *
 * get specific stream entry that does not belong to the named stream
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
		$this->assertEquals($e1content, $e1->getResponseBody(), "The response for the first entry is wrong");

		//
		// get entry 2
		//
		$e2 = new HttpRequest(get_endpoint("/$streamid/"), HttpRequest::METH_GET);
		$e2->send();
		$this->assertEquals($e2content, $e2->getResponseBody(), "The response for the second entry is wrong");


		/**
		 * now let us get specific entries
		 **/

		//
		// get entry 2
		//
		$e2 = new HttpRequest(get_endpoint("/$streamid/2"), HttpRequest::METH_GET);
		$e2->send();
		$this->assertEquals($e2content, $e2->getResponseBody(), "The response for entryid 2 is wrong");

		//
		// get entry 1
		//
		$e1 = new HttpRequest(get_endpoint("/$streamid/1/"), HttpRequest::METH_GET);
		$e1->send();
		$this->assertEquals($e1content, $e1->getResponseBody(), "The response for entryid 1 is wrong");
	}
}

?>
