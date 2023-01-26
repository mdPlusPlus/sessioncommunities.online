<?php
	/*
	 * @Deprecated
	 */
	function room_qr_code_cached($room_id) {
		global $QR_CODES;
		return "$QR_CODES/$room_id.png";
	}

	/*
	 * Takes join URL and derives the invite.png path from it
	 */
	function room_qr_code_native($join_url) {
		// Ex.: https://open.getsession.org/session?public_key=[...]
		// Goal: https://open.getsession.org/r/session/invite.png
		// Note: No @legacy support (Ex.: https://reccacon.com/view/Ukraine/invite.png)
		// TODO: How does this behave with unreliable connections to Chinese servers?
		$exploded = explode("/", explode("?", $join_url)[0]); // everything before "?"
		$png_url =
			$exploded[0] . "//" .         // https://
			$exploded[2] . "/r/" .        // open.getsession.org/r/
			$exploded[3] . "/invite.png"; // session/invite.png

//		fwrite(STDERR, "PNG URL: " . $png_url . PHP_EOL);
		return $png_url;
	}

	/*
	 * @Deprecated
	 * Use Google API to generate QR codes and encode them as base64
	 */
	function base64_qr_code($room_id, $join_url, $size = "512x512") {
		// Could use http_build_query() instead, but I won't break what works.  
		// https://developers.google.com/chart/infographics/docs/qr_codes
		$png_cached = room_qr_code_cached($room_id);
		if (file_exists($png_cached)) 
//			fwrite(STDERR, "QR code found for " . $room_id . PHP_EOL);
			return base64_encode(file_get_contents($png_cached));
//			fwrite(STDERR, "QR code NOT found for " . $room_id . PHP_EOL);
		$data = urlencode($join_url);
		$api_url =
			"https://chart.googleapis.com/chart?cht=qr" .
			"&chs=$size" .
			"&chl=$data" .
			"&chld=L|0"; 
			// error correction level: L = 7%, M = 15%, Q = 25%, H = 30% 
			// | margin in number of rows
		$png = file_get_contents($api_url);
		file_put_contents($png_cached, $png);
		return base64_encode($png);
	}
	
	file_exists($QR_CODES) or mkdir($QR_CODES, 0700);
?>

<?php foreach ($rooms as $id => $room): ?>
<div id="modal_<?=$id?>" class="qr-code-modal">
	<div class="qr-code-modal-content">
		<span class="qr-code-modal-close" onclick='hideQRModal("<?=$id?>")'>
			&times;
		</span>
		<!--
		<img 
			src="data:image/png;base64,<?=base64_qr_code($id, $room->join_link)?>"
			alt="Community join link encoded as QR code"
			class="qr-code"
			loading="lazy"
		>
		-->
		<img
			src="<?=room_qr_code_native($room->join_link)?>"
			alt="Community join link encoded as QR code"
			class="qr-code"
			loading="lazy"
			referrerpolicy="no-referrer"
		>
	</div>
</div>
<?php endforeach; ?>
