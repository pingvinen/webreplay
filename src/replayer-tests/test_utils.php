<?php

/**
 * Test that the given string ($haystack) begins
 * with the given string ($needle).
 *
 * @param string $haystack The string to test
 * @param string $needle The string to test for
 * @return bool True if $haystack begins with $needle, false otherwise
 */
function starts_with($haystack, $needle)
{
    return !strncmp($haystack, $needle, strlen($needle));
}



function delete_all_streams()
{
	$r = new HttpRequest(get_endpoint("/debug/deleteallstreams/"), HttpRequest::METH_GET);
	$r->send();
}


function get_endpoint($path)
{
	return "http://test.webreplay.local$path";
}

?>
