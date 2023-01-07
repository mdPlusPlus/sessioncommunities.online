<?php
	/*
	 * Build valid HTML5 page from provided (table) html
	 */
	function create_html_page_from_html_data($html_data, $title, $timestamp) {
		$pre =
			"<!DOCTYPE html>" . PHP_EOL .
			"<html lang=\"en\">" . PHP_EOL .
			"	<head>" . PHP_EOL .
			"		<link rel=\"icon\" type=\"image/svg+xml\" href=\"favicon.svg\" sizes=\"any\">" . PHP_EOL .
			"		<link rel=\"stylesheet\" href=\"styles.css\">" . PHP_EOL .
			"		<script src=\"script.js\" defer></script>" . PHP_EOL .
			"		<title>" . $title . "</title>" . PHP_EOL .
			"	</head>" . PHP_EOL .
			"	<body onload=\"onLoad(" . $timestamp . ")\">" . PHP_EOL;

		$post =
			"	</body>" . PHP_EOL .
			"</html>" . PHP_EOL;

		$html5 = $pre . $html_data . $post;

		return $html5;
	}

	/*
	 * Writes HTML table with the following info:
	 * Token + shortened pubkey | Name | Description | Users | View Links(?) | Join URL
	 */
	function get_table_html($info_arrays) {
		$table_lines = array();
		foreach($info_arrays as $id => $content) {
			/*
			 * $id is "room token+shortened_pubkey", e.g. "example+09af"
			 * Each $content looks like this:
			 * $info_array = array(
			 * 		"name"         => "Name of the room",
			 * 		"language"     => "🇩🇪",
			 * 		"description"  => "Some text that describes the community",
			 * 		"active_users" => 1234,
			 * 		"preview_link" => "https://example.com/r/example",
			 * 		"join_link"    => "https://example.com/example?public_key=[64_hex_chars]"
			 * );
			 */
			$exploded = explode("/", $content["join_link"]);  // https: + "" + 1.2.3.4:56789 + token?public_key=0123456789abcdef
			$server_url = $exploded[0] . "//" . $exploded[2]; // extract server_url
			$token  = explode("?", $exploded[3])[0];          // extract token

			$line =
				"	<tr id=\"" . $id . "\">" . PHP_EOL .
				"		<td class=\"td_identifier\">" . $id . "</td>" . PHP_EOL .
				"		<td>" . $content["language"] . "</td>" . PHP_EOL .
				"		<td>" . $content["name"] . "</td>" . PHP_EOL .
				"		<td>" . $content["description"] . "</td>" . PHP_EOL .
				"		<td class=\"td_users\">" . $content["active_users"] . "</td>" . PHP_EOL .
				"		<td><a href=\"" . $content["preview_link"] . "\">" . $content["preview_link"] . "</a></td>" . PHP_EOL .
				"		<td class=\"td_join_url\">" . substr($content["join_link"], 0, 32) . "..." . PHP_EOL .
				"			<button class=\"copy_button\" onclick=\"copyToClipboard('" . $content["join_link"] . "')\">Copy</button>" . PHP_EOL .
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
			"		<th onclick=\"sortTable(1)\" id=\"th_language\">L</th>" . PHP_EOL .
			"		<th onclick=\"sortTable(2)\" id=\"th_name\">Name</th>" . PHP_EOL .
			"		<th onclick=\"sortTable(3)\" id=\"th_description\">Description</th>" . PHP_EOL .
			"		<th onclick=\"sortTable(4)\" id=\"th_users\">Users</th>" . PHP_EOL .
			"		<th onclick=\"sortTable(5)\" id=\"th_preview\">Preview</th>" . PHP_EOL .
			"		<th onclick=\"sortTable(6)\" id=\"th_join_url\">Join URL</th>" . PHP_EOL .
			"	</tr>" . PHP_EOL;

		// suffix
		$suffix =
			"</table>" . PHP_EOL .
			"<table id=\"tbl_footer\">" . PHP_EOL .
			"	<tr>" . PHP_EOL .
			"		<td id=\"td_summary\">" . count($table_lines) . " unique Session Communities on " . count_servers($info_arrays) . " servers have been found.</td>" . PHP_EOL .
			"	</tr>" . PHP_EOL .
			"	<tr>" . PHP_EOL .
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
?>
