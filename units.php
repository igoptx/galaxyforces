<?php

// ===========================================================================
// Units {units.php}
// ===========================================================================

// ---------------------------------------------------------------------------
//	Version:	1.1
//	Modified:	2005-11-02
// ---------------------------------------------------------------------------

// ===========================================================================
// This file is a part of Galaxy Forces project.
// ===========================================================================
 
$index = 'units';
$auth = true;

require('include/header.php');

// ===========================================================================
// GALAXOPEDIA
// ===========================================================================

if ($view == 'unitslist') {
	tablebegin($Lang['Units'], 500);

	echo "\t<div class=\"cards cards-gallery\">\n";
	foreach ($Var['units'] as $id => $unit) {
		$name = isset($Lang['units'][$id]['name']) ? $Lang['units'][$id]['name'] : $id;
		echo "\t\t<article class=\"card card-tile\">" . card_image(array("gallery/units/$id.jpg", "gallery/units/icons/$id.jpg"), "description.php?type=unit&amp;subject=$id", $name) . "<span class=\"card-caption\">$name</span></article>\n";
	}
	echo "\t</div>\n";

	tableend('Galaxopedia');
}

// ===========================================================================
// UNITS
// ===========================================================================

elseif (isset($Colony) && $Colony) {
	tablebegin('<a href="units.php?view=unitslist">'.$Lang['Units'].'</a>');

	$flats = isset($Lang['structures']['flats']['name']) ? $Lang['structures']['flats']['name'] : 'Flats';
	$barracks = isset($Lang['structures']['barracks']['name']) ? $Lang['structures']['barracks']['name'] : 'Barracks';

	$count = 0;
	$type = null;
	if ($Units) {
		echo "\t<div class=\"cards\">\n";
		foreach ($Units as $s) {
			if ($type !== null && $s['type'] != $type) {
				echo "\t</div>\n";
				tablebreak();
				echo "\t<div class=\"cards\">\n";
			}
			$type = $s['type'];
			$href = "description.php?type=unit&amp;subject={$s['id']}&amp;back={$_SERVER['PHP_SELF']}";

			// produção por unidade (com sinal) e atributos, como nas colunas antigas
			$ratios = array();
			foreach (array('energy', 'silicon', 'metal', 'uran', 'plutonium', 'deuterium', 'food') as $r)
				if (!empty($s[$r . 'ratio'])) $ratios[] = array('icon' => "images/$r.jpg", 'title' => $Lang[strcap($r)], 'html' => amount($s[$r . 'ratio'], 0));
			$stats = array();
			if (isset($s['workforce'])) $stats[] = array('label' => 'W', 'title' => $Lang['Workforce'], 'html' => '<span class="work">' . $s['workforce'] . '</span>');
			if (@$s['scienceforce']) $stats[] = array('label' => 'S', 'title' => $Lang['Scienceforce'], 'html' => '<span class="work">' . $s['scienceforce'] . '</span>');
			if (!empty($s['attack'])) $stats[] = array('label' => 'A', 'title' => $Lang['Attack'], 'html' => '<span class="plus">' . div($s['attack']) . '</span>');
			if (isset($s['damage'])) $stats[] = array('label' => 'D', 'title' => $Lang['Damage'], 'html' => '<span class="capacity">' . div($s['damage']) . '</span>');
			if (!empty($s['capacity'])) $stats[] = array('label' => 'C', 'title' => $Lang['Capacity'], 'html' => '<span class="result">' . div($s['capacity']) . '</span>');
			if (isset($s['foodcapacity'])) $stats[] = array('label' => 'F', 'title' => $Lang['Food'] . ' / ' . $Lang['Capacity'], 'html' => '<span class="capacity">' . div($s['foodcapacity']) . '</span>');
			if (isset($s['flats'])) $stats[] = array('label' => 'P', 'title' => $flats, 'html' => '<span class="capacity">' . div($s['flats']) . '</span>');
			if (isset($s['barracks'])) $stats[] = array('label' => 'B', 'title' => $barracks, 'html' => '<span class="capacity">' . div($s['barracks']) . '</span>');

			if (isset($s['level'])) $count++;
			if (isset($s['amount'])) $count += $s['amount'];
?>		<article class="card">
			<?php echo card_image(array("gallery/units/{$s['id']}.jpg", "gallery/units/icons/{$s['id']}.jpg"), $href, $s['name']); ?>

			<div class="card-body">
				<h4 class="card-title"><a href="<?php echo $href; ?>"><?php echo $s['name']; ?></a>
<?php if (isset($s['amount'])) { ?>					<span class="badge" title="<?php echo $Lang['Amount']; ?>"><?php echo div($s['amount']); ?></span>
<?php } elseif (isset($s['level'])) { ?>					<span class="badge" title="<?php echo $Lang['Level']; ?>"><?php echo $s['level']; ?></span>
<?php } ?>				</h4>
				<p class="card-desc"><?php echo $s['description']; ?></p>
				<?php echo card_chips($ratios, 'ratios'); ?>

				<?php echo card_chips($stats); ?>

<?php if (isset($s['amount'])) { ?>				<div class="card-actions">
					<form action="<?php echo $_SERVER['PHP_SELF']; ?>?rid=<?php echo $rid; ?>" method="POST">
						<input type="hidden" name="action" value="destroyunits" />
						<input type="hidden" name="name" value="<?php echo $s['id']; ?>" />
						<input class="amount" size="4" maxlength="8" name="amount" value="0" /><input type="submit" class="danger" value="<?php echo $Lang['destroy']; ?>" />
					</form>
<?php if (($Player['planet'] == $Colony['planet']) && ($type == 'fighter' || $type == 'thief')) { ?>					<a class="action" href="equipment.php?action=shipexchange&amp;name=<?php echo $s['id']; ?>"><?php echo $Lang['Equip']; ?></a>
<?php } ?>				</div>
<?php } ?>			</div>
		</article>
<?php
		}
		echo "\t</div>\n";
	}
	else echo "\t<p>{$Lang['No units']}</p>\n";

	tableend($Lang['Count'] . ': <font class="result">' . div($count) . '</font>');
}
else {
	tablebegin($Lang['Colony'], 500);
?>	<br />
	<b><?php echo $Lang['HaveNoColony']; ?></b><br />
	<br />
	<?php echo $Lang['NoColonyTip']; ?><br />
	<br />
	<a href="create.php"><?php echo $Lang['CreateColony']; ?> &gt;&gt;</a><br />
	<br />
	<a href="control.php"><?php echo $Lang['GoBack']; ?> &gt;&gt;</a><br />
	<br />
<?php
	tableend($Lang['Structures']);
}

require('include/footer.php');
