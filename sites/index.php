<?php
	// prerequisite include for sites and components
	require_once "+getenv.php";
	require_once "$PROJECT_ROOT/php/utils/server-utils.php";
	
	$rooms_raw = file_get_contents($ROOMS_FILE);
	$rooms = json_decode($rooms_raw);
	$rooms_assoc = json_decode($rooms_raw, true);
	$timestamp = filemtime($ROOMS_FILE);
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8">
		<link rel="icon" type="image/svg+xml" href="favicon.svg" sizes="any">
		<link rel="stylesheet" href="styles2.css">
		<script type="module" src="main.js" defer></script>
		<title>Self-updating list of active Session communities</title>
	</head>
	<body onload="onLoad(<?php echo $timestamp ?>)">
		<h1 id="headline">Session Communities</h1>
		<?php include "+components/qr_modals.php" ?>

		<?php include "+components/tbl_communities.php" ?>

		<table id="tbl_footer">
			<tr>
				<td id="td_summary">
					<?=count($rooms_assoc)?> unique Session Communities 
					on <?=count_servers($rooms_assoc)?> servers have been found.
				</td>
			</tr>
			<tr>
				<td id="td_last_checked">Last checked X minutes ago.</td>
			</tr>
		</table>

		<div id="copy-snackbar">
			Copied URL to clipboard. Paste into Session app to join
		</div>
	</body>
</html>
