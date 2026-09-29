<?php

// ===========================================================================
// Colony {colony.php}
// ===========================================================================

// ---------------------------------------------------------------------------
//	Version:	1.7
//	Modified:	2005-11-19
//	Author(s):	zoltarx
// ---------------------------------------------------------------------------

// ===========================================================================
// This file is a part of Galaxy Forces project.
// ===========================================================================

$index = 'colony';
$auth = true;

require('include/header.php');

locale('colony');

$view = getvar('view');
$confirm = getvar('confirm');
$technology = getvar('technology');
$planet = strip_tags(escapesql(getvar('planet')));

$pagename = $Lang['Colony'];

// ===========================================================================
// ACTIONS
// ===========================================================================

if ($action) {
	switch ($action) {
		case 'abandon':
			if ($Colony && (($confirm = getvar('confirm')) == $secret)) {
				if (($exp = round(num(0.05 * $Player['exp']))) < 250) $exp = 250;
				if ($exp > $Player['exp']) $exp = $Player['exp'];
				$Player['score'] = round(num(0.85 * $Player['score']));
				$Player['exp'] -= $Cost['exp'];
				$db->query("UPDATE {$prefix}users SET exp='{$Player['exp']}',score='{$Player['score']}' WHERE login='$login' LIMIT 1;");
				$db->query("UPDATE {$prefix}space SET abandoned=abandoned+1 WHERE name='{$Colony['planet']}' LIMIT 1;");
				$db->query("DELETE FROM {$prefix}colonies WHERE name='{$Colony['name']}' LIMIT 1;");
				audit('colony', 'abandon', $Colony['name'], $Colony['planet']);
				$db->query("DELETE FROM {$prefix}researches WHERE login='$login';");
				$db->query("DELETE FROM {$prefix}exploration WHERE login='$login';");
				$db->query("DELETE FROM {$prefix}buildings WHERE login='$login';");
				$db->query("DELETE FROM {$prefix}productions WHERE login='$login';");
				$db->query("DELETE FROM {$prefix}attacks WHERE login='$login';");
				sendmessage($Lang['AbaS'], $Lang['AbaM'], '', $login, 'report');
				@chat("<font color=\"yellow\">$login</font>", "<font class=\"capacity\"><i>abandoned <b>{$Colony['name']}</b></i></font>...");
				$result = "{$Lang['CA1']}<br />";
				$Colony = array();
			}
			else $errors .= "{$Lang['HaveNoColony']}<br />";
			break;

		case 'create':
			$db->query("SELECT `technology` FROM `{$prefix}space` WHERE `name`='$planet';");
			if ($t = $db->fetchrow()) {
				$technology = $t['technology'];
				if ($technology == 'tron' && $Player['reputation'] > -5 || $technology == 'tron' && $Player['reputation'] > -5 || $technology == 'cyber' || $technology == 'necro' && $Player['voyaged'] < 250000 || $technology == 'ami') $errors .= "{$Lang['ErrorCannotUse']}<br />";
				elseif ($name) {
					$db->query("SELECT `name` FROM `{$prefix}colonies` WHERE `name`='$name';");
					if ($db->numrows()) $errors .= "{$Lang['ErrorColonyAlreadyExists']}<br />";
					else {
						$base = $technology == 'human' ? 1 : 0;
						$tron = $technology == 'tron' ? 1 : 0;
						$ami = $technology == 'ami' ? 1 : 0;
						$cyber = $technology == 'cyber' ? 1 : 0;
						$necro = $technology == 'necro' ? 1 : 0;
						$energy = 500 + ($technology == 'tron' ? 1500 : 0);
						$silicon = ($technology == 'tron' ? 100 : 0);
						$metal = ($technology == 'human' ? 300 : 0) + ($technology == 'tron' ? 200 : 0);
						$food = ($technology == 'human' ? 200 : 0);
						$colonists = ($technology == 'human' ? 5 : 0);
						$db->query("INSERT INTO `{$prefix}colonies` (`name`,`owner`,`planet`,`thicks`,`base`,`tron`,`ami`,`cyber`,`necro`,`energy`,`silicon`,`metal`,`food`,`colonists`) VALUES ('$name','$login','$planet','$stardate',$base,$tron,$ami,$cyber,$necro,$energy,$silicon,$metal,$food,$colonists);");
						audit('colony', 'create', $name, "$planet ($technology)");
						if ($Player['credits'] + $Player['bank'] < 25000) {
							$db->query("UPDATE `{$prefix}users` SET `credits`='25000',`bank`='0' WHERE `login`='$login' LIMIT 1;");
							sendmessage($Lang['BankDonatedSubject'], $Lang['BankDonated'] . '<b>25000</b>!<br />', $Lang['GalaxyBank'], $login, 'report');
						}
						$result .= "{$Lang['Colony']} <b>$name</b> {$Lang['CC1']} <font class=\"capacity\">$planet</font><br />";
					}
				}
				else $errors .= "{$Lang['ErrorUnknownName']}<br />";
			}
			break;
	}
}

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
// ABANDON
// ===========================================================================

elseif ($Colony && $view == 'abandon') {
	tablebegin('<font class="work">' . $Lang['Confirmation'] . '</font>', 500);

	if (($exp = round(num(0.05 * $Player['exp']))) < 250) $exp = 250;
	if ($exp > $Player['exp']) $exp = $Player['exp'];
	$score = round(num(0.15 * $Player['score']));

	echo "<h3>{$Lang['AbandonColony']}</h3>";

	if ($banned) echo "\t<font class=\"error\">{$Lang['Banned']}!</font><br />\n";
	else {
		echo $Lang['AC1'].':<br /><br /><b>'.$Lang['Experience'].'</b>: <font class="minus">'.div($exp).'</font><br /><b>'.$Lang['Score'].'</b>: <font class="work">'.div($score).'</font><br /><br />';
		echo "\t<font class=\"error\">{$Lang['RUSure']}</font><br /><br />\n";
		echo "\t".'<a href="colony.php?action=abandon&confirm='.$secret.'" class="delete">'.$Lang['Yes'].'</a>&nbsp; &nbsp; &nbsp; &nbsp;<a href="colony.php">'.$Lang['No']."</a><br />\n";
	}

	echo "\t<br />\n";

	tableend('<a href="colony.php">' . $Lang['GoBack'] . ' &gt;&gt;</a>');
}

// ===========================================================================
// MANAGEMENT
// ===========================================================================

elseif ($Colony && $view == 'management') {
	tablebegin($Lang['ColonyManagement'], 500);
	
	echo '<h3>'.$Lang['Statistics'].'</h3>';

	?><table width="100%" cellspacing="0" cellpadding="0"><?php

	echo '<tr><td width="12">&nbsp;</td><td width="30%"><b>'.$Lang['Workforce'].'</b>:</td><td>&nbsp;</td><td width="10%" align="center"><font class="work">'.div($Colony['workforce'], 1, $Lang['DecPoint']).'</font></td><td width="12">&nbsp;</td>';
	echo '<td width="30%"><b>'.$Lang['CrewLost'].'</b>:<td>&nbsp;</td><td width="10%" align="center"><font class="minus">'.div($Colony['lost']).'</font></td><td>&nbsp;</td></tr>';

	echo '<tr><td>&nbsp;</td><td><b>'.$Lang['Scienceforce'].'</b>:</td><td>&nbsp;</td><td align="center"><font class="result">'.div($Colony['scienceforce'], 1, $Lang['DecPoint']).'</font></td><td>&nbsp;</td>';
	echo '<td><b>'.$Lang['Satisfaction'].'</b>:<td>&nbsp;</td><td align="center">'.amount($Colony['satisfaction']).'</td><td>&nbsp;</td></tr>';

	echo '</table><br />';

	tablebreak();
	
	echo '<h3>'.$Lang['Expenses'].'</h3>';

	echo '<form action="colony.php" method="POST"><input type="hidden" name="action" value="management" /><input type="hidden" name="view" value="management" />';
	echo '<table cellspacing="0" cellpadding="0">';

	echo '<tr><td width="12">&nbsp;</td><td><b>'.$Lang['Infrastructure'].'</b>:</td><td>&nbsp;</td><td align="center"><input size="3" type="text" name="infrastructure" value="'.$Colony['infrastructure'].'" /> %</td><td width="12">&nbsp;</td></tr>';
	echo '<tr><td width="12">&nbsp;</td><td><b>'.$Lang['Science'].'</b>:</td><td>&nbsp;</td><td align="center"><input size="3" type="text" name="science" value="'.$Colony['science'].'" /> %</td><td width="12">&nbsp;</td></tr>';
	echo '<tr><td width="12">&nbsp;</td><td><b>'.$Lang['Military'].'</b>:</td><td>&nbsp;</td><td align="center"><input size="3" type="text" name="military" value="'.$Colony['military'].'" /> %</td><td width="12">&nbsp;</td></tr>';
	echo '<tr><td colspan="5">&nbsp;</td></tr>';
	echo '<tr><td colspan="5" align="center"><input type="submit" value="'.$Lang['Change'].'" /></td></tr>';

	echo '</table></form><br />';

	tableend(anchor('colony.php', $Lang['GoBack']));
}

// ===========================================================================
// COLONY
// ===========================================================================

elseif ($Colony) {

	function fill($name) {
		global $Colony, $Lang;
	       if (@$Colony[$name.'capacity'] && (($x = round(num(100*$Colony[$name]/$Colony[$name.'capacity']))) >= 0)) {
			if ($x == 0) return " (<font class=\"minus\">{$Lang['Empty']}</font>)";
			elseif ($x == 100) return " (<font class=\"plus\">{$Lang['Full']}</font>)";
			elseif ($x > 100) return " (<font class=\"work\">{$Lang['Overload']}</font>)";
			else return " (<font class=\"result\">$x%</font>)";
		}
		else return '';
	}

	foreach (array('energy','silicon','metal','uran','plutonium','deuterium','food') as $name) $Colony[$name.'fill'] = fill($name);

	tablebegin($pagename);

	// população: só os grupos que a colónia tem, como antes
	$people = array('colonists' => 'Colonists', 'scientists' => 'Scientists', 'soldiers' => 'Soldiers', 'clones' => 'Clones', 'drones' => 'Drones', 'souls' => 'Souls');
	$freekey = array('colonists' => 'colonists', 'scientists' => 'scientists', 'soldiers' => 'soldiers', 'clones' => 'clones', 'drones' => 'clones', 'souls' => 'souls');
?>	<div class="colony">
		<div class="colony-info">
			<dl class="facts">
				<dt><?php echo $Lang['Colony name']; ?></dt><dd class="capacity"><?php echo $Colony['name']; ?></dd>
				<dt><?php echo $Lang['Planet']; ?></dt><dd><a href="galaxy.php?galaxy=<?php echo $Galaxy['name']; ?>&amp;object=<?php echo $Planet['name']; ?>"><?php echo strcap($Planet['name']); ?></a>, <a href="galaxy.php?galaxy=<?php echo $Galaxy['name']; ?>"><?php echo strcap($Galaxy['name']); ?></a></dd>
				<dt><?php echo $Lang['Technology']; ?></dt><dd class="result"><?php echo $Planet['technology']; ?></dd>
				<dt><?php echo $Lang['Population']; ?></dt><dd class="result"><?php echo div($Colony['colonists'] + $Colony['scientists'] + $Colony['soldiers']); ?></dd>
				<dt><?php echo $Lang['Robots']; ?></dt><dd class="result"><?php echo div($Colony['bx1'] + $Colony['bx2'] + $Colony['bx5'] + $Colony['bx10']); ?></dd>
<?php
	foreach ($people as $key => $lang) {
		if (!@$Colony[$key]) continue;
		$free = $key == 'drones' ? @$Colony['clonesfree'] : @$Colony[$freekey[$key] . 'free'];
		$freeval = $key == 'drones' ? @$Colony['dronesfree'] : $free;
?>				<dt><?php echo $Lang[$lang]; ?></dt><dd><span class="result"><?php echo div($Colony[$key]); ?></span><?php if (in_array($key, array('colonists', 'scientists', 'soldiers')) || $free) echo ' <span class="muted">(<span class="capacity">' . div($freeval) . '</span> ' . $Lang['freeinbase'] . ')</span>'; ?><?php if ($key == 'soldiers') { ?> <a href="train.php?RID=<?php echo $RID; ?>"><?php echo $Lang['TrainS']; ?> &gt;&gt;</a><?php } ?></dd>
<?php
	}
?>				<dt><?php echo $Lang['Damaged']; ?></dt><dd><span class="delete"><?php echo round(num(100 * $Colony['damage'])) / 100; ?>%</span><?php if ($Colony['damage']) { ?> <a href="colony.php?action=repair"><?php echo $Lang['Repair']; ?> &gt;&gt;</a><?php } ?></dd>
			</dl>
			<div class="colony-desc">
<?php if ($Colony['description']) { ?>				<b><?php echo $Lang['Description']; ?></b> (<a href="javascript:description()"><?php echo $Lang['change']; ?></a>):
				<span class="result"><?php echo emoticons($Colony['description']); ?></span>
<?php } else { ?>				<a href="javascript:description()"><?php echo $Lang['EditDescription']; ?> &gt;&gt;</a>
<?php } ?>			</div>
			<p class="colony-links"><?php echolink('colony.php?view=management', $Lang['ColonyManagement']); ?> <?php echolink('colony.php?view=abandon', $Lang['AbandonColony'], 'delete'); ?></p>
		</div>
		<a class="colony-avatar" href="javascript:avatar()"><img src="<?php echo $Colony['avatar'] ? $Colony['avatar'] : 'gallery/avatars/noavatar.gif'; ?>" alt="" width="160" height="160" /></a>
	</div>
<?php
	tablebreak();

	// um cartão por recurso: quantidade, ocupação do armazém, fontes e produção por ciclo
	echo "\t<div class=\"cards cards-res\">\n";
	foreach (array('energy','silicon','metal','uran','plutonium','deuterium','food','crystals') as $name) {
		if (!($Colony[$name] || @$Colony[$name.'capacity'] || @$Colony[$name.'sources'])) continue;
		$label = $Lang[strcap($name)];
?>		<article class="card card-res">
			<?php echo card_image(array("gallery/resources/icons/$name.jpg"), "description.php?subject=$name&amp;back=colony.php", $label); ?>

			<div class="card-body">
				<h4 class="card-title"><?php echo $label; ?></h4>
				<p class="res-amount value"><?php echo div($Colony[$name]); ?></p>
<?php
		if (@$Colony[$name.'capacity']) {
			$pct = 100 * $Colony[$name] / $Colony[$name.'capacity'];
?>				<div class="meter<?php echo $pct > 100 ? ' over' : ''; ?>"><i style="width: <?php echo max(0, min(100, round(num($pct)))); ?>%"></i></div>
				<p class="card-meta"><?php echo $Lang['Capacity']; ?>: <span class="capacity"><?php echo div($Colony[$name.'capacity']); ?></span><?php echo $Colony[$name.'fill']; ?></p>
<?php
		}
		if (@$Colony[$name.'sources']) echo "\t\t\t\t<p class=\"card-meta\">{$Lang['Sources']}: <span class=\"work\">" . div($Colony[$name.'sources']) . "</span></p>\n";
		if (@$Colony[$name.'plus'] || @$Colony[$name.'minus']) {
?>				<p class="card-meta rates"><?php echo $Lang['Rates']; ?>: <?php echo amount($Colony[$name.'plus']); ?> <?php echo amount(-$Colony[$name.'minus']); ?> = <span class="result"><?php echo div($Colony[$name.'plus'] - $Colony[$name.'minus'], 1, $Lang['DecPoint']); ?></span></p>
<?php
		}
?>			</div>
		</article>
<?php
	}
?>		<article class="card card-res">
			<?php echo card_image(array("gallery/resources/icons/credits.jpg"), "description.php?subject=credits&amp;back=colony.php", $Lang['Credits']); ?>

			<div class="card-body">
				<h4 class="card-title"><?php echo $Lang['Credits']; ?></h4>
				<p class="res-amount value"><?php echo div($Player['credits']); ?></p>
				<p class="card-meta"><?php echo $Lang['Bank']; ?>: <span class="value"><?php echo div($Player['bank']); ?></span></p>
			</div>
		</article>
	</div>
<?php

	tableend($Lang['Resources']);

	echo "\t<script>\n\t<!--\n\tfunction avatar() {\n\t\t\$msg = prompt('{$Lang['EnterAvatarURL']}', '');\n\t\tif (\$msg > '') {\n\t\t\t\$url = '{$_SERVER['PHP_SELF']}?rid=$rid&action=changeavatar&url=' + \$msg;\n\t\t\t	document.location.href = \$url;\n\t\t}\n";
	if ($Colony['avatar']) echo "\t\telse if (\$msg != null) {\n\t\t\t\$url = '{$_SERVER['PHP_SELF']}?rid=$rid&action=changeavatar';\n\t\t\tdocument.location.href = \$url;\n\t\t}\n";
	echo "\t}\n\n\tfunction description() {\n\t\t\$msg = prompt('{$Lang['EnterDescription']}:', '');\n\t\t\$msg = \$msg.replace(/\\+/g,\"%2B\"); // code: kot\n\t\t\$msg = \$msg.replace(/\\&/g,\"%26\");\n\t\t\$msg = \$msg.replace(/\\#/g,\"%23\");\n\t\tif (\$msg > '') {\n\t\t\t\$url = '{$_SERVER['PHP_SELF']}?rid=$rid&action=changedescription&description=' + \$msg;\n\t\t\tdocument.location.href = \$url;\n\t\t}\n";
	if ($Colony['description']) echo "\t\telse if (\$msg != null) {\n\t\t\t\$url = '{$_SERVER['PHP_SELF']}?rid=$rid&action=changedescription';\n\t\t\tdocument.location.href = \$url;\n\t\t}\n";
	echo "\t}\n\t//-->\n\t</script>\n";
}

// ===========================================================================
// CREATE
// ===========================================================================

elseif ($view == 'create') {
	if ($planet) {
		$db->query("SELECT `technology` FROM `{$prefix}space` WHERE `name`='$planet';");
		if ($t = $db->fetchrow()) $technology = $t['technology'];
	}

	if ($technology && isset($Lang['Race[]'][$technology])) {
		tablebegin($Lang['Create'], 500);
		echo "\t<br /><font class=\"h3\">".$Lang['Technology[]'][$technology]."</font><br /><br />\n\t<font class=\"result\">{$Lang['ChoosePlanet']}</font><br /><br />\n";
		$db->query("SELECT `name`,`galaxy` FROM `{$prefix}space` WHERE `type`='planet' AND `technology`='$technology' ORDER BY `galaxy`;");

		if ($db->numrows()) {
			echo "\t<form action=\"colony.php\" method=\"POST\" name=\"form\">\n\t<table align=\"center\"><input type=\"hidden\" name=\"action\" value=\"create\" />\n";
			echo "\t<tr><td><b>{$Lang['Name']}</b>:</td><td>&nbsp; &nbsp;</td><td><input name=\"name\" size=\"24\" maxlength=\"32\" /></td></tr>\n";
			echo "\t<tr><td>&nbsp;</td></tr>\n";
			echo "\t<tr><td><b>{$Lang['Planet']}</b>:</td><td></td><td><select name=\"planet\">";
			while ($t = $db->fetchrow()) echo "<option value=\"{$t['name']}\"".($planet == $t['name'] ? ' selected="selected"' : '').'>'.strcap($t['name']).' ('.strcap($t['galaxy']).')'.'</option>';
			echo "</select></td></tr>\n\t<tr><td>&nbsp;</td></tr>\n\t<tr><td colspan=\"3\" align=\"center\"><input type=\"submit\" value=\"{$Lang['CreateColony']}\" /></td></tr>\n";
			echo "\t</table>\n\t</form>\n";
			echo "\t<script>\n\t<!--\n\t\tdocument.form.name.focus();\n\t//-->\n\t</script>\n";
		}
		else echo "\t<font class=\"error\">{$Lang['NoPlanet']}</font><br />";

		echo "\t<br />\n";
		tableend("<a href=\"colony.php?view=create\">{$Lang['GoBack']}&nbsp;&gt;&gt;</a>");
	}
	else {
		tablebegin($Lang['Create'], 500);
		echo "\t<br /><font class=\"h3\">{$Lang['Technology']}</font><br /><br />\n\t{$Lang['TechnologyTip']}<br />\n\t<br />\n";

//		foreach (array('human','tron','ami','cyber','necro') as $race) {
		foreach (array('human','tron') as $race) {
			tablebreak();
			subbegin();
			tableimg('images/bw.gif', 168, 168, "gallery/technology/$race.jpg", 160, 160, '', 'right');
			echo "\t<font class=\"h3\">".$Lang['Technology[]'][$race]."</font><br />\n";
			echo "\t<br /><font class=\"result\">".$Lang['Race[]'][$race]."</font><br />\n";
			echo "\t<br /><b>{$Lang['Requirements']}</b>: <font class=\"capacity\">".$Lang['Req[]'][$race]."</font><br />\n";
			echo "\t<br /><a href=\"colony.php?view=create&technology=$race\">{$Lang['Next']}&nbsp;&gt;&gt;</a><br />\n";
			subend();
		}


//		echo "\t<br />${Lang['NotAvailable']}<br />\n";
//		echo "\t<br />\n";

		tableend("<a href=\"control.php\">{$Lang['GoBack']}&nbsp;&gt;&gt;</a>");

	}
}

// ===========================================================================
// NO COLONY
// ===========================================================================

else {
	tablebegin($Lang['Colony'], 500);

	echo "\t<br /><font class=\"h3\">{$Lang['HaveNoColony']}</font><br /><br />\n";
	echo "\t{$Lang['NoColonyTip']}<br />\n";
	echo "\t<br /><a href=\"colony.php?view=create\">{$Lang['CreateColony']}&nbsp;&gt;&gt;</a><br />\n";
	echo "\t<br />\n";

	tableend("<a href=\"control.php\">{$Lang['GoBack']}&nbsp;&gt;&gt;</a>");
}

require('include/footer.php');
