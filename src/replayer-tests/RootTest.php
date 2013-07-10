<?php

class RootTest extends FixtureBase
{
	/**
	 * Test that the root handler works when request does
	 * not have end slash
	 */
	public function test_root__no_end_slash()
	{
		$r = new HttpRequest(get_endpoint(""), HttpRequest::METH_GET);
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
	public function test_root_with_end_slash()
	{
		$r = new HttpRequest(get_endpoint("/"), HttpRequest::METH_GET);
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
