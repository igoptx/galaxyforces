<?php

// ===========================================================================
// Build {build.php}
// ===========================================================================

// ---------------------------------------------------------------------------
//	Version:	1.2
//	Modified:	2005-11-11
// ---------------------------------------------------------------------------

// ===========================================================================
// This file is a part of Galaxy Forces project.
// ===========================================================================

$index = 'builds';
$auth = true;
$js[] = 'functions';

require('include/header.php');

$pagename = $Lang['Build'];

if (! @$Colony) $errors .= "{$Lang['NotAvailable']}<br />";

// ===========================================================================
// ERRORS
// ===========================================================================

if ($errors) {
	tablebegin("<font class=\"error\">{$Lang['Error']}!</font>", '400');
	echo "\t\t<br />\n\t\t<font class=\"h3\">{$Lang['ErrorProblems']}</font><br />\n\t\t<br />\n\t\t<font class=\"error\">$errors</font>\n\t\t<br />\n\t\t<a href=\"javascript:history.back(1)\">{$Lang['GoBack']}&nbsp;&gt;&gt;</a><br /><br />\n";
	sound('error');
	tableend("<a href=\"colony.php\">{$Lang['GoBack']}&nbsp;&gt;&gt;</a>");
}

// ===========================================================================
// RESULT
// ===========================================================================

elseif ($result) {
	tablebegin($pagename, 400);
	echo "\t\t<br /><font class=\"result\">$result</font><br /><a href=\"{$_SERVER['PHP_SELF']}\">{$Lang['GoBack']}&nbsp;&gt;&gt;</a><br /><br />";
	tableend("<a href=\"admin.php\">{$Lang['GoBack']}&nbsp;&gt;&gt;</a>");
}

// ===========================================================================
// BUILD
// ===========================================================================

else {
	echo "\t<script>\n\t<!--\n\tfunction ask(\$url)\n\t{\n\t\tif (confirm('{$Lang['AreYouSure?']}')) location.href = \$url;\n\t}\n\t//-->\n\t</script>\n";

	if ($Buildings) {
		tablebegin($pagename, 500);
		subbegin();

		tableimg('images/bw.gif', 168, 168, "gallery/buildings/{$Buildings['name']}.jpg", 160, 160, '', 'right');

		echo "\t<center><font class=\"h3\">{$Lang['Building']}</font></center>\n";
?>	<br />
	<b><?php echo $Lang['Name']; ?></b>: <font class="plus"><?php echo $Builds[$Buildings['name']]['name']; ?></font><br />
	<b><?php echo $Lang['Amount']; ?></b>: <font class="result"><?php echo $Buildings['amount']; ?></font><br />
	<b><?php echo $Lang['Progress']; ?></b>: <font class="capacity"><?php echo round(num(100 * ($stardate - $Buildings['begin']) / $Buildings['time'])); ?> %</font><br />
	<br />
	<?php echo $Lang['FullETA']; ?>: <b><?php echo eta($Buildings['end'] - $stardate); ?><br />
	<br />
	<center><a href="javascript:ask('<?php echo $_SERVER['PHP_SELF']; ?>?action=cancelbuilding')" class="delete"><?php echo $Lang['BuildC']; ?> &gt;&gt;</a></center>
	<br />
<?php
		sound('building');

		subend();
		tableend($Lang['Build']);
	}
	elseif ($action == 'build') {
		if ($errors) {
			tablebegin('<font color="red" class="error">' . $Lang['Error'] . '!</font>', '400');

?>	<br />
	<b><?php echo $Lang['ErrorCantBuild']; ?></b><br />
	<br />
	<font color="red" class="error"><?php echo $errors; ?></font>
	<br />
	<a href="javascript:history.back(1)"><?php echo $Lang['GoBack']; ?> &gt;&gt;</a><br />
	<br />
<?php
		 	tableend($Lang['Build']);
			sound('error');
		}
		else {
			tablebegin($Lang['Build'], 500);
?>		<br />
		<font class="h3"><?php echo $Lang['BuildS']; ?></font><br />
		<br />
		<table align="center" cellspacing="0" cellpadding="0" border="0">
<?php
 			if ($Cost['credits']) {
?>		<tr>
		<td><?php echo $Lang['Credits']; ?>:</td>
		<td>&nbsp; &nbsp;</td>
		<td><b><?php echo div($Cost['credits']); ?></b></td>
		</tr>
<?php
			}
 			if ($Cost['energy']) echo "\t<tr><td>{$Lang['Energy']}:</td><td></td><td><b>".div($Cost['energy'])."</b></td></tr>\n";
 			if ($Cost['silicon']) echo "\t<tr><td>{$Lang['Silicon']}:</td><td></td><td><b>".div($Cost['silicon'])."</b></td></tr>\n";
 			if ($Cost['metal']) echo "\t<tr><td>{$Lang['Metal']}:</td><td></td><td><b>".div($Cost['metal'])."</b></td></tr>\n";
 			if ($Cost['uran']) {
?>		<tr>
		<td><?php echo $Lang['Uran']; ?>:</td>
		<td>&nbsp; &nbsp;</td>
		<td><b><?php echo div($Cost['uran']); ?></b></td>
		</tr>
<?php
			}
 			if ($Cost['crystals']) {
?>		<tr>
		<td><?php echo $Lang['Crystals']; ?>:</td>
		<td>&nbsp; &nbsp;</td>
		<td><b><?php echo div($Cost['crystals']); ?></b></td>
		</tr>
<?php
			}
?>		</table>
		<br />
		<a href="<?php echo $_SERVER['PHP_SELF']; ?>?rid=<?php echo $rid; ?>"><?php echo $Lang['GoBack']; ?> &gt;&gt;</a><br />
		<br />
<?php
			tableend($Lang['Build']);
			sound('buildingstarted');
		}
	}
	else {
		tablebegin($Lang['Build']);

		// um grupo de cartões por tipo de estrutura
		$type = null;
		echo "\t<div class=\"cards\">\n";
		foreach ($Builds as $s) {
			if ($type !== null && $s['type'] != $type) {
				echo "\t</div>\n";
				tablebreak();
				echo "\t<div class=\"cards\">\n";
			}
			$type = $s['type'];

			$href = "description.php?subject={$s['id']}&back={$_SERVER['PHP_SELF']}";
			$costs = $s;
			if (isset($s['cost'])) $costs['credits'] = $s['credits'] + $s['cost'];
			$credits_class = isset($s['cost']) ? 'work' : 'result';
?>		<article class="card">
			<?php echo card_image(array("gallery/buildings/{$s['id']}.jpg", "gallery/buildings/icons/{$s['id']}.jpg"), $href, $s['name']); ?>

			<div class="card-body">
				<h4 class="card-title"><a href="<?php echo $href; ?>"><?php echo $s['name']; ?></a>
					<span class="badge" title="<?php echo $Lang['Amount']; ?>"><?php echo strdiv($Colony[$s['id']]); ?></span></h4>
				<p class="card-desc"><?php echo $s['description']; ?></p>
<?php if (isset($s['level'])) { ?>				<p class="card-meta"><b><?php echo $Lang['Level']; ?></b>: <span class="capacity"><?php echo $s['level']; ?></span></p>
<?php } ?>				<?php echo card_costs($costs, $credits_class); ?>

<?php if ($Colony['infrastructure']) { ?>				<div class="card-actions">
					<span class="eta" title="ETA"><?php echo eta(round(num((50 / $Colony['infrastructure']) * $s['work'] / log(num($Colony['workforce']))))); ?></span>
					<form action="<?php echo $_SERVER['PHP_SELF']; ?>?rid=<?php echo $rid; ?>" method="POST">
						<input type="hidden" name="action" value="build" />
						<input type="hidden" name="name" value="<?php echo $s['id']; ?>" />
						<?php if (! isset($s['level'])) { ?><input class="amount" size="4" maxlength="8" name="amount" value="1" /><?php } ?><input type="submit" value="<?php echo $Lang['build']; ?>" />
					</form>
				</div>
<?php } ?>			</div>
		</article>
<?php
		}
		echo "\t</div>\n";
		tableend(count((array)($Builds)) . $Lang[' structure(s) available']);

		if ($action == 'cancelbuilding') sound('processcancelled');
		else sound('selectstructure');
	}
}

require('include/footer.php');
