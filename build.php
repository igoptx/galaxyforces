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

	// estado atual (reflete a ação que o motor acabou de processar)
	$Buildings = readbuildings($login);
	$buildqueue = readbuildqueue($login);

	// -----------------------------------------------------------------------
	// Em construção agora + fila (estilo OGame)
	// -----------------------------------------------------------------------
	if ($Buildings || $buildqueue) {
		tablebegin($Lang['Building']);
		echo "\t<ul class=\"queue\">\n";

		// item ativo
		if ($Buildings) {
			$bname = isset($Builds[$Buildings['name']]) ? $Builds[$Buildings['name']]['name'] : strcap($Buildings['name']);
			$left = $Buildings['end'] - $stardate;
			$pct = $Buildings['time'] > 0 ? round(num(100 * ($stardate - $Buildings['begin']) / $Buildings['time'])) : 100;
			echo "\t\t<li>" . card_image(array("gallery/buildings/{$Buildings['name']}.jpg", "gallery/buildings/icons/{$Buildings['name']}.jpg"), 'build.php', $bname)
				. '<div class="queue-body"><div class="queue-title"><span class="result">' . htmlspecialchars($bname) . '</span> <span class="badge">x' . (int)$Buildings['amount'] . '</span></div>'
				. '<div class="queue-meta"><span class="value" data-countdown="' . max(0, $left * $thicklength) . '">' . eta($left) . '</span> <span class="muted">(' . $pct . '%)</span> '
				. '<a class="delete" href="javascript:ask(\'' . $_SERVER['PHP_SELF'] . '?action=cancelbuilding&amp;rid=' . $rid . '\')">' . $Lang['BuildC'] . '</a></div></div></li>' . "\n";
			sound('building');
		}

		// itens em fila, com ETA acumulado
		$cum = $Buildings ? ($Buildings['end'] - $stardate) : 0;
		if ($cum < 0) $cum = 0;
		foreach ($buildqueue as $n => $q) {
			$cum += (int)$q['time'];
			$qname = isset($Builds[$q['name']]) ? $Builds[$q['name']]['name'] : strcap($q['name']);
			echo "\t\t<li>" . card_image(array("gallery/buildings/{$q['name']}.jpg", "gallery/buildings/icons/{$q['name']}.jpg"), '', $qname)
				. '<div class="queue-body"><div class="queue-title"><span class="muted">' . ($n + 1) . '.</span> <span class="result">' . htmlspecialchars($qname) . '</span> <span class="badge">x' . (int)$q['amount'] . '</span></div>'
				. '<div class="queue-meta"><span class="muted">' . $Lang['Queued'] . ':</span> <span class="value" data-countdown="' . max(0, $cum * $thicklength) . '">' . eta($cum) . '</span> '
				. '<a class="delete" href="' . $_SERVER['PHP_SELF'] . '?action=dequeuebuild&amp;id=' . (int)$q['id'] . '&amp;rid=' . $rid . '">' . $Lang['QueueRemove'] . '</a></div></div></li>' . "\n";
		}

		echo "\t</ul>\n";
		tableend($Lang['BuildQueue'] . ': ' . count((array)$buildqueue) . '/' . QUEUE_MAX);
	}

	// -----------------------------------------------------------------------
	// Estruturas disponíveis (permite pôr na fila mesmo a construir)
	// -----------------------------------------------------------------------
	tablebegin($Lang['Build']);

	$btn = $Buildings ? $Lang['queue'] : $Lang['build'];
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
						<?php if (! isset($s['level'])) { ?><input class="amount" size="4" maxlength="8" name="amount" value="1" /><?php } ?><input type="submit" value="<?php echo $btn; ?>" />
					</form>
				</div>
<?php } ?>			</div>
		</article>
<?php
	}
	echo "\t</div>\n";
	tableend(count((array)($Builds)) . $Lang[' structure(s) available']);

	if ($action == 'cancelbuilding') sound('processcancelled');
	elseif ($action == 'build') sound('buildingstarted');
	else sound('selectstructure');
}

require('include/footer.php');
