<?php

require_once "test_utils.php";

class ReplayerRootHandlerTest extends PHPUnit_Framework_TestCase
{
	/**
	 * Test that the root handler works when request does
	 * not have end slash
	 */
	public function test_no_end_slash()
	{
		$r = new HttpRequest('http://webreplay.local', HttpRequest::METH_GET);
		try {
			$r->send();
			if ($r->getResponseCode() == 200)
			{
				$this->assertTrue(starts_with($r->getResponseBody(), "<h1>Web Replay</h1>"), "Wrong beginning of payload");
			}
		}
		catch (HttpException $ex)
		{
			echo $ex;
		}
	}



	/**
	 * Test that the root handler works when request does
	 * have end slash
	 */
	public function test_with_end_slash()
	{
		$r = new HttpRequest('http://webreplay.local/', HttpRequest::METH_GET);
		try {
			$r->send();
			if ($r->getResponseCode() == 200)
			{
				$this->assertTrue(starts_with($r->getResponseBody(), "<h1>Web Replay</h1>"), "Wrong beginning of payload");
			}
		}
		catch (HttpException $ex)
		{
			echo $ex;
		}
	}
}

?>
