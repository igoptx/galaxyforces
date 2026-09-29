<?php

// ===========================================================================
// Research {research.php}
// ===========================================================================

// ---------------------------------------------------------------------------
//	Version:	1.1
//	Modified:	2005-10-25
// ---------------------------------------------------------------------------

// ===========================================================================
// This file is a part of Galaxy Forces project.
// ===========================================================================

$auth = true;

require('include/header.php');

// ===========================================================================
// ERRORS
// ===========================================================================

if ($errors) {
	tablebegin("<font class=\"error\">{$Lang['Error']}!</font>", '400');
	echo "\t\t<br />\n\t\t<font class=\"h3\">{$Lang['ErrorProblems']}</font><br />\n\t\t<br />\n\t\t<font class=\"error\">$errors</font>\n\t\t<br />\n\t\t<a href=\"javascript:history.back(1)\">{$Lang['GoBack']}&nbsp;&gt;&gt;</a><br />\n";
	echo "\t\t<br />\n";
	sound('error');
	tableend('<a href="research.php">'.$Lang['GoBack'].'&nbsp;&gt;&gt;</a>');
}

// ===========================================================================
// RESULT
// ===========================================================================

elseif ($result) {
	tablebegin($pagename, 400);
	echo "\t\t<br /><font class=\"result\">$result</font><br />";
	tableend('<a href="research.php">'.$Lang['GoBack'].'&nbsp;&gt;&gt;</a>');
}
 
// ===========================================================================
// RESEARCH
// ===========================================================================

elseif (@$Colony && ($Colony['laboratory'] || $Colony['databank'])) {
	if ($Research) {
		tablebegin($Lang['Research'], 500);

?>	<h3><?php echo $Lang['Researching']; ?></h3>

	<font class="result"><?php echo $Technologies[$Research['name']]['name']; ?></font>, <?php echo $Lang['FullETA']; ?>: <font class="value" data-countdown="<?php echo max(0, ($Research['end'] - $stardate) * $thicklength); ?>"><?php echo eta($Research['end'] - $stardate); ?></font><br />
	<br /><a href="javascript:ask('research.php?action=cancelresearch&id=<?php echo $Research['id']; ?>')" class="delete"><?php echo $Lang['Cancel']; ?> &gt;&gt;</a><br />
	<br /><a href="colony.php"><?php echo $Lang['GoBack']; ?> &gt;&gt;</a><br />

	<script>
	<!--
	function ask($url) {
		if (confirm('<?php echo $Lang['RUSure']; ?>')) location.href = $url;
	}
	//-->
	</script>

	<br />
<?php
		tableend($Lang['Research']);
	}
	else {
		tablebegin($Lang['Research']);

		echo "\t<div class=\"cards\">\n";
		foreach ($Technologies as $t) {
			$folder = $t['completed'] ? 'completed' : 'icons';
?>		<article class="card<?php echo $t['completed'] ? ' card-done' : ''; ?>">
			<?php echo card_image(array("gallery/technology/$folder/{$t['id']}.jpg", "gallery/technology/icons/{$t['id']}.jpg", "gallery/technology/{$t['id']}.jpg"), '', $t['name']); ?>

			<div class="card-body">
				<h4 class="card-title"><span class="<?php echo $t['completed'] ? 'work' : 'result'; ?>"><?php echo $t['name']; ?></span>
<?php if (@$t['level']) { ?>					<span class="badge" title="<?php echo $Lang['Level']; ?>"><?php echo $t['level']; ?></span>
<?php } ?>				</h4>
				<p class="card-desc"><?php echo $t['description']; ?></p>
<?php if ($t['completed']) { ?>				<div class="card-actions"><span class="work state"><?php echo $Lang['completed']; ?></span></div>
<?php } else { ?>				<?php echo card_costs($t); ?>

				<div class="card-actions">
					<span class="eta" title="ETA"><?php echo eta(1 + round(num((25 / $Colony['science']) * $t['work'] / log(num($Colony['scienceforce']))))); ?></span>
					<a class="button" href="<?php echo $_SERVER['PHP_SELF']; ?>?action=initiate&amp;name=<?php echo $t['id']; ?>"><?php echo $Lang['initiate']; ?></a>
				</div>
<?php } ?>			</div>
		</article>
<?php
		}
		echo "\t</div>\n";
		tableend($Lang['Research']);
	}
}

// ===========================================================================
// NOT AVAILABLE
// ===========================================================================

else {
	tablebegin('<font class="error">' . $Lang['Error'] . '!</font>', '400');
	echo '<br /><b>'.$Lang['NotAvailable'].'</b><br /><br /><a href="control.php">'.$Lang['GoBack'].' &gt;&gt;</a><br /><br />';
	tableend($Lang['Research']);
}

require('include/footer.php');
