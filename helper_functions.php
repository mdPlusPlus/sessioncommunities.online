<?php
	/*
	 * Helper function for reduce_servers
	 */
	function url_is_reachable($url) {
		global $curl_connecttimeout_ms;
		global $curl_timeout_ms;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS , $curl_connecttimeout_ms);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, $curl_timeout_ms);
		curl_exec($ch);
		$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
//		echo($url . " is " . $retcode . PHP_EOL);

		if ($retcode != 0) {
			return true;
		}
		else {
//			echo($url . " is " . $retcode . PHP_EOL);
			return false;
		}
	}

	/*
	 * Helper function for to decide room preview link
	 */
	function url_is_200($url) {
		global $curl_connecttimeout_ms;
		global $curl_timeout_ms;
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS , $curl_connecttimeout_ms);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, $curl_timeout_ms);
		curl_exec($ch);
		$retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
//		echo($url . " is " . $retcode . PHP_EOL);

		if ($retcode == 200) {
			return true;
		}
		else {
//			echo($url . " is " . $retcode . PHP_EOL);
			return false;
		}
	}

    /*
	 * file_get_contents alternative that circumvents flaky routing to Chinese servers
	 */
	function curl_get_contents($url) {
		// use separate timeouts to reliably get data from Chinese server with repeated tries
		$connecttimeout = 2; // wait at most X seconds to connect
		$timeout = 3; // can't take longer than X seconds for the whole curl process
		$sleep = 2;	// sleep between tries in seconds
		$retries = 120;
		// takes at most ($timeout + $sleep) * retries seceonds
		// 3 + 2 * 150 = 5 * 120 = 600s = 10m

		$contents = false;
		$retcode = 404;
		$counter = 1;

		while(!$contents && $counter <= $retries) {
//			echo("Trial #" . $counter . PHP_EOL);
			$curl = curl_init($url);
//			curl_setopt($curl, CURLOPT_VERBOSE, true);

			curl_setopt($curl, CURLOPT_AUTOREFERER, true);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $connecttimeout);
			curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);

			$contents = curl_exec($curl);
			$retcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

			curl_close($curl);

			$counter++;
			sleep($sleep);
		}

		if($retcode != 200) {
			return false;
		} else {
			return $contents;
		}
	}
?>
