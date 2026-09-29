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
	echo "\t<script>\n\t<!--\n\tfunction ask(\$url) { if (confirm('{$Lang['RUSure']}')) location.href = \$url; }\n\t//-->\n\t</script>\n";

	// estado atual (reflete a ação que o motor acabou de processar)
	$Research = readresearch($login);
	$researchqueue = readresearchqueue($login);

	// -----------------------------------------------------------------------
	// A investigar agora + fila (estilo OGame)
	// -----------------------------------------------------------------------
	if ($Research || $researchqueue) {
		tablebegin($Lang['Researching']);
		echo "\t<ul class=\"queue\">\n";

		if ($Research) {
			$rname = isset($Technologies[$Research['name']]) ? $Technologies[$Research['name']]['name'] : strcap($Research['name']);
			$left = $Research['end'] - $stardate;
			echo "\t\t<li>" . card_image(array("gallery/technology/icons/{$Research['name']}.jpg", "gallery/technology/{$Research['name']}.jpg"), 'research.php', $rname)
				. '<div class="queue-body"><div class="queue-title"><span class="result">' . htmlspecialchars($rname) . '</span></div>'
				. '<div class="queue-meta"><span class="value" data-countdown="' . max(0, $left * $thicklength) . '">' . eta($left) . '</span> '
				. '<a class="delete" href="javascript:ask(\'research.php?action=cancelresearch&amp;id=' . (int)$Research['id'] . '&amp;rid=' . $rid . '\')">' . $Lang['Cancel'] . '</a></div></div></li>' . "\n";
		}

		$cum = $Research ? ($Research['end'] - $stardate) : 0;
		if ($cum < 0) $cum = 0;
		foreach ($researchqueue as $n => $q) {
			$cum += (int)$q['time'];
			$qname = isset($Technologies[$q['name']]) ? $Technologies[$q['name']]['name'] : strcap($q['name']);
			echo "\t\t<li>" . card_image(array("gallery/technology/icons/{$q['name']}.jpg", "gallery/technology/{$q['name']}.jpg"), '', $qname)
				. '<div class="queue-body"><div class="queue-title"><span class="muted">' . ($n + 1) . '.</span> <span class="result">' . htmlspecialchars($qname) . '</span></div>'
				. '<div class="queue-meta"><span class="muted">' . $Lang['Queued'] . ':</span> <span class="value" data-countdown="' . max(0, $cum * $thicklength) . '">' . eta($cum) . '</span> '
				. '<a class="delete" href="research.php?action=dequeueresearch&amp;id=' . (int)$q['id'] . '&amp;rid=' . $rid . '">' . $Lang['QueueRemove'] . '</a></div></div></li>' . "\n";
		}

		echo "\t</ul>\n";
		tableend($Lang['ResearchQueue'] . ': ' . count((array)$researchqueue) . '/' . QUEUE_MAX);
	}

	// -----------------------------------------------------------------------
	// Tecnologias (permite pôr na fila mesmo a investigar)
	// -----------------------------------------------------------------------
	tablebegin($Lang['Research']);

	$btn = $Research ? $Lang['queue'] : $Lang['initiate'];
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
					<a class="button" href="<?php echo $_SERVER['PHP_SELF']; ?>?action=initiate&amp;name=<?php echo $t['id']; ?>&amp;rid=<?php echo $rid; ?>"><?php echo $btn; ?></a>
				</div>
<?php } ?>			</div>
		</article>
<?php
	}
	echo "\t</div>\n";
	tableend($Lang['Research']);
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
