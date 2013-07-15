<?php

class PartialResponseTest extends FixtureBase
{
	public function test_valid_using_get()
	{
		$streamid = "teststream";
		$e1content = "{\"name\":\"entry1\",\"int\":1}";
		$length = 13;
		

		//
		// create stream
		//
		$add = new HttpRequest(get_endpoint("/add/$streamid/"), HttpRequest::METH_POST);
		$add->addRawPostData($e1content);
		$add->send();

		

		//
		// get entry 1
		//
		$e1 = new HttpRequest(get_endpoint("/$streamid/?length=$length"), HttpRequest::METH_GET);
		$e1->send();
		$this->assertEquals(200, $e1->getResponseCode(), "The response code should have been 200");
		$this->assertEquals(substr($e1content, 0, $length), $e1->getResponseBody(), "The response for the first entry is wrong");
	}


	public function test_valid_using_post()
	{
		$streamid = "teststream";
		$e1content = "{\"name\":\"entry1\",\"int\":1}";
		$length = 13;
		

		//
		// create stream
		//
		$add = new HttpRequest(get_endpoint("/add/$streamid/"), HttpRequest::METH_POST);
		$add->addRawPostData($e1content);
		$add->send();

		
		//
		// get entry 1
		//
		$e1 = new HttpRequest(get_endpoint("/$streamid/"), HttpRequest::METH_POST);
		$e1->addPostFields(array(
			"length" => $length
		));
		$e1->send();
		$this->assertEquals(200, $e1->getResponseCode(), "The response code should have been 200");
		$this->assertEquals(substr($e1content, 0, $length), $e1->getResponseBody(), "The response for the first entry is wrong");
	}


	public function test_invalid_nan()
	{
		$streamid = "teststream";
		$e1content = "{\"name\":\"entry1\",\"int\":1}";
		$length = "thirteen";
		

		//
		// create stream
		//
		$add = new HttpRequest(get_endpoint("/add/$streamid/"), HttpRequest::METH_POST);
		$add->addRawPostData($e1content);
		$add->send();

		

		//
		// get entry 1
		//
		$e1 = new HttpRequest(get_endpoint("/$streamid/?length=$length"), HttpRequest::METH_GET);
		$e1->send();
		$this->assertEquals(400, $e1->getResponseCode(), "The response code should have been 400");
		$this->assertEquals("", $e1->getResponseBody(), "The response for the first entry is wrong");
	}



	public function test_invalid_negative()
	{
		$streamid = "teststream";
		$e1content = "{\"name\":\"entry1\",\"int\":1}";
		$length = -10;
		

		//
		// create stream
		//
		$add = new HttpRequest(get_endpoint("/add/$streamid/"), HttpRequest::METH_POST);
		$add->addRawPostData($e1content);
		$add->send();

		

		//
		// get entry 1
		//
		$e1 = new HttpRequest(get_endpoint("/$streamid/?length=$length"), HttpRequest::METH_GET);
		$e1->send();
		$this->assertEquals(400, $e1->getResponseCode(), "The response code should have been 400");
		$this->assertEquals("", $e1->getResponseBody(), "The response for the first entry is wrong");
	}



	public function test_valid_longer_than_content()
	{
		$streamid = "teststream";
		$e1content = "{\"name\":\"entry1\",\"int\":1}";
		$length = 999;
		

		//
		// create stream
		//
		$add = new HttpRequest(get_endpoint("/add/$streamid/"), HttpRequest::METH_POST);
		$add->addRawPostData($e1content);
		$add->send();

		

		//
		// get entry 1
		//
		$e1 = new HttpRequest(get_endpoint("/$streamid/?length=$length"), HttpRequest::METH_GET);
		$e1->send();
		$this->assertEquals(200, $e1->getResponseCode(), "The response code should have been 200");
		$this->assertEquals(substr($e1content, 0, $length), $e1->getResponseBody(), "The response for the first entry is wrong");
	}
}

?>
