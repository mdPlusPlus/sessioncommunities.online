<?php	
	// Once handlers are attached in JS, this check ceases to be useful.
	function column_sortable($id) {
		return $id != "qr";
	}
	
	function sort_onclick($colno) {
		global $TABLE_COLUMNS;
		$column = $TABLE_COLUMNS[$colno];
		if (!column_sortable($column['id'])) return "";
		return " onclick='sortTable($colno)'";
	}
	
	$TABLE_COLUMNS = [
		['id' => "identifier", 'name' => "Identifier"],
		['id' => "language", 'name' => "L"],
		['id' => "name", 'name' => "Name"],
		['id' => "description", 'name' => "Description"],
		['id' => "users", 'name' => "Users"],
		['id' => "preview", 'name' => "Preview"],
		['id' => "qr", 'name' => "QR"],
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
	<tr id="<?=$id?>">
		<td class="td_identifier"><?=$id?></td>
		<td class="td_language"><?=$room->language?></td>
		<td class="td_name"><?=$room->name?></td>
		<td class="td_description">
			<div class="clamp">
				<?=$room->description?>
			</div>
		</td>
		<td class="td_users"><?=$room->active_users?></td>
		<td class="td_preview">
			<a href="<?=$room->preview_link?>">
				<?=$room->preview_link?>
			
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
		<td class="td_join_url">
			<a href="<?=$room->join_link?>">
				<?=$room->join_link?>

			</a>
		</td>
	</tr>
<?php endforeach; ?>
</table>
