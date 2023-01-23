<?php
	include_once "+getenv.php";
	$instruction_files = glob("+instructions/*.txt");
?>
<!DOCTYPE html>
<html>
	<head>
		<?php include "+components/page-head.php" ?>

		<link rel="stylesheet" href="css/instructions.css">
		<style type="text/css">
		<?php foreach ($instruction_files as $i => $file): ?>
			#language-selection-<?=$i?>:checked ~ 
			#instructions #instructions-<?=$i?> {
				display: block;
			}
		<?php endforeach; ?>
		</style>
	</head>
	<body>
		<header>
			<h1>Instructions for joining Session Communities</h1>
		</header>
		<main>
			Choose your language:
			<?php foreach ($instruction_files as $i => $file): ?>
			<br>
			<input 
				id="language-selection-<?=$i?>"
				class="language-selection"
				name="language"
				type="radio"
			>
			<label for="language-selection-<?=$i?>">
				<?=
					// Name of the language
					// Can be later parsed from i.e. first line of file
					pathinfo($file)['filename']
				?>
			</label>
			<?php endforeach; ?>
			
			
			<article id="instructions">
			<?php foreach ($instruction_files as $i => $file): ?>
			<section id="instructions-<?=$i?>" class="instructions"><?php
				// Sanitization as second layer of protection
				// for user-submitted instruction files.
				// Should not ever have to be used.
				$content = trim(file_get_contents($file));
				$content = htmlentities($content);
				// Minimal formatting so that contributions are easier
				$content = str_replace("\n-", "\n\n•", $content);
				$content = str_replace("\n\n\n", "<br><br>", $content);
				$content = str_replace("\n\n", "<br>", $content);
				echo $content;
			?>
			</section>
			<?php endforeach; ?>
			</article>
		</main>
	</body>
</html>