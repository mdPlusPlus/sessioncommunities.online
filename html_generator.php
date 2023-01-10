<?php
	/*
	 * Build valid HTML5 page from provided (table) html
	 */
	function create_html_page_from_html_data($html_data, $title, $timestamp) {
		$pre =
			"<!DOCTYPE html>" . PHP_EOL .
			"<html lang=\"en\">" . PHP_EOL .
			"	<head>" . PHP_EOL .
			"		<meta charset=\"UTF-8\">" . PHP_EOL .
			"		<link rel=\"icon\" type=\"image/svg+xml\" href=\"favicon.svg\" sizes=\"any\">" . PHP_EOL .
			"		<link rel=\"stylesheet\" href=\"styles2.css\">" . PHP_EOL .
			"		<script src=\"script2.js\" defer></script>" . PHP_EOL .
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
				"		<td><img class=\"qr-code-icon\" src=\"qrcode-solid.svg\" onclick=\"displayQRModal('" . $id . "')\" alt=\"Pictogram of an QR code\"></td>" . PHP_EOL .
				"		<td class=\"td_join_url\"><a href=\"" . $content["join_link"] . "\">" . $content["join_link"] . "</a></td>" . PHP_EOL .
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
			"		<th id=\"th_qr\">QR</th>" . PHP_EOL .
			"		<th onclick=\"sortTable(7)\" id=\"th_join_url\">Join URL</th>" . PHP_EOL .
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

	/*
	 * Needed until all Community servers reliably generate QR codes again
	 */
	function get_qr_img_element_from_join_url($join_url) {
		$data = get_base64_qr_code_from_join_url($join_url);
		$mime = "image/png";
		$src = "data:" . $mime . ";base64," . $data;

		$result =
			"<img src=\"" . $src . "\" " .
			"alt=\"Community join link encoded as QR code image\" " .
			"class=\"qr-code\">";
//		echo($result . PHP_EOL);

		return $result;
	}

	/*
	 * Use Google API to generate QR codes and encode them as base64
	 */
	function get_base64_qr_code_from_join_url($join_url) {
		// https://developers.google.com/chart/infographics/docs/qr_codes
		$data =  urlencode($join_url);
		$size = "512x512";
		//$error_correction_level = ""; // "chld=" L = 7%, M = 15%, Q = 25%, H = 30%
		$api_url =
			"https://chart.googleapis.com/chart?cht=qr" .
			"&chs=" . $size .
			"&chl=" . $data;
		$img_base64 = base64_encode(file_get_contents($api_url));
//		echo($img_base64 . PHP_EOL);

		return $img_base64;
	}

	/*
	 * TODO: Description
	 */
	function create_qr_code_modals_html($info_arrays) {
		$html = "";
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
			$img_elem = get_qr_img_element_from_join_url($content["join_link"]); // TODO: incorporate ID to use in js function
			$modal_html =
				"<div id=\"modal_" . $id . "\" class=\"qr-code-modal\">" . PHP_EOL .
				"	<div class=\"qr-code-modal-content\">" . PHP_EOL .
				"		<span class=\"qr-code-modal-close\" onclick=\"hideQRModal('" . $id . "')\">&times;</span>" . PHP_EOL .
				"		" . $img_elem . PHP_EOL .
				"	</div>" . PHP_EOL .
				"</div>" . PHP_EOL;

			$html = $html . $modal_html;
		}

		return $html;
	}

	/*
	 * TODO: Description
	 */
	function generateHTML($timestamp, $info_arrays) {
		$title = "Self-updating list of active Session Communities";

		$modal_html = create_qr_code_modals_html($info_arrays);
		$table_html = get_table_html($info_arrays);

		$html =
			$modal_html . PHP_EOL .
			$table_html . PHP_EOL;

		$final_html = create_html_page_from_html_data($html, $title, $timestamp);

		return $final_html;
	}

?>
