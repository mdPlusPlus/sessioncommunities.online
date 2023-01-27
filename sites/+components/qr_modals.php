<?php
	function room_qr_code_cached($room_id) {
		global $QR_CODES;
		return "$QR_CODES/$room_id.png";
	}

	/**
	 * Derive URL of the invite code for a given room.
	 */
	function room_invite_png($room_id, $room) {
		return $room->preview_link . "invite.png";
	}

	/*
	 * Fetch QR codes from SOGS server and encode them as base64
	 */
	function base64_qr_code($room_id, $room, $size = "512x512") {
		$png_cached = room_qr_code_cached($room_id);
		if (file_exists($png_cached)) {
//			fwrite(STDERR, "QR code found for " . $room_id . PHP_EOL);
			return base64_encode(file_get_contents($png_cached));
		}
//		fwrite(STDERR, "QR code NOT found for " . $room_id . PHP_EOL);
		$png = file_get_contents(room_invite_png($room_id, $room));
		file_put_contents($png_cached, $png);
		return base64_encode($png);
	}

	file_exists($QR_CODES) or mkdir($QR_CODES, 0700);
?>

<div id="modal-container">
<?php foreach ($rooms as $id => $room): ?>
	<div id="modal_<?=$id?>" class="qr-code-modal">
		<div class="qr-code-modal-content">
			<span class="qr-code-modal-close" onclick='hideQRModal("<?=$id?>")'>
				&times;
			</span>
			<img
				src="data:image/png;base64,<?=base64_qr_code($id, $room)?>"
				alt="Community join link encoded as QR code"
				class="qr-code"
				loading="lazy"
			>
		</div>
	</div>
<?php endforeach; ?>
</div>
