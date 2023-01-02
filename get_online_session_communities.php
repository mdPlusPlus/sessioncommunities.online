<?php
	// requires php-curl


	// some global stuff

	// set timeout for file_get_contents()
	ini_set('default_socket_timeout', 5); // in seconds, default is 60

	// curl timeout is millisecons
	$curl_connecttimeout_ms = 2000; // time for initiation of the connection
	$curl_timeout_ms = 5000;		// max time for whole connection (incl. transfer)

	// do not report warnings (timeouts, SSL/TLS errors)
	error_reporting(E_ALL & ~E_WARNING);

	// regex that matches room join links like http://1.2.3.4:56789/token?public_key=0123456789abcdef
	$room_join_regex = "/https?:\/\/.+\?public_key=[0-9a-f]+/" ; //TODO: How long can a public key be? Most likely exactly 64 chars long

	// room token regex part
	$room_token_regex_part = "[0-9A-Za-z]+"; //TODO: actually correct?

	/*
	 * Some servers don't appear in the wild yet, but can be queried
	 * Ideally this shouldn't be necessary, but it is for now
	 */
	$known_servers = array(
		"http://13.233.251.36:8081",
		"http://open.session.codes",
		"https://open.getsession.org"
	);

	$known_pubkeys = array(
		"13.233.251.36:8081"  => "efcaecf00aebf5b75e62cf1fd550c6052842e1415a9339406e256c8b27cd2039",
		"open.session.codes"  => "c7fbfa183b601f4d393a43644dae11e5f644db2a18c747865db1ca922e632e32",
		"open.getsession.org" => "a03c383cf63c3c4efe67acc52112a6dd734b3a946b9545f488aaa93da7991238"
	);

	// path for HTML output
	$output = "output/index.php";


	// run main function
	main();

	function main() {
		$timestamp = time(); // unix timestamp in seconds

		echo("Running, please wait..." . PHP_EOL);
		echo("This script will usually take approximately 3 minutes to run." . PHP_EOL);
		echo("It will take longer if the Chinese servers are spasming out." . PHP_EOL);

		$html = get_html_from_known_sources();
		$wild_join_links = extract_join_links_from_html($html);
		$servers = get_servers_from_join_links($wild_join_links);
		$servers = reduce_servers($servers);
		$servers = merge_servers_with_known_good_servers($servers); //TODO: Switch merge and reduce?
		$rooms = query_servers_for_rooms($servers);
		$pubkeys = acquire_pubkeys_from_join_links($wild_join_links);
		$pubkeys = merge_pubkeys_with_known_pubkeys($pubkeys);
		$addr_assignments = get_pubkeys_of_servers($servers, $pubkeys);
		$addr_assignments = reduce_addresses_of_pubkeys($addr_assignments);
		$room_assignments = assign_rooms_to_address_assignments($addr_assignments, $rooms);

		$final_join_links = generate_join_links($room_assignments);

//		print_r($servers);
//		print_r($rooms);
//		print_r($addr_assignments);
//		print_r($room_assignments); //TODO: We also assigned empty room arrays. Should probably be fixed

//		print_r($final_join_links);

		$table_html = get_table_html($room_assignments);
		$title = "Self-updating list of active Session Communities";
		$final_html = create_html_page_from_table($table_html, $title, $timestamp);

		// write output to disk
		global $output;
		file_put_contents($output, $final_html); // overwrites existing file
		echo("Done. " .  count($final_join_links) . " unique Session Communities have been found." . PHP_EOL);
	}

	/*
	 * Queries following known sources of join links for Session Communities:
	 * - Awesome Session Open Group List repository on GithUb
	 * - LokiLocker.com Open Groups
	 * - https://session.directory open groups
	 */
	function get_html_from_known_sources() {
		// known open group / community lists
		$asgl   = "https://github.com/GNU-Linux-libre/Awesome-Session-Group-List/raw/main/README.md";
		$ll     = "https://lokilocker.com/Mods/Session-Groups/wiki/Session-Open-Groups";
		$sd_pre = "https://session.directory/?all=groups" ; // this one has to be expanded first

		// get awesome session group list html
		$asgl_html = file_get_contents($asgl);

		// get lokilocker.com html
		$ll_html   = file_get_contents($ll);

		// get session.directory html
		$sd_html = "";
		$sd_pre_html = file_get_contents($sd_pre);
		$sd_pattern    = "/view_session_group_user_lokinet\.php\?id=\d+/";
		preg_match_all($sd_pattern, $sd_pre_html, $sd_links);
		$sd_links = $sd_links[0]; // don't know why
		foreach ($sd_links as &$link) {
			// add prefix "https://session.directory to the sd_links
			$link = str_replace('view_session_group_user_lokinet.php?id=', 'https://session.directory/view_session_group_user_lokinet.php?id=', $link);
			// add html to sd_html
			$sd_html = $sd_html . file_get_contents($link);
		}

		// merge all html into a single string
		return(
			$asgl_html . PHP_EOL .
			$ll_html . PHP_EOL .
			$sd_html
		);
	}

	/*
	 * Extracts all links that match the $room_join_regex
	 * Example: http(s)://whatever:port/?public_key=0123456789abcef
	 * Result is sorted and unique
	 * There's no check for reachability or additional https availability
	 */
	function extract_join_links_from_html($html){
		global $room_join_regex;
		$result = array();
		preg_match_all($room_join_regex, $html, $result);
		$result = $result[0]; // there's only $result[0], no $result[1] or others

		// filter $result[0] because some entries look like this:
		//[106] => http://sog.caliban.org/im?public_key=118df8c6c471ac0468c7c77e1cdc12f24a139ee8a07c6e3bf4e7855640dad821" rel="nofollow">http://sog.caliban.org/im?public_key=118df8c6c471ac0468c7c77e1cdc12f24a139ee8a07c6e3bf4e7855640dad821
		//TODO: Figure out why the regex does match those
		foreach($result as &$entry) {
//			if(str_contains($entry, "\"")) { // str_contains() requires PHP 8
			if(strpos($entry, "\"")) {
				$entry = explode("\"", $entry)[0]; // split on " and take first part
			}
		}

		$result = array_unique($result);
		sort($result);

		return $result;
	}

	/*
	 * Gets all servers from an array of join links
	 * Returns an array that looks like this:
	 * [0] => 1.2.3.4
	 * [1] => 2.3.4.5:12345
	 * [2] => example.com
	 * [3] => dev.test:23456
	 * Result is sorted and unique
	 */
	function get_servers_from_join_links($join_links_arr) {
		$result = array();

		foreach($join_links_arr as $join_link){
			$split  = array();
			$split  = explode("/", $join_link); // http(s): + "" + 1.2.3.4:56789 + "name?public_key=0123456789abcdef"
			$result[] = $split[2]; // 1.2.3.4:56789
		}

		$result = array_unique($result);
		sort($result);

		return $result;
	}

	/*
	 * Checks whether servers are reachable and whether they support https
	 * and makes sure that there are no http/https duplicates
	 * Input is an array of servers without protocol (no http:(s)// in front)
	 * Result is unique and sorted
	 */
	function reduce_servers($servers_arr) {
		$reduced_servers = array();
		$offline_servers = array(); // debug
		foreach($servers_arr as $server) {
			// try https
			$url = "https://" . $server;
			if(url_is_reachable($url)){
				$reduced_servers[] = $url;
			}
			else{
				// try http
				$url = "http://" . $server;
				if(url_is_reachable($url)){
					$reduced_servers[] = $url;
				}
				else {
					$offline_servers[] = $url;
//					echo("Server " . $server . " is not reachable" . PHP_EOL);
				}
			}
		}
		$reduced_servers = array_unique($reduced_servers);
		sort($reduced_servers);

		return $reduced_servers;
	}

	/*
	 * Some servers don't appear in the wild yet, but can be queried
	 * Ideally this shouldn't be necessary, but it is for now
	 * Should be called after reduce_servers()
	 */
	function merge_servers_with_known_good_servers($url_arr){
		$result = array();
		global $known_servers;

		$result = array_merge($url_arr, $known_servers);
		$result = array_unique($result); // just in case we accidentally add a duplicate
		sort($result);

		return $result;
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
//		echo($url . " is " . $retcode . PHP_EOL);
		if ($retcode == 200) {
			return true;
		}
		else {
			return false;
		}
	}

	/*
	 * Takes an input like this:
	 * [0] => http://1.2.3.4
	 * [1] => https://2.3.4.5:12345
	 * [2] => https://example.com
	 * [3] => http://dev.test:23456
	 * and queries the /room JSON API endpoint
	 * Returns a multidimensional array
	 * The first dimension uses the server URL as public_key
	 * The second dimension is an array that contains $room_array array
	 * $room_array arrays contain token, name, users and description
	 */
	function query_servers_for_rooms($url_arr){
		$rooms = array();
		$endpoint = "/rooms";
		$failed_arr = array(); // debug

		// we can't use array_unique later so we make sure the input is unique
		$url_arr = array_unique($url_arr); // not really necessary though
		// we can't use sort or asort later so me do it now
		sort($url_arr); // not really necessary though

		foreach($url_arr as $url) {
			$json_url = $url . $endpoint;
//			$json = file_get_contents($json_url);
			$json = curl_get_contents($json_url); // circumvents flaky routing
//			echo("URL: " . $url . " - JSON URL: " . $json_url . PHP_EOL);
//			echo("JSON: " . $json . PHP_EOL);
			$failed = false;
			if($json) {
				$json_obj = json_decode($json);
				$json_rooms = array();
				// if response was not empty
				if($json_obj) {
					foreach($json_obj as $json_room) {
						$token = $json_room->token; // room "name"
						$room_array = array(
							"token"        => $token,
							"name"         => $json_room->name,
							"active_users" => $json_room->active_users,
							"description"  => $json_room->description
						);

						$json_rooms[$token] = $room_array;
					}

//					print_r($json_rooms);
					$rooms[$url] = $json_rooms;
				}
				else {
					$failed = true;
//					echo($json_url . " failed to decode" . PHP_EOL);
				}
			}
			else {
				$failed = true;
			}

			if($failed) {
				// 404 - could mean it's a legacy server that doesn't provide /room endpoint
				$failed_arr[] = $url;
				$legacy_rooms = query_homepage_for_rooms($url);
				if($legacy_rooms) {
					$rooms[$url] = $legacy_rooms;
				}
			}
		}


		/*$counter = 0;
		foreach($rooms as $room_arr) {
			$counter = $counter + count($room_arr);
		}
		echo("Found " . $counter . " rooms, but there could be duplicates." . PHP_EOL);*/

//		print_r($failed_arr);

		return $rooms;
	}

	/*
	 * For servers that do not provide the /rooms endpoint
	 * Takes same input as query_api_for_rooms(), but only singular URL
	 * Returns array of all available rooms (each its own array with token, name, users and description)
	 * Result is false if no rooms where found
	 */
	function query_homepage_for_rooms($url) {
		$result = array();
		global $room_token_regex_part;
		$contents = file_get_contents($url);
		if($contents) {
			$regex_new = "/\/r\/" . $room_token_regex_part . "/";
			$regex_old = "/\/view\/room\/" . $room_token_regex_part . "/";

			preg_match_all($regex_new, $contents, $rooms);
			$rooms = $rooms[0];
			// if the new regex doesn't match, use the old one
			if(empty($rooms)) {
				preg_match_all($regex_old, $contents, $rooms);
				$rooms = $rooms[0];
			}
			// if one of the two regex has found anything
			if(!empty($rooms)) {
				// we also want the room names (not tokens)
				preg_match_all('/<li.*?><a.*?>(.*?)<\/a><\/li>/', $contents, $names);
				$names = $names[1]; // [1] contains only the contents of the a tags, not the li or a tags themselves
				// at this point the array contents look either like this:
				// /r/token
				// or like this
				// /view/room/token
				// so split by / and use last element
				foreach($rooms as $i => $room) {
					$exploded = explode("/", $room);
					$token = $exploded[count($exploded) - 1]; // take last element
					$room_array = array(
						"token"        => $token,
						"name"         => $names[$i], // take same index in $names array
						"active_users" => -1, // without API we can't query the acutal number
						"description"  => null // same goes for the description
					);
					//$result[] = $token;
					$result[$token] = $room_array;
				}
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}

		$result = array_unique($result);
		//sort($result);

		return $result;
	}

	/*
	 * Returns an array with the server URLs as keys and the public keys an value
	 * "https://server:port" => "somehexstring"
	 */
	function acquire_pubkeys_from_join_links($join_links_arr) {
		$result = array();

		foreach($join_links_arr as $join_link) {
			// example: http://1.2.3.4:56789/token?public_key=0123456789abcdef
			// we split by / and take the index [2] as the server
			$server = explode("/", $join_link)[2];
			// we assume everything behind the "=" is the public key
			$pubkey = explode("=", $join_link)[1];

			$result[$server] = $pubkey;
		}

		return $result;
	}

	/*
	 * Merge pubkeys
	 */
	function merge_pubkeys_with_known_pubkeys($pubkeys_arr) {
		$result = array();
		global $known_pubkeys;
		$result = array_merge($pubkeys_arr, $known_pubkeys);

		return $result;
	}

	/*
	 * Returns an array with the pubkey as index and the server(s) as an value array
	 * Expects the $servers_arr to be with protocol
	 */
	function get_pubkeys_of_servers($servers_arr, $pubkeys_arr) {
		$result = array();
		$server_to_pubkey = array();
//		print_r($servers_arr);
//		print_r($pubkeys_arr);

		// first create an array of all public keys
		// we do this because it is possible that the same server has been added with multiple URLs (but the same public key
		$unique_pubkeys = array();
		foreach($pubkeys_arr as $pk_server => $pk_value) {
			$unique_pubkeys[] = $pk_value;
		}
		$unique_pubkeys = array_unique($unique_pubkeys);
		sort($unique_pubkeys);

		// then assign $server_url => $pubkey
		foreach($servers_arr as $server_url) {
			// split protocol
			$server = explode("//", $server_url)[1];
			$server_to_pubkey[$server_url] = $pubkeys_arr[$server];
		}
//		print_r($server_to_pubkey);

		// but this still has duplicates in it
		// so we get every address for a known pubkey so the reuslt is an array:
		// result[$pubkey] = array("address1", "address2", ...);
		foreach($unique_pubkeys as $pubkey) {
			$addresses = array();
			foreach($server_to_pubkey as $s_address => $s_pubkey) {
				if($pubkey == $s_pubkey) {
					$addresses[] = $s_address;
				}
			}
			$result[$pubkey] = $addresses;
		}

		return $result;
	}

	/*
	 * Input array of type array[pubkey] = array("address1", "address2", ...)
	 * Output array of type array[pubkey] = "address"
	 * Removes pubkeys that do not have an active address
	 * For those with multiple addresses it checks if one of them is not a IP address and then assumes it to be the (primary) domain of the server //TODO: not ideal, but works for now
	 */
	function reduce_addresses_of_pubkeys($pubkey_to_addresses_arr) {
		$result = array();
		foreach($pubkey_to_addresses_arr as $pubkey => $addresses_arr) {
//			print_r($addresses_arr);
//			echo($pubkey . " has count " . count($addresses_arr) . "." . PHP_EOL);
			// has active addresses?
			if(count($addresses_arr) != 0) {
				// has only one active address?
				if(count($addresses_arr) == 1) {
					// add only entry to result
					$result[$pubkey] = $addresses_arr[0];
				}
				else{
					// has more than one active address
					$found_domain = false;
					foreach($addresses_arr as $address) {
						$without_proto = explode("//", $address)[1];
						// has no domain be found yet?
						if(!$found_domain) {
							// (bool)ip2long returns 1 (true) if valid IP //TODO: Does this handle IPv6?
							if(!(bool)ip2long($without_proto)) {
								$found_domain = true;
								// add first found domain to result
								$result[$pubkey] = $address;
							}
						}
					}
					// has no domain been found?
					if(!$found_domain) {
						// them simply add first entry
						$result[$pubkey] = $addresses_arr[0];
					}
				}
			}
		}

		return $result;
	}

	/*
	 * TODO: Description
	 */
	function assign_rooms_to_address_assignments($addr_assignments_arr, $rooms_arr) {
		$result = array();
		foreach($addr_assignments_arr as $pubkey => $address) {
			$result[$pubkey] = array($address, $rooms_arr[$address]);
		}

		return $result;
	}

	/*
	 * TODO: Description
	 */
	function generate_join_links($room_assignments_arr) {
		$result = array();
		// for each server a.k.a. public key do
		foreach($room_assignments_arr as $pubkey => $room_assignment) {
			// for every room do
			foreach($room_assignment[1] as $room_array) {
				// info:
				// $room_array = array(
				//	"token"        => bla,
				//	"name"         => Blabla,
				//	"active_users" => -1,
				//	"description"  => Blabla bla bla
				//);
				$server = $room_assignment[0];
				$join_link = $server . "/" . $room_array["token"] . "?public_key=" . $pubkey;

				$result[] = $join_link;
			}
		}
		$result = array_unique($result); // shouldn't be necessary
		sort($result);

		return $result;
	}

	/*
	 * Writes HTML table with the following info:
	 * Token + shortened pubkey | Name | Description | Users | View Links(?) | Join URL
	 */
	function get_table_html($room_assignments_arr) {
		$shortened_pubkey_length = 4; // shorten pubkey to this length to make room token unique
		// contains the info for each line of the table
		$ordered_table_elements = array();
		// for each server a.k.a. public key do
		foreach($room_assignments_arr as $pubkey => $room_assignment) {
			$server = $room_assignment[0];
			$shortened_pubkey = substr($pubkey, 0, $shortened_pubkey_length); // first X chars of pubkey
			// for every room do
			foreach($room_assignment[1] as $room_array) {
				// info:
				// $room_array = array(
				//	"token"        => bla,
				//	"name"         => Blabla,
				//	"active_users" => -1,
				//	"description"  => Blabla bla bla
				//);

				$join_link = $server . "/" . $room_array["token"] . "?public_key=" . $pubkey;
				$identifier = $room_array["token"] . "+" . $shortened_pubkey;

				$info_array = array(
					"name"         => $room_array["name"],
					"description"  => $room_array["description"],
					"active_users" => $room_array["active_users"],
					"join_link"    => $join_link
				);
				$ordered_table_elements[$identifier] = $info_array;
			}
		}

		// sorting that keeps index association, sort by index
		ksort($ordered_table_elements, SORT_STRING | SORT_FLAG_CASE);
//		print_r($ordered_table_elements);

		$table_lines = array();
		foreach($ordered_table_elements as $id => $content) {
			// https://1.2.3.4:56789/token?public_key=0123456789abcdef
			$join_link = $content["join_link"];

			// get preview links
			$exploded = explode("/", $join_link); // https: + "" + 1.2.3.4:56789 + token?public_key=0123456789abcdef
			$server = $exploded[0] . "//" . $exploded[2];
			$token  = explode("?", $exploded[3])[0];
			$preview_link     = $server . "/r/" . $token . "/";
			$preview_link_alt = $server . "/view/room/" . $token;

			// test if preview_links are 404
			if(!url_is_reachable($preview_link)) {
				if(!url_is_reachable($preview_link_alt)) {
					$preview_link = null; // $preview_link and $preview_link_alt not reachable
				}
				else {
					$preview_link = $preview_link_alt; // $preview_link_alt reachable
				}
			}

			// test if active_users is valid
			$active_users = $content["active_users"];
			/*if($active_users == -1) {
				$active_users = "N/A"; // this breaks sortTable()
			}*/

			$line =
				"	<tr>" . PHP_EOL .
				"		<td class=\"td_identifier\">" . $id . "</td>" . PHP_EOL .
				"		<td>" . $content["name"] . "</td>" . PHP_EOL .
				"		<td>" . $content["description"] . "</td>" . PHP_EOL .
				"		<td class=\"td_users\">" . $active_users . "</td>" . PHP_EOL .
				"		<td><a href=\"" . $preview_link . "\">" . $preview_link . "</a></td>" . PHP_EOL .
				"		<td class=\"td_join_url\">" . substr($join_link, 0, 32) . "..." . PHP_EOL .
				"			<button class=\"copy_button\" onclick=\"copyToClipboard('" . $join_link . "')\">Copy</button>" . PHP_EOL .
				"		</td>" . PHP_EOL .
				"	</tr>" . PHP_EOL;
			$table_lines[] = $line;
		}

		// prefix
		$prefix =
			"<h1 id=\"headline\">Session Communities</h1>" . PHP_EOL .
			"<table id=\"tbl_communities\">" . PHP_EOL .
			"	<tr>" . PHP_EOL .
			"		<th onclick=\"sortTable(0)\" id=\"th_identifier\">Identifier</th>" . PHP_EOL .
			"		<th onclick=\"sortTable(1)\" id=\"th_name\">Name</th>" . PHP_EOL .
			"		<th onclick=\"sortTable(2)\" id=\"th_description\">Description</th>" . PHP_EOL .
			"		<th onclick=\"sortTable(3)\" id=\"th_users\">Users</th>" . PHP_EOL .
			"		<th onclick=\"sortTable(4)\" id=\"th_preview\">Preview</th>" . PHP_EOL .
			"		<th onclick=\"sortTable(5)\" id=\"th_join_url\">Join URL</th>" . PHP_EOL .
			"	</tr>" . PHP_EOL;

		// suffix
		// span over 6 columns (id, name, description, users, preview, join link)
		//$span_count = 6;
		$suffix =
			"</table>" . PHP_EOL .
			"<table id=\"tbl_footer\">" . PHP_EOL .
			"	<tr>" . PHP_EOL .
			//"		<td id=\"td_summary\" colspan=\"" . $span_count . "\">" . count($table_lines) . " unique Session Communities have been found.</td>" . PHP_EOL .
			"		<td id=\"td_summary\">" . count($table_lines) . " unique Session Communities have been found.</td>" . PHP_EOL .
			"	</tr>" . PHP_EOL .
			"	<tr>" . PHP_EOL .
			//"		<td id=\"td_last_checked\" colspan=\"" . $span_count . "\">Last checked X minutes ago.</td>" . PHP_EOL .
			"		<td id=\"td_last_checked\">Last checked X minutes ago.</td>" . PHP_EOL .
			"	</tr>" . PHP_EOL .
			"</table>" . PHP_EOL;

		// concatenate html
		$html = $prefix;
		foreach($table_lines as $line) {
			$html = $html . $line;
		}
		$html = $html . $suffix;

		return $html;
	}

	/*
	 * Build valid HTML5 page from provided table html
	 */
	function create_html_page_from_table($table_html, $title, $timestamp) {
		$pre =
			"<!DOCTYPE html>" . PHP_EOL .
			"<html lang=\"en\">" . PHP_EOL .
			"	<head>" . PHP_EOL .
			"		<link rel=\"icon\" type=\"image/svg+xml\" href=\"favicon.svg\" sizes=\"any\">" . PHP_EOL .
			"		<link rel=\"stylesheet\" href=\"styles.css\">" . PHP_EOL .
			"		<script src=\"script.js\" defer></script>" . PHP_EOL .
			"		<title>" . $title . "</title>" . PHP_EOL .
			"	</head>" . PHP_EOL .
			"	<body onload=\"setLastChecked(" . $timestamp . ")\">" . PHP_EOL;
		$post =
			"	</body>" . PHP_EOL .
			"</html>" . PHP_EOL;

		$html5 = $pre . $table_html . $post;

		return $html5;
	}

	/*
	 * file_get_contents alternative that circumvents flaky routing to Chinese servers
	 */
	function curl_get_contents($url) {
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
