<?php

class ForcedErrorTest extends FixtureBase
{
	public function test_forced_error_code_and_message()
	{
		$streamid = "teststream";
		$code = 666;
		$msg = "The devil is here";

		//
		// the get
		//
		$e1 = new HttpRequest(get_endpoint("/$streamid"), HttpRequest::METH_GET);
		$e1->addQueryData(array(
			"error_code" => urlencode($code),
			"error_msg" => urlencode($msg)
		));
		$e1->send();
		$this->assertEquals($code, $e1->getResponseCode(), "The response code is wrong");
		$this->assertEquals($msg, $e1->getResponseStatus(), "The response status is wrong");
		$this->assertEquals("", $e1->getResponseBody(), "The response for the first entry is wrong");
	}




	public function test_forced_error_code_and_message_with_delay()
	{
		$streamid = "teststream";
		$code = 666;
		$msg = "The devil is here";
		$delay = 1;

		//
		// the get
		//
		$e1 = new HttpRequest(get_endpoint("/$streamid"), HttpRequest::METH_GET);
		$e1->addQueryData(array(
			"error_code" => urlencode($code),
			"error_msg" => urlencode($msg),
			"delay" => $delay
		));
		$time_before = time();
		$e1->send();
		$time_after = time();
		$this->assertEquals($code, $e1->getResponseCode(), "The response code is wrong");
		$this->assertEquals($msg, $e1->getResponseStatus(), "The response status is wrong");
		$this->assertEquals("", $e1->getResponseBody(), "The response for the first entry is wrong");
		$this->assertEquals($time_before+$delay, $time_after, "Timing is wrong");
	}
}

?>
