<?php

class DelayedGetTest extends FixtureBase
{
	public function test_delayed_get_zero()
	{
		$streamid = "teststream";
		$e1content = "{\"name\":\"entry1\",\"int\":1}";
		$delay = 0;
		

		//
		// create stream
		//
		$add = new HttpRequest(get_endpoint("/add/$streamid/"), HttpRequest::METH_POST);
		$add->addRawPostData($e1content);
		$add->send();

		

		//
		// get entry 1
		//
		$e1 = new HttpRequest(get_endpoint("/$streamid/?delay=$delay"), HttpRequest::METH_GET);
		$time_before = time();
		$e1->send();
		$time_after = time();
		$this->assertEquals(200, $e1->getResponseCode(), "The response code should have been 200");
		$this->assertEquals($e1content, $e1->getResponseBody(), "The response for the first entry is wrong");

		$this->assertEquals($time_before+$delay, $time_after, "Timing is wrong");
	}



	public function test_delayed_get_positive()
	{
		$streamid = "teststream";
		$e1content = "{\"name\":\"entry1\",\"int\":1}";
		$delay = 1;
		

		//
		// create stream
		//
		$add = new HttpRequest(get_endpoint("/add/$streamid/"), HttpRequest::METH_POST);
		$add->addRawPostData($e1content);
		$add->send();

		

		//
		// get entry 1
		//
		$e1 = new HttpRequest(get_endpoint("/$streamid/?delay=$delay"), HttpRequest::METH_GET);
		$time_before = time();
		$e1->send();
		$time_after = time();
		$this->assertEquals(200, $e1->getResponseCode(), "The response code should have been 200");
		$this->assertEquals($e1content, $e1->getResponseBody(), "The response for the first entry is wrong");

		$this->assertEquals($time_before+$delay, $time_after, "Timing is wrong");
	}



	public function test_delayed_get_positive_using_post()
	{
		$streamid = "teststream";
		$e1content = "{\"name\":\"entry1\",\"int\":1}";
		$delay = 1;
		

		//
		// create stream
		//
		$add = new HttpRequest(get_endpoint("/add/$streamid/"), HttpRequest::METH_POST);
		$add->addRawPostData($e1content);
		$add->send();

		

		//
		// get entry 1
		//
		$e1 = new HttpRequest(get_endpoint("/$streamid/?delay=$delay"), HttpRequest::METH_POST);
		$time_before = time();
		$e1->send();
		$time_after = time();
		$this->assertEquals(200, $e1->getResponseCode(), "The response code should have been 200");
		$this->assertEquals($e1content, $e1->getResponseBody(), "The response for the first entry is wrong");

		$this->assertEquals($time_before+$delay, $time_after, "Timing is wrong");
	}




	public function test_delayed_get_negative()
	{
		$streamid = "teststream";
		$e1content = "{\"name\":\"entry1\",\"int\":1}";
		$delay = -1;
		

		//
		// create stream
		//
		$add = new HttpRequest(get_endpoint("/add/$streamid/"), HttpRequest::METH_POST);
		$add->addRawPostData($e1content);
		$add->send();

		

		//
		// get entry 1
		//
		$e1 = new HttpRequest(get_endpoint("/$streamid/?delay=$delay"), HttpRequest::METH_GET);
		$time_before = time();
		$e1->send();
		$time_after = time();
		$this->assertEquals(200, $e1->getResponseCode(), "The response code should have been 200");
		$this->assertEquals($e1content, $e1->getResponseBody(), "The response for the first entry is wrong");

		$this->assertEquals($time_before+0, $time_after, "Timing is wrong");
	}
}

?>
