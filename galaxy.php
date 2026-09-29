<?php

// ===========================================================================
// Galaxy {galaxy.php}
// ===========================================================================

// ---------------------------------------------------------------------------
//	Version:	1.3
//	Modified:	2005-11-13
//	Author(s):	zoltarx
// ---------------------------------------------------------------------------

// ===========================================================================
// This file is a part of Galaxy Forces project.
// ===========================================================================

$index = 'galaxy';
$auth = true;

require('include/header.php');

locale('colony');

$galaxy = escapesql(strip_tags((string)getvar('galaxy')));
$object = escapesql(strip_tags((string)getvar('object')));
$page = abs(num(getvar('page')));

$pagecount = 100;
$MAXUSERS = 250;

// ===========================================================================
// ERRORS
// ===========================================================================

if ($errors) {
	tablebegin("<font class=\"error\">{$Lang['Error']}!</font>", '400');
	echotitle($Lang['ErrorProblems']);
	echo "\t\t<font class=\"error\">$errors</font>\n\t\t<br />\n\t\t<a href=\"javascript:history.back(1)\">{$Lang['GoBack']}&nbsp;&gt;&gt;</a><br /><br />\n";
	sound('error');
	tableend("<a href=\"colony.php\">{$Lang['GoBack']}&nbsp;&gt;&gt;</a>");
}

// ===========================================================================
// RESULT
// ===========================================================================

elseif ($result) {
	tablebegin($pagename, 400);
	echo "\t\t<br /><font class=\"result\">$result</font><br /><a href=\"{$_SERVER['PHP_SELF']}\">{$Lang['GoBack']}&nbsp;&gt;&gt;</a><br /><br />";
	tableend(anchor('admin.php', $Lang['GoBack']));
}

// ===========================================================================
// OBJECT
// ===========================================================================

elseif ($object) {
	$db->query("SELECT * FROM {$prefix}space WHERE name='$object' LIMIT 1;");
	if ($t = $db->fetchrow()) {
		$galaxy = $t['galaxy'];

		if (! $Player['destination'] && $Player['planet'] != $object) {
			$db->query("SELECT * FROM {$prefix}universe WHERE name='$galaxy' LIMIT 1;");
			$g = $db->fetchrow();
			$db->query("SELECT * FROM `{$prefix}universe` WHERE `name`='{$Player['galaxy']}' LIMIT 1;");
			$gg = $db->fetchrow();
			$db->query("SELECT * FROM `{$prefix}space` WHERE `name`='{$Player['planet']}' LIMIT 1;");
			$tt = $db->fetchrow();
			$time = round(num((galaxydistance($gg, $g) + planetdistance($tt, $t)) / $playerspeed));
		}

		$db->query("SELECT id FROM {$prefix}colonies WHERE planet='$object';");
		$max = $db->numrows();
		if ($page > ($m = floor(num($max / $pagecount)))) $page = $m;
		$l = $page * $pagecount;
		$db->query("SELECT * FROM {$prefix}colonies WHERE planet='$object' ORDER BY base DESC,colonists DESC,name LIMIT $l,$pagecount;");
		$s = '';

		tablebegin("{$Lang['Object']}: <b>" . strcap($t['name']) . '</b>', 640);
?>	<div class="colony">
		<div class="colony-info">
			<dl class="facts">
				<dt><?php echo $Lang['Objects[]'][$t['type']]; ?></dt><dd class="plus"><?php echo strcap($t['name']); ?></dd>
<?php if ($t['system']) { ?>				<dt><?php echo $Lang['PlanetSystem']; ?></dt><dd class="work"><?php echo $t['system']; ?></dd>
<?php } ?>				<dt><?php echo $Lang['Technology']; ?></dt><dd class="capacity"><?php echo $Lang['Technology[]'][$t['technology']]; ?></dd>
				<dt><?php echo $Lang['SizeC']; ?></dt><dd class="result"><?php echo $Lang['SizeT'][$t['class']]; ?></dd>
<?php if ($t['type'] == 'planet') { ?>				<dt><?php echo $Lang['Explored']; ?></dt><dd class="minus"><?php echo number_format(num($t['explored']), 2, $Lang['DecPoint'], ' '); ?>%</dd>
				<dt><?php echo $Lang['Colonies']; ?></dt><dd class="result"><?php echo $max; ?></dd>
<?php if ($t['abandoned']) { ?>				<dt><?php echo $Lang['AbandonedC']; ?></dt><dd class="minus"><?php echo $t['abandoned']; ?></dd>
<?php } ?>				<dt><?php echo $Lang['WindS']; ?></dt><dd class="capacity"><?php echo $t['wind']; ?>%</dd>
				<dt><?php echo $Lang['Gravity']; ?></dt><dd class="work"><?php echo number_format(num($t['gravity']), 1, $Lang['DecPoint'], ' '); ?> Q</dd>
<?php } ?>			</dl>
			<p class="colony-links">
<?php
		if (! $Player['destination'] && $Player['planet'] != $object) echo "\t\t\t\t<a class=\"button\" href=\"control.php?action=travel&amp;destination={$t['name']}\">{$Lang['Travel']} (" . eta($time) . ")</a>\n";
		if (! $Colony) echo "\t\t\t\t" . anchor("colony.php?view=create&amp;planet={$t['name']}", $Lang['CreateColony']) . "\n";
		if (! $action) echo "\t\t\t\t" . anchor("galaxy.php?galaxy=$galaxy&amp;object=$object&amp;action=scanobject", $Lang['Scan']) . "\n";
?>			</p>
		</div>
		<?php echo card_image(array("gallery/space/{$t['name']}.jpg", "gallery/space/icons/{$t['name']}.jpg"), '', strcap($t['name'])); ?>

	</div>
<?php
		if ($action == 'scanobject') {
			tablebreak();
?>	<dl class="facts">
		<dt><?php echo $Lang['Moons']; ?></dt><dd class="plus"><?php echo div($t['moons']); ?></dd>
		<dt><?php echo $Lang['TerrainHardness']; ?></dt><dd class="minus"><?php echo div($t['terrain']); ?></dd>
		<dt><?php echo $Lang['Illumination']; ?></dt><dd class="result"><?php echo div($t['illumination']); ?></dd>
		<dt><?php echo $Lang['LifeSigns']; ?></dt><dd class="result"><?php echo div($t['life']); ?></dd>
		<dt><?php echo $Lang['Coordinates']; ?></dt><dd class="work"><?php echo div($t['x']) . ', ' . div($t['y']) . ', ' . div($t['z']); ?></dd>
	</dl>
<?php
		}
		elseif ($max) {
			tablebreak();

?>	<table class="list">
	<thead><tr><th class="left"><?php echo $Lang['ColonyName']; ?></th><th><?php echo $Lang['Owner']; ?></th><th><?php echo $Lang['Level']; ?></th><th><?php echo $Lang['Population']; ?></th><th><?php echo $Lang['Attacked']; ?></th></tr></thead>
	<tbody>
<?php
			while ($u = $db->fetchrow()) {
?>	<tr<?php echo $u['owner'] == $login ? ' class="here"' : ''; ?>>
		<td class="left capacity"><?php echo strcap($u['name']); ?></td>
		<td><a href="whois.php?name=<?php echo $u['owner']; ?>"><?php echo $u['owner']; ?></a></td>
		<td><?php echo $u['base']; ?></td>
		<td class="result"><?php echo div($u['colonists'] + $u['scientists'] + $u['soldiers']); ?></td>
		<td class="minus"><?php echo $u['attacked']; ?></td>
	</tr>
<?php
			}
?>	</tbody>
	</table>
<?php
			$n = $page;
			if ($n) $s .= "<a href=\"galaxy.php?galaxy=$galaxy&object=$object&page=" . ($n - 1) . "\">";
			$s .=  "&lt;&lt; {$Lang['Previous']}";
			if ($n) $s .= "</a>";
			$s .= ' &nbsp; ';
			$a = $n > 5 ? $n - 5 : 0;
			$b = $n < $m - 5 ? $n + 5 : $m;
			for ($i = $a; $i <= $b; $i++) {
				if ($i != $n) $s .= "<a href=\"galaxy.php?galaxy=$galaxy&object=$object&page=$i\">";
				$s .= $i + 1;
				if ($i != $n) $s .= "</a>";
				$s .= ' ';
			}
			$s .= '&nbsp; ';
			if ($n < $m) $s .= "<a href=\"galaxy.php?galaxy=$galaxy&object=$object&page=" . ($n + 1) . "\">";
			$s .= "{$Lang['Next']} &gt;&gt;";
			if ($n < $m) $s .= "</a>";
			$s .= ' &nbsp; &nbsp ';
		}

		// jogadores neste objeto
		$db->query("SELECT login,destination,clan FROM {$prefix}users WHERE planet='$object' ORDER BY clan,level DESC LIMIT $MAXUSERS;");
		if ($l = $db->numrows()) {
			tablebreak();
			echo "\t<ul class=\"people\">\n";
			while ($t = $db->fetchrow()) echo "\t\t<li><a href=\"whois.php?name={$t['login']}\"" . ($t['destination'] ? ' class="work"' : '') . ">{$t['login']}" . ($t['clan'] ? ' <span class="muted">(' . $t['clan'] . ')</span>' : '') . "</a></li>\n";
			if (++$l > $MAXUSERS) echo "\t\t<li class=\"result\">{$Lang['morethan']} $MAXUSERS</li>\n";
			echo "\t</ul>\n";
		}

		tableend($s . '<a href="galaxy.php?galaxy=' . $galaxy . '">' . $Lang['GoBack'] . ' &gt;&gt;</a>');
	}
}

// ===========================================================================
// GALAXY
// ===========================================================================

elseif ($galaxy) {
	$db->query("SELECT * FROM `{$prefix}universe` WHERE `name` = '{$Player['galaxy']}' LIMIT 1;");
	$tt = $db->fetchrow();

	$db->query("SELECT * FROM `{$prefix}space` WHERE `name` = '{$Player['planet']}' LIMIT 1;");
	$uu = $db->fetchrow();

	$db->query("SELECT * FROM `{$prefix}universe` WHERE `name`='$galaxy' LIMIT 1;");
	if ($t = $db->fetchrow()) {
		$galaxydistance = galaxydistance($t, $tt);

		$db->query("SELECT * FROM `{$prefix}space` WHERE `galaxy`='{$t['name']}' AND `type`='planet' ORDER BY `system`, `name` LIMIT 0, 100;");
		$planets = array();
		while ($u = $db->fetchrow()) $planets[] = $u;

		tablebegin($Lang['Galaxy'] . ': ' . strcap($t['name']));
?>	<div class="colony">
		<div class="colony-info">
			<dl class="facts">
				<dt><?php echo $Lang['ObjectName[]'][$t['type']]; ?></dt><dd class="minus"><?php echo strcap($t['name']); ?></dd>
<?php if ($galaxydistance > 0.1) { ?>				<dt><?php echo $Lang['Distance']; ?></dt><dd class="result"><?php echo number_format(num($galaxydistance), 1, $Lang['DecPoint'], ' '); ?></dd>
<?php } if ($t['type'] == 'galaxy') { ?>				<dt><?php echo $Lang['CPC']; ?></dt><dd class="plus"><?php echo count($planets); ?></dd>
<?php } ?>			</dl>
		</div>
		<?php echo card_image(array("gallery/galaxy/{$t['name']}.jpg", "gallery/galaxy/icons/{$t['name']}.jpg"), '', strcap($t['name'])); ?>

	</div>
<?php
		if (file_exists("flash/universe/{$t['name']}.swf")) {
			tablebreak();
			echo "\t<div class=\"flash\">\n";
			swf($t['name'], "flash/universe/{$t['name']}.swf", 400, 300, '#000000');
			echo "\t</div>\n";
		}

		// planetas
		if ($planets) {
			tablebreak();
			echo "\t<div class=\"cards\">\n";
			foreach ($planets as $u) {
				$href = "{$_SERVER['PHP_SELF']}?galaxy=$galaxy&amp;object={$u['name']}";
				$chips = array();
				if (($distance = $galaxydistance + planetdistance($u, $uu)) > 0.1) $chips[] = array('title' => $Lang['Distance'], 'label' => $Lang['Distance'], 'html' => '<span class="result">' . number_format(num($distance), 1, $Lang['DecPoint'], ' ') . '</span>');
				$chips[] = array('title' => $Lang['SizeC'], 'label' => $Lang['SizeC'], 'html' => '<span class="result">' . $Lang['SizeT'][$u['class']] . '</span>');
				if ($u['explored'] > 0.1) $chips[] = array('title' => $Lang['Explored'], 'label' => $Lang['Explored'], 'html' => '<span class="minus">' . number_format(num($u['explored']), 2, $Lang['DecPoint'], ' ') . ' %</span>');
				if ($u['abandoned']) $chips[] = array('title' => $Lang['AbandonedC'], 'label' => $Lang['AbandonedC'], 'html' => $u['abandoned']);
?>		<article class="card<?php echo $u['name'] == $Player['planet'] ? ' card-here' : ''; ?>">
			<?php echo card_image(array("gallery/space/icons/{$u['name']}.jpg", "gallery/space/{$u['name']}.jpg"), $href, strcap($u['name'])); ?>

			<div class="card-body">
				<h4 class="card-title"><a href="<?php echo $href; ?>"><span class="plus"><?php echo strcap($u['name']); ?></span></a></h4>
				<p class="card-meta"><span class="capacity"><?php echo $Lang['Technology[]'][$u['technology']]; ?></span><?php if ($u['system']) echo " &middot; <span class=\"work\">{$u['system']}</span>"; ?></p>
				<?php echo card_chips($chips); ?>

			</div>
		</article>
<?php
			}
			echo "\t</div>\n";
		}

		// outros objetos (asteroides, meteoros...)
		$db->query("SELECT * FROM `{$prefix}space` WHERE `galaxy`='{$t['name']}' AND `type`<>'planet' ORDER BY `type`,`name` LIMIT 0, 100;");
		if ($db->numrows()) {
			tablebreak();
			$objects = array();
			while ($row = $db->fetchrow()) $objects[] = $row;
			echo "\t<div class=\"cards\">\n";
			foreach ($objects as $o) {
				$href = "galaxy.php?galaxy=$galaxy&amp;object={$o['name']}";
				$chips = array();
				if (($distance = $galaxydistance + planetdistance($o, $uu)) > 0.1) $chips[] = array('title' => $Lang['Distance'], 'label' => $Lang['Distance'], 'html' => number_format(num($distance), 1, $Lang['DecPoint'], ' '));
?>		<article class="card">
			<?php echo card_image(array("gallery/space/icons/{$o['name']}.jpg", "gallery/space/{$o['name']}.jpg"), $href, strcap($o['name'])); ?>

			<div class="card-body">
				<h4 class="card-title"><a href="<?php echo $href; ?>"><span class="plus"><?php echo strcap($o['name']); ?></span></a></h4>
				<p class="card-meta"><b><?php echo $Lang['Objects[]'][$o['type']]; ?></b><?php if ($o['system']) echo " &middot; <span class=\"work\">{$o['system']}</span>"; ?></p>
				<?php echo card_chips($chips); ?>

			</div>
		</article>
<?php
			}
			echo "\t</div>\n";
		}

		tableend('<a href="galaxy.php">' . $Lang['GoBack'] . ' &gt;&gt;</a>');
	}
}

// ===========================================================================
// UNIVERSE
// ===========================================================================

else {
	$db->query("SELECT * FROM `{$prefix}universe` WHERE `name`='{$Player['galaxy']}' LIMIT 1");
	$tt = $db->fetchrow();

	$db->query("SELECT * FROM `{$prefix}universe` ORDER BY `type` ASC LIMIT 0 , 100");

	tablebegin($Lang['Universe']);

	echo "\t<div class=\"cards\">\n";
	while ($t = $db->fetchrow()) {
		$href = "galaxy.php?galaxy={$t['name']}";
		$distance = round(num(100 * galaxydistance($t, $tt))) / 100;
		$chips = array();
		if ($distance > 1) $chips[] = array('title' => $Lang['Distance'], 'label' => $Lang['Distance'],
			'html' => $distance > 100000 ? "<span class=\"capacity\">{$Lang['Unreachable']}</span>" : '<span class="result">' . number_format(num($distance), 2, $Lang['DecPoint'], ' ') . '</span>');
?>		<article class="card<?php echo $t['name'] == $Player['galaxy'] ? ' card-here' : ''; ?>">
			<?php echo card_image(array("gallery/galaxy/icons/{$t['name']}.jpg", "gallery/galaxy/{$t['name']}.jpg"), $href, strcap($t['name'])); ?>

			<div class="card-body">
				<h4 class="card-title"><a href="<?php echo $href; ?>"><span class="minus"><?php echo strcap($t['name']); ?></span></a></h4>
				<p class="card-meta"><b><?php echo $Lang['ObjectName[]'][$t['type']]; ?></b></p>
				<?php echo card_chips($chips); ?>

			</div>
		</article>
<?php
	}
	echo "\t</div>\n";

	tablebreak();

?>	<p class="linkbox"><?php echo anchor('propaganda/wallpapers/map.jpg', $Lang['UniverseMap']); ?></p>
<?php

	tableend($Lang['Universe']);
}

require('include/footer.php');
