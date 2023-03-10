<?php
	// requires php-curl

	// require other php files
	require_once "getenv.php";
	require_once "utils/server-utils.php";
	include_once "$LANGUAGES_ROOT/language_flags.php"; // actually runs fine without it

	// room token regex part, must consist of letters, numbers, underscores, or dashes: https://github.com/oxen-io/session-pysogs/blob/dev/administration.md
	$room_token_regex_part = "[0-9A-Za-z-_]+";

	/*
	 * This regex uses the following components:
	 * - https?:\/\/   - This matches "http" or "https" followed by "://"
	 * - [^\/]+\/      - This matches one or more characters that are not a forward slash, followed by a forward slash
	 * - [0-9A-Za-z-]+  - This matches one or more alphanumeric characters or dash (room token)
	 * - \?public_key= - This matches a question mark followed by the text "public_key="
	 * - [0-9A-Fa-f]{64}  - This matches 64 hexadecimal digits (0-9, A-F and a-f)
	 * This regex should match strings in the following format:
	 * http(s)://[server]/[room_token]?public_key=[64_hexadecimal_digits]
	 */
	$room_join_regex = "/https?:\/\/[^\/]+\/" . $room_token_regex_part . "\?public_key=[0-9A-Fa-f]{64}/";

	/*
	 * Some servers don't appear in the wild yet, but can be queried
	 * Ideally this shouldn't be necessary, but it is for now
	 */
	$known_servers = array(
		// "http://116.203.51.179", // removed by request
		// "http://13.233.251.36:8081", // found via shodan.io, but now offline
		"http://164.92.176.135", // found via shodan.io
		"http://176.119.147.102", // found via shodan.io
		// "http://192.227.193.159", // found via shodan.io, removed out of decency
		"http://5.39.117.98", // found via reddit
		"http://60a9fc9.online-server.cloud", // found via shodan.io
		"http://93.95.230.10", // found via shodan.io
		"http://94.176.239.60", // found via shodan.io
		// "http://bitcoincash.tokyo", // found via shodan.io, removed by request
		"http://captain.geekgalaxy.com", // found via shodan.io
		"http://session.hwreload.it", // found via shodan.io
		"http://sogs.k9net.org", // found via shodan.io
		"https://open.getsession.org", // found via shodan.io
	);

	$known_pubkeys = array(
		// "server_without_proto" => "64 char hex public key"
		"116.203.51.179"              => "39016f991400c35a46e11e06cb2a64d6d8ab6652e484a556b14f7cf57ed7e73a",
		"13.233.251.36:8081"          => "efcaecf00aebf5b75e62cf1fd550c6052842e1415a9339406e256c8b27cd2039",
		"164.92.176.135"              => "e529311ec8fb6fdb950aaa4fb71fc4da3ea59c6c9ba2886708b9538eea6aa213",
		"176.119.147.102"             => "e093994156ec92e4c13d0387208bfa48ae56dd88b8f60a03980d9ef048af1e3f",
		"192.227.193.159"             => "426453e0e991235b62bc5f35f36d5a204e64b2d8b44e971609add6a10aac6674",
		"5.39.117.98"                 => "4bec6d6c7b502a819b47b3af75272c0774ab1214fba33fb5ec29949f864eb028",
		"60a9fc9.online-server.cloud" => "7908bcd748313355f99e62f9c1f11c395d04019410edb7ee1618dbe26a423c4f",
		"93.95.230.10"                => "b501f2dc7dc912aa0981b0ba10f2ba739d2f729a7d9b37022aee505aaf72807c",
		"94.176.239.60"               => "2cbde327e9da216af9a69876bc57e16cc0c540b0aa2dfecdd1c115e67993b040",
		"bitcoincash.tokyo"           => "c9a30da579d8fdaeded009d1afa3de573dd783e3081b7504c4cbfa470e5db378",
		"captain.geekgalaxy.com"      => "7242ad657dc2dd20e902a6fa82c34465907b67e80daf50173f38d5745abbaa24",
		"open.getsession.org"         => "a03c383cf63c3c4efe67acc52112a6dd734b3a946b9545f488aaa93da7991238",
		"session.hwreload.it"         => "4b3e75eedd2116b4dab0bcb6443b0e9fbfce7bcf1d35970bdad8a57a0113fb20",
		"sogs.k9net.org"              => "fdcb047eb78520e925fda512a45ae74c6e2de9e0df206b3c0471bf1509919559"
	);

	// path for HTML output
	// $output = "output/index.html";
	
	// path for room data output
	$output = "$ROOMS_FILE";
	
	file_exists($CACHE_ROOT) or mkdir($CACHE_ROOT, 0700);

	// run main function
	main();

	function main() {
		$timestamp = time(); // unix timestamp in seconds

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
		$info_arrays = generate_info_arrays($room_assignments);
		
//		$final_join_links = generate_join_links($room_assignments);


//		$final_html = generateHTML($timestamp, $info_arrays);

//		print_r($wild_join_links);
//		print_r($servers);
//		print_r($rooms);
//		print_r($pubkeys);
//		print_r($addr_assignments);
//		print_r($room_assignments);
//		print_r($final_join_links);
//		print_pinned_messages($room_assignments);

		// write output to disk
		global $output;
		file_put_contents($output, json_encode($info_arrays)); // overwrites existing file
		log_info("Done. ");
		log_info("Found " . count($info_arrays) . " unique Session Communities on " . count_servers($info_arrays) . " servers." . PHP_EOL);
	}

	/*
	 * Queries following known sources of join links for Session Communities:
	 * - Awesome Session Open Group List repository on GitHub
	 * - LokiLocker.com Open Groups
	 * - https://session.directory open groups
	 */
	function get_html_from_known_sources() {
		// known open group / community lists
		$asgl   = "https://raw.githubusercontent.com/GNU-Linux-libre/Awesome-Session-Group-List/main/README.md";
		$ll     = "https://lokilocker.com/Mods/Session-Groups/wiki/Session-Open-Groups";
		$sd_pre = "https://session.directory/?all=groups" ; // this one has to be expanded first

		// get awesome session group list html
		log_info("Requesting Awesome Session Group list.");
		$asgl_html = file_get_contents($asgl);
//		log_debug($http_response_header[0]); // Supposed to be "HTTP/1.1 200 OK"

		// get lokilocker.com html
		log_info("Requesting Lokilocker Mods Open Group list.");
		$ll_html   = file_get_contents($ll);
//		log_debug($http_response_header[0]); // Supposed to be "HTTP/1.1 200 OK"

		// get session.directory html
		$sd_html = "";
		log_info("Requesting session.directory list.");
		$sd_pre_html = file_get_contents($sd_pre);
//		log_debug($http_response_header[0]); // Supposed to be "HTTP/1.1 200 OK"

		$sd_pattern    = "/view_session_group_user_lokinet\.php\?id=\d+/";
		preg_match_all($sd_pattern, $sd_pre_html, $sd_links);
		$sd_links = $sd_links[0];
		foreach ($sd_links as &$link) {
			// add prefix "https://session.directory to the sd_links
			$link = str_replace('view_session_group_user_lokinet.php?id=', 'https://session.directory/view_session_group_user_lokinet.php?id=', $link);
			// add html to sd_html
//			log_debug("Requesting " . $link);
			$sd_html = $sd_html . file_get_contents($link) . PHP_EOL;
//			log_debug($http_response_header[0]); // Supposed to be "HTTP/1.1 200 OK"
		}

		log_info("Done fetching sources.");

		// merge all html into a single string
		return (
			$asgl_html . PHP_EOL .
			$ll_html . PHP_EOL .
			$sd_html . PHP_EOL
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
//		print_r($result);
		$result = $result[0]; // there's only $result[0], no $result[1] or others

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
		log_info("Checking found servers for availability.");
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

//		print_r($offline_servers);

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
	function query_servers_for_rooms($url_arr) {
		log_info("Querying available servers for rooms.");
		$rooms = array();
		$failed_arr = array(); // debug

		// we can't use array_unique later so we make sure the input is unique
		$url_arr = array_unique($url_arr); // not really necessary though
		// we can't use sort or asort later so me do it now
		sort($url_arr); // not really necessary though
		// we could probably use ksort or something else that persists the keys

		foreach($url_arr as $url) {
			$query_result = query_single_servers_for_rooms($url, $failed_arr);
			if($query_result) {
				$rooms[$url] = $query_result;
			}
		}

//		print_r($failed_arr);

		return $rooms;
	}

	/*
	 * TODO: Description
	 */
	function query_single_servers_for_rooms($server_url, &$failed_arr = null) {
		$result = array();
		$endpoint = "/rooms?all=1";
		$json_url = $server_url . $endpoint;
		log_info("Polling $server_url for rooms.");
		$json = curl_get_contents($json_url); // circumvents flaky routing, don't use file_get_contents
//		echo("URL: " . $server_url . " - JSON URL: " . $json_url . PHP_EOL);
//		echo("JSON: " . $json . PHP_EOL);
		$failed = false;
		if($json) {
			$json_obj = json_decode($json);
			$json_rooms = array();
			// if response was not empty
			if($json_obj) {
				log_info("Received response from $server_url.");
				foreach($json_obj as $json_room) {
					$token = $json_room->token; // room "name"
					$users_per_second = $json_room->active_users / $json_room->active_users_cutoff;
					$seconds_in_a_week = 604800;
					$weekly_active_users = floor($users_per_second * $seconds_in_a_week);
//					echo($token . " has " . $users_per_second . " UPS." . PHP_EOL);
//					echo($token . " has " . $weekly_active_users . " WAU. (" . $json_room->active_users . ")" . PHP_EOL);
					$room_array = array(
						"token"        => $token,
						"name"         => $json_room->name,
						"active_users" => $weekly_active_users,
						"description"  => $json_room->description
					);

					$json_rooms[$token] = $room_array;
				}
//				print_r($json_rooms);
				$result = $json_rooms;
			}
			else {
				$failed = true;
//				echo($json_url . " failed to decode" . PHP_EOL);
				}
			}
		else {
			$failed = true;
		}

		if($failed) {
			// 404 - could mean it's a legacy server that doesn't provide /room endpoint
//			echo("Failed json_url: " . $json_url . PHP_EOL);
			if(!is_null($failed_arr)) {
				// if $failed_arr has been used as parameter, add failed URL to it
				$failed_arr[] = $server_url;
//				echo("Failed: " . $server_url . PHP_EOL);
			}
			$legacy_rooms = query_homepage_for_rooms($server_url);
			if($legacy_rooms) {
				$result = $legacy_rooms;
			} else {
				log_info("Failed to receive response from $server_url.");
				$result = null;
			}
		}

//		print_r($failed_arr);

		return $result;
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
			$regex_old = "/\/view\/room\/" . $room_token_regex_part . "/"; // @legacy

			preg_match_all($regex_new, $contents, $rooms);
			$rooms = $rooms[0];
			// if the new regex doesn't match, use the old one // @legacy
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
						"active_users" => -1, // without API we can't query the actual number
						"description"  => null // same goes for the description
					);

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
		$result = array(); // will hold the final $server => $pubkey data
		$temp = array();   // will hold temporary $server => array("pubkey1", "pubkey2", ...) data
		$server_to_server_url = array(); // "example.com" => "https://example.com" - ugly! TODO: find more elegant solution

		// first pass (will collect multiple pubkeys for each server if multiple are found)
		foreach($join_links_arr as $join_link) {
			// example: http://1.2.3.4:56789/token?public_key=0123456789abcdef
			$exploded = explode("/", $join_link);
			// we split by / and take the index [2] as the server
			$server = $exploded[2];
			// we split by / and take the index [0] and $server as the server url
			$server_url = $exploded[0] . "//" . $server; // required for visit_first_room_of_server_to_acquire_public_key
			// we assume everything behind the "=" is the public key
			$pubkey = explode("=", $join_link)[1];

			$temp[$server][] = $pubkey;
			$server_to_server_url[$server] = $server_url;
		}

		// second pass
		// will filter the pubkeys
		// and if different pubkeys for the same server were found and will query server
		foreach($temp as $server => $pubkey_arr) {
			$uniq_arr = array_unique($pubkey_arr);
			if(count($uniq_arr) >= 1) {
				if(count($uniq_arr) == 1) {
					$result[$server] = $uniq_arr[0]; // if only one unique pubkey was found use that
				}
				else { // multiple unique pubkeys were found
					echo("Multiple public keys found for server " . $server . "." . PHP_EOL);
					print_r($uniq_arr);

					//$result[$server] = $uniq_arr[0]; // placeholder

					$actual_pubkey = visit_first_room_of_server_to_acquire_public_key($server_to_server_url[$server]);

					echo("Server responded with " . $actual_pubkey . PHP_EOL);
					$result[$server] = $actual_pubkey;
				}
			} // else (<= 1) do nothing
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
		// so we get every address for a known pubkey so the result is an array:
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
	 * Returns an array that uses the public key as the index
	 * and assigns an array that has the server URL as index [0],
	 * and an array with all the room arrays as index[1]
	 * Example:
	 * [49ac5595058829c961eea6f60c44914cd08ea9b4c463d657fc82904eb2a89623] => Array (
	 *		[0] => https://2hu-ch.org
	 *		[1] => Array (
	 *			[animu] => Array (
	 *				[token] => animu
	 *				[name] => animu
	 *				[active_users] => 34
	 *				[description] =>
	 *			)
	 *			[cryptography] => Array (
	 *				[token] => cryptography
	 *				[name] => cryptography
	 *				[active_users] => 14
	 *				[description] =>
	 *			)
	 *))
	 */
	function assign_rooms_to_address_assignments($addr_assignments_arr, $rooms_arr) {
		$result = array();
		foreach($addr_assignments_arr as $pubkey => $address) {
			// only assign room array when one can be found in $rooms_arr
			if($rooms_arr[$address]) {
				$result[$pubkey] = array($address, $rooms_arr[$address]);
			}
		}

		return $result;
	}

	/*
	 * TODO: Description
	 * This function is only used for debugging
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
	 * Test if preview_links are 404 and return the right one (or null) // @legacy
	 */
	function get_preview_link($server_url, $token) {
		$preview_link     = $server_url . "/r/" . $token . "/";
		$preview_link_alt = $server_url . "/view/room/" . $token;

		$result = $preview_link;
		if(!url_is_200($preview_link)) {
			if(!url_is_200($preview_link_alt)) {
				// $preview_link and $preview_link_alt not reachable
				//$result = null;
				$result = $preview_link; // assume preview_link to be the valid one TODO: Why is it empty sometimes?
			}
			else {
				$result = $preview_link_alt; // $preview_link_alt reachable
			}
		}

		return $result;
	}

	/*
	 * Queries the first found room for a server for its actual public key
	 */
	function visit_first_room_of_server_to_acquire_public_key($server_url) {
		global $room_join_regex;
		$result = null;

		$rooms = query_single_servers_for_rooms($server_url);
//		print_r($rooms);
		if($rooms) {
			$room_to_visit = $rooms[array_key_first($rooms)]; // use first room e.g. $rooms["offtopic"]
//			print_r($room_to_visit);
			$token  = $room_to_visit["token"];
			$preview_link = get_preview_link($server_url, $token); // @legacy
//			var_dump($preview_link);
			$preview_contents = file_get_contents($preview_link);
//			print_r($preview_contents);
			$join_links = array();
			preg_match_all($room_join_regex, $preview_contents, $join_links);
//			print_r($join_links);
			$first_join_link = $join_links[0][0]; // first found join link
			$result = explode("=", $first_join_link)[1]; // assume right of "=" is public key
//			var_dump($result);
		}

		return $result;
	}

	/*
	 * TODO: Description
	 */
	function generate_info_arrays($room_assignments_arr) {
		global $languages; // language_flags.php
		$shortened_pubkey_length = 4; // shorten pubkey to this length to make room token unique
		$info_arrays = array(); // contains the info for each community, will be the returned as result

		// for each server a.k.a. public key do
		foreach($room_assignments_arr as $pubkey => $room_assignment) {
			$server_url = $room_assignment[0];
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

				$join_link = $server_url . "/" . $room_array["token"] . "?public_key=" . $pubkey;
				$identifier = $room_array["token"] . "+" . $shortened_pubkey;
				$preview_link = get_preview_link($server_url, $room_array["token"]); // @legacy

				// debug logging - does not work anymore, since $preview_link will not be empty when failed
				/*
				if(!$preview_link || $preview_link == "") {
					echo("Preview link is empty. Dumping variables." . PHP_EOL);
					echo("Join link: " . $join_link . PHP_EOL);
					echo("Server: " . $server_url. PHP_EOL);
					echo("Token: " . $room_array["token"] . PHP_EOL);
				}
				*/

				$info_array = array(
					"name"         => $room_array["name"],
					"language"     => $languages[$identifier], // example: $languages["deutsch+118d"] = "????????"
					"description"  => $room_array["description"],
					"active_users" => $room_array["active_users"],
					"preview_link" => $preview_link,
					"join_link"    => $join_link
				);
				$info_arrays[$identifier] = $info_array;
			}
		}

		// sorting that keeps index association, sort by index
		ksort($info_arrays, SORT_STRING | SORT_FLAG_CASE);

		return $info_arrays;
	}

	/*
	 * Debug function to see which communities use pinned messages already
	 */
	function print_pinned_messages($room_assignments_arr) {
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
				$server_url = $room_assignment[0];
				$room_json_url = $server_url . "/room/" . $room_array["token"];
				echo($room_json_url . PHP_EOL);
				$contents = file_get_contents($room_json_url);
				if($contents) {
//					print_r($contents);
					$json_obj = json_decode($contents);
					$pinned_messages = $json_obj->pinned_messages;
					echo("Pinned messages:" . PHP_EOL);
					print_r($pinned_messages);
				}
			}
		}
	}

?>
