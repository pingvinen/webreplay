<?php

class ListStreamsTest extends FixtureBase
{
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
}

?>
