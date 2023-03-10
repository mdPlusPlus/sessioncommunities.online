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
		<?php include "+components/page-head.php" ?>

		<link rel="stylesheet" href="styles2.css">
		<script type="module" src="main.js"></script>
		<title>Self-updating list of active Session communities</title>
	</head>
	<body onload="onLoad(<?php echo $timestamp ?>)">
		<header>
			<div id="header-start"></div>
			<div id="header-end">
				<a
					id="link-instructions"
					target="_blank"
					href="instructions.html"
				>Instructions</a>
			</div>
		</header>
			<h1 id="headline">Session Communities</h1>
		<?php include "+components/qr_modals.php" ?>

		<input type="checkbox" id="toggle-show-room-ids" class="hidden">

		<?php include "+components/tbl_communities.php" ?>

		<hr>

		<footer>
			<p id="server_summary">
				<?=count($rooms_assoc)?> unique Session Communities
				on <?=count_servers($rooms_assoc)?> servers have been found.
				<span id="servers_hidden">(None hidden as JS is off)</span>
			</p>
			<p id="last_checked">
				Last checked <span id="last_checked_value">
					<?=date("Y-m-d H:i:s", $timestamp)?> (UTC)
				</span>.
			</p>
			<p id="disclaimer">
					This site is not affiliated with
					<a href="https://optf.ngo">Oxen Privacy Tech Foundation</a>.
					<br>
					Communities shown are fetched automatically from
					various sources.
					<br>
					We make an attempt to hide communities containing
					objectionable or illegal content, but
					you should still proceed with caution.
			</p>
			<p class="noscript">
				This site works fine without JavaScript.
				However, some interactive features are
				only available with JS enabled.
			</p>
			<p>
				<label for="toggle-show-room-ids" class="clickable anchorstyle">
					Toggle room identifier display
				</label>
			</p>
			<nav>
				<a
					href="https://lokilocker.com/Mods/Session-Groups/wiki/Session-Closed-Groups"
					target="_blank"
					title="Closed groups curated by community moderators"
				>Closed Groups</a>
				<a
					href="https://session.directory/"
					target="_blank"
					title="User-submitted closed groups, communities and user profiles. Not safe for work."
				>session.directory</a>
				<a
					href="https://github.com/oxen-io/session-pysogs"
					target="_blank"
					title="Information about running a community server"
				>Host Your Own Community</a>
				<a
					href="https://getsession.org/terms-of-service"
					target="_blank"
				>Session Terms Of Service</a>
				<a
					href="https://github.com/mdPlusPlus/sessioncommunities.online"
					target="_blank"
					title="sessioncommunities.online repository on GitHub."
				>Source Code & Contact</a>
			</nav>
		</footer>
		<div id="copy-snackbar">
			Copied URL to clipboard. Paste into Session app to join
		</div>
	</body>
</html>
