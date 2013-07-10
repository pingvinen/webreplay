<?php

abstract class FixtureBase extends PHPUnit_Framework_TestCase
{
	public function setUp()
	{
		delete_all_streams();
	}

	public function tearDown()
	{
		delete_all_streams();
	}
}

?>
