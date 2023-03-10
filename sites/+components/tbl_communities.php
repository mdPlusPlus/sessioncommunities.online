<?php
	require_once "$PROJECT_ROOT/php/utils/server-utils.php";

	// Once handlers are attached in JS, this check ceases to be useful.
	function column_sortable($id) {
		// Join URL contents are not guaranteed to have visible text.
		return $id != "qr" && $id != "preview" && $id != "join_url";
	}
	
	function sort_onclick($colno) {
		global $TABLE_COLUMNS;
		$column = $TABLE_COLUMNS[$colno];
		if (!column_sortable($column['id'])) return "";
		return " onclick='sortTable($colno)' title='Click to sort this column'";
	}
	
	$TABLE_COLUMNS = [
		['id' => "identifier", 'name' => "Identifier"],
		['id' => "language", 'name' => "L"],
		['id' => "name", 'name' => "Name"],
		['id' => "description", 'name' => "Description"],
		['id' => "users", 'name' => "Users"],
		['id' => "preview", 'name' => "Preview"],
		['id' => "qr", 'name' => "QR"],
		['id' => "server_icon", 'name' => "Server"],
		['id' => "join_url", 'name' => "Join URL"],
	];
?>

<table id="tbl_communities">
	<tr>
<?php foreach ($TABLE_COLUMNS as $colno => $column): ?>
		<th<?=sort_onclick($colno)?> id="th_<?=$column['id']?>">
			<?=$column['name']?>

		</th>
<?php endforeach; ?>
	</tr>
<?php foreach ($rooms as $id => $room): ?>
	<?php
		// Get the server public key.

		// FIXME:
		// ! This is bad practice.
		// However, the fetching code hides component data
		// and this is a low risk use case.

		$token = explode("=", $room->join_link)[1];
		$icon_hue = hexdec($token[2] . $token[2]);
		$icon_color = "hsl($icon_hue, 80%, 50%)";

		$hostname = explode("//", $room->join_link)[1];
		$hostname = explode("/", $hostname)[0];
	?>

	<tr id="<?=$id?>">
		<td class="td_identifier"><?=$id?></td>
		<td class="td_language"><?=$room->language?></td>
		<td class="td_name"><?=$room->name?></td>
		<td class="td_description"
			><?=$room->description?></td>
		<td class="td_users"><?=$room->active_users?></td>
		<td class="td_preview">
			<a href="<?=$room->preview_link?>" target="_blank" rel="noopener noreferrer">
				<?php if (str_starts_with($room->preview_link, 'http://')): ?>
					<span class="protocol-indicator protocol-http">HTTP</span>
				<?php endif; ?>
				<?php if (str_starts_with($room->preview_link, 'https://')): ?>
					<span class="protocol-indicator protocol-https">HTTPS</span>
				<?php endif; ?>
			</a>
		</td>
		<td class="td_qr_code">
			<img 
				class="qr-code-icon" 
				src="qrcode-solid.svg"
				onclick='displayQRModal("<?=$id?>")'
				alt="Pictogram of a QR code"
			>
		</td>
		<td class="td_server_icon" 
			data-token="<?=$token?>"
			title="<?=$hostname?> (<?=$token?>)"
		>
			<div class="td_server_icon-circle" style="background-color: <?=$icon_color?>">
				<span><?=strtoupper($token[0] . $token[1])?></span>
			</div>
		</td>
		<td class="td_join_url">
			<div class="join_url_container" data-url="<?=$room->join_link?>">
				<a class="join_url show-from-w5" title="<?=$room->join_link?>"
					><?=truncate($room->join_link, 32)?></a>
				<a class="noscript" href="<?=$room->join_link?>"
					>Copy link</a>
			</div>
		</td>
	</tr>
<?php endforeach; ?>
</table>
