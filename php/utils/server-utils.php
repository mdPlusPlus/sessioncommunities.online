<?php
	/*
	 * Counts every unique server from given $info_arrays and returns the count
	 */
	function count_servers($info_arrays) {
		$servers = array();
		foreach($info_arrays as $i_arr) {
			// https://sogs.example.com:1234/token?public_key=...
			$join_link = $i_arr["join_link"];
			// https: + "" + sogs.example.com:1234 + token?public_key=...
			$exploded = explode("/", $join_link); 
			$servers[] = $exploded[0] . "//" . $exploded[2];
		}
		$servers = array_unique($servers);
		sort($servers);
//		print_r($servers);

		return count($servers);
	}
	
	function truncate($url, $len) {
		return (strlen($url) > $len + 3)
			? substr($url, 0, $len).'...'
			: $url;
	}
	
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

		if ($retcode != 0) {
//			log_debug($url . " is " . $retcode . ".");
			return true;
		}
		else {
//			log_debug($url . " is " . $retcode . ".");
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

		if ($retcode == 200) {
//			log_debug($url . " is " . $retcode . ".");
			return true;
		}
		else {
//			log_debug($url . " is " . $retcode . ".");
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
		$sleep = 2; // sleep between tries in seconds
		$retries = 120;
//		$retries = 10; // debug
		// takes at most ($timeout + $sleep) * retries seceonds
		// 3 + 2 * 150 = 5 * 120 = 600s = 10m

		$contents = false;
		$retcode = -1;
		$counter = 1;

		while(!$contents && $counter <= $retries && $retcode != 404) {
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

//			log_debug("Trial #" . $counter . " for " . $url . " returned code " . $retcode . ".");
			$counter++;
			sleep($sleep);
		}

		if ($retcode != 200) {
			return false;
		} else {
			return $contents;
		}
	}
?>
