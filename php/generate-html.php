<?php
	// Perform static site generation.
	
	require_once "getenv.php";

	// https://stackoverflow.com/a/17161106
	function rglob($pattern, $flags = 0) {
		$files = glob($pattern, $flags); 
		foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
			$files = array_merge(
				[],
				...[$files, rglob($dir . "/" . basename($pattern), $flags)]
			);
		}
		return $files;
	}

	foreach (rglob("$TEMPLATES_ROOT/*.php") as $phppath) {
		// Do not render auxiliary PHP files.
		if (str_contains("$phppath", "/+") || $phppath[0] == "+") 
			continue;


		$docpath = str_replace($TEMPLATES_ROOT, $DOCUMENT_ROOT, $phppath);
		$relpath = str_replace($TEMPLATES_ROOT, "", $phppath);
		$docpath = str_replace(".php", ".html", $docpath);
		
		// This works? Yes, yes it does.
		// We do this to isolate the environment and include-once triggers,
		// otherwise we could include the documents in an ob_* wrapper.
		
		// Same as shell_exec, except we don't have to escape quotes.
		log_info("Generating output for $relpath.");
		$document = `cd "$TEMPLATES_ROOT"; php $phppath`;
		
		file_put_contents($docpath, $document);
	}

	log_info("Done generating HTML.");
?>
