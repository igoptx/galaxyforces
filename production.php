<?php

// ===========================================================================
// Production {production.php}
// ===========================================================================

// ---------------------------------------------------------------------------
//	Version:	1.3
//	Modified:	2005-11-02
// ---------------------------------------------------------------------------

// ===========================================================================
// This file is a part of Galaxy Forces project.
// ===========================================================================

$index = 'production';
$auth = true;

require('include/header.php');

if (isset($Colony) && $Colony) {

	if ($action == 'product') {
		if ($errors) {
			tablebegin('<font color="red" class="error">' . $Lang['Error'] . '!</font>', '400');

?>	<br />
	<b><?php echo $Lang['ErrorCantProduct']; ?></b><br />
	<br />
	<font color="red" class="error"><?php echo $errors; ?></font>
	<br />
	<a href="<?php echo $_SERVER['PHP_SELF']; ?>"><?php echo $Lang['GoBack']; ?> &gt;&gt;</a><br />
	<br />
<?php
		 	tableend($Lang['Production']);
		}
		else {
			tablebegin($Lang['Production'], 500);

?>		<br /><font class="h3"><?php echo $Lang['ProductS']; ?></font><br />
		<br />

		<table align="center" cellspacing="0" cellpadding="0" border="0">
<?php
 			if ($Cost['credits']) echo "\t<tr><td><b>{$Lang['Credits']}</b>:</td><td>&nbsp;</td><td class=\"result\">".div($Cost['credits'])."</td></tr>\n";
 			if ($Cost['energy']) echo "\t<tr><td><b>{$Lang['Energy']}</b>:</td><td>&nbsp;</td><td>".div($Cost['energy'])."</td></tr>\n";
 			if ($Cost['silicon']) echo "\t<tr><td><b>{$Lang['Silicon']}</b>:</td><td>&nbsp;</td><td>".div($Cost['silicon'])."</td></tr>\n";
 			if ($Cost['metal']) echo "\t<tr><td><b>{$Lang['Metal']}</b>:</td><td>&nbsp;</td><td>".div($Cost['metal'])."</td></tr>\n";
 			if ($Cost['uran']) echo "\t<tr><td><b>{$Lang['Uran']}</b>:</td><td>&nbsp;</td><td>".div($Cost['uran'])."</td></tr>\n";
 			if ($Cost['plutonium']) echo "\t<tr><td><b>{$Lang['Plutonium']}</b>:</td><td>&nbsp;</td><td>".div($Cost['plutonium'])."</td></tr>\n";
 			if ($Cost['deuterium']) echo "\t<tr><td><b>{$Lang['Deuterium']}</b>:</td><td>&nbsp;</td><td>".div($Cost['deuterium'])."</td></tr>\n";
 			if ($Cost['food']) echo "\t<tr><td><b>{$Lang['Food']}</b>:</td><td>&nbsp;</td><td>".div($Cost['food'])."</td></tr>\n";
 			if ($Cost['crystals']) echo "\t<tr><td><b>{$Lang['Crystals']}</b>:</td><td>&nbsp;</td><td>".div($Cost['crystals'])."</td></tr>\n";

?>		</table>
		<br />
		<a href="<?php echo $_SERVER['PHP_SELF']; ?>"><?php echo $Lang['GoBack']; ?> &gt;&gt;</a><br />
		<br />
<?php
			tableend($Lang['Production']);
		}
	}
	else {
		tablebegin($Lang['Production']);

		if ($Productions) {

?>	<br /><font class="h3"><?php echo $Lang['CurrentProductions']; ?><br />
	<br />

	<script>
	<!--

	function ask($url) {
		if (confirm('<?php echo $Lang['AreYouSure?']; ?>')) location.href = $url;
	}

	//-->
	</script>

	<ul class="queue">
<?php
			foreach($Productions as $s) {
				$left = $s['end'] - $stardate;
				$pct = $left > 0 ? round(num(100 * ($stardate - $s['begin']) / $s['time'])) : 100;
?>		<li>
			<?php echo card_image(array("gallery/units/icons/{$s['name']}.jpg"), '', $ProductionsAvailable[$s['name']]['name']); ?>

			<div class="queue-body">
				<div class="queue-title"><span class="result"><?php echo $ProductionsAvailable[$s['name']]['name']; ?></span> <span class="badge" title="<?php echo $Lang['Amount']; ?>"><?php echo div($s['amount']); ?></span></div>
				<div class="meter"><i style="width: <?php echo max(0, min(100, $pct)); ?>%"></i></div>
				<div class="queue-meta"><?php echo $Lang['FullETA']; ?>: <span class="value" data-countdown="<?php echo max(0, $left * $thicklength); ?>"><?php echo eta($left); ?></span><?php if ($left > 0) echo " ($pct%)"; ?></div>
			</div>
			<a href="javascript:ask('<?php echo $_SERVER['PHP_SELF']; ?>?action=cancelproduction&id=<?php echo $s['id']; ?>')" class="delete"><?php echo $Lang['ProductC']; ?> &gt;&gt;</a>
		</li>
<?php
			}
?>	</ul>
<?php
			tablebreak();
		}

		$count = 0;

		if ($ProductionsAvailable) {
			$type = null;
			echo "\t<div class=\"cards\">\n";
			foreach ($ProductionsAvailable as $s) {
				if ($type !== null && $s['type'] != $type) {
					echo "\t</div>\n";
					tablebreak();
					echo "\t<div class=\"cards\">\n";
				}
				$type = $s['type'];
				$href = "description.php?type=unit&amp;subject={$s['id']}&amp;back={$_SERVER['PHP_SELF']}";
				$costs = $s;
				if (isset($s['cost'])) $costs['credits'] = $s['credits'] + $s['cost'];
?>		<article class="card">
			<?php echo card_image(array("gallery/units/{$s['id']}.jpg", "gallery/units/icons/{$s['id']}.jpg"), $href, $s['name']); ?>

			<div class="card-body">
				<h4 class="card-title"><a href="<?php echo $href; ?>"><?php echo $s['name']; ?></a>
					<span class="badge" title="<?php echo $Lang['Amount']; ?>"><?php echo div($Colony[$s['id']] + 0); ?></span></h4>
				<p class="card-desc"><?php echo $s['description']; ?></p>
				<?php echo card_costs($costs, isset($s['cost']) ? 'work' : 'result', array('credits', 'energy', 'silicon', 'metal', 'uran', 'plutonium', 'deuterium', 'crystals')); ?>

				<div class="card-actions">
<?php
				if ($Colony['military']) {
					// tempo para 1 unidade e para 100 unidades, como nas colunas antigas
					$eta1 = eta(1 + round(num((50 / $Colony['military']) * ($s['work'] / log(num($Colony['workforce'])) / ($Colony['factory'] + $Colony['tron'])))));
					$eta100 = eta(1 + round(num((50 / $Colony['military']) * (100 * $s['work'] / log(num($Colony['workforce'])) / ($Colony['factory'] + $Colony['tron'])))));
					echo "\t\t\t\t\t<span class=\"eta\" title=\"1\">$eta1</span> <span class=\"eta eta-100\" title=\"100\">$eta100</span>\n";
				}
				else echo "\t\t\t\t\t<span class=\"muted\">{$Lang['NotAvailable']}</span>\n";
?>					<form action="<?php echo $_SERVER['PHP_SELF']; ?>?rid=<?php echo $rid; ?>" method="POST">
						<input type="hidden" name="action" value="product" />
						<input type="hidden" name="name" value="<?php echo $s['id']; ?>" />
						<input class="amount" size="4" maxlength="8" name="amount" value="1" /><input type="submit" value="<?php echo $Lang['Production']; ?>" />
					</form>
				</div>
			</div>
		</article>
<?php
				$count++;
			}
			echo "\t</div>\n";
		}
		if ($count) tableend($count . $Lang[' unit(s) available']);
		else tableend($Lang['Production']);
	}
}

require('include/footer.php');
