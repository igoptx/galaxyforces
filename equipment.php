<?php

// ===========================================================================
// Equipment {equipment.php}
// ===========================================================================

// ---------------------------------------------------------------------------
//	Version:	1.3
//	Modified:	2005-11-13
//	Author(s):	zoltarx
// ---------------------------------------------------------------------------

// ===========================================================================
// This file is a part of Galaxy Forces project.
// ===========================================================================

$auth = true;

require('include/header.php');

$back = getvar('back');
$page = abs(num(getvar('page')));
$view = getvar('view');

if (!$back) $back = 'control.php';

$pagename = $Lang['Equipment'];

$avatar = $Player['avatar'] ? $Player['avatar'] : 'gallery/avatars/noavatar.gif';

// Cartão de objeto: imagem, nome (+nível), atributos e ações/formulário.
function item_card($t, $stats, $actions)
{
	global $Lang;
	$name = $Lang['items'][$t['name']]['name'] . ($t['level'] ? " +{$t['level']}" : '');
	$href = "description.php?type=equipment&amp;back=equipment.php&amp;subject={$t['name']}&amp;id={$t['id']}";
	echo "\t\t<article class=\"card\">\n\t\t\t" . card_image(array("gallery/items/{$t['name']}.jpg", "gallery/items/icons/{$t['name']}.jpg"), $href, $name) . "\n";
	echo "\t\t\t<div class=\"card-body\">\n\t\t\t\t<h4 class=\"card-title\"><a href=\"$href\">$name</a>"
		. (@$t['count'] ? "<span class=\"badge\" title=\"{$Lang['Count']}\">" . div($t['count']) . '</span>' : '') . "</h4>\n";
	echo "\t\t\t\t" . card_chips($stats) . "\n";
	echo "\t\t\t\t<div class=\"card-actions\">$actions</div>\n\t\t\t</div>\n\t\t</article>\n";
}

// Atributos de combate de um objeto.
function item_stats($t)
{
	global $Lang;
	$stats = array();
	if ($t['min'] || $t['max']) $stats[] = array('title' => $Lang['Damage'], 'label' => $Lang['Damage'], 'html' => "<span class=\"plus\">{$t['min']}-{$t['max']}</span>");
	if ($t['armor']) $stats[] = array('title' => $Lang['Armor'], 'label' => $Lang['Armor'], 'html' => "<span class=\"minus\">{$t['armor']}</span>");
	return $stats;
}

// ===========================================================================
// ERRORS
// ===========================================================================

if ($errors) {
	tablebegin('<font class="error">' . $Lang['Error'] . '!</font>', '400');
	echo "\t\t<br />\n\t\t<font class=\"h3\">{$Lang['ErrorProblems']}</font><br />\n\t\t<br />\n\t\t<font class=\"error\">$errors</font>\n\t\t<br />\n\t\t<a href=\"javascript:history.back(1)\">{$Lang['GoBack']} &gt;&gt;</a><br />\n\t\t<br />\n";
	tableend("<a href=\"$back\">{$Lang['GoBack']} &gt;&gt;</a>");
	sound('error');
}

// ===========================================================================
// RESULT
// ===========================================================================

elseif ($result) {
	tablebegin($pagename, 400);
	echo "\t\t<br /><font class=\"result\">$result</font><br /><a href=\"{$_SERVER['PHP_SELF']}\">{$Lang['GoBack']}&nbsp;&gt;&gt;</a><br /><br />";
	tableend($pagename);
}

// ===========================================================================
// SELL
// ===========================================================================

elseif ($view == 'sellitem' && checkplace('itemshop')) {
	tablebegin($Lang['SellItem'], 500);

	$Backpack = array();
	foreach ($Equipment as $t) if (! $t['active']) $Backpack[] = $t;

	if ($Backpack) {
		$mod = reputationmodifier($Player['reputation']);
		if (($ratio = $place['extra']) < 1) $ratio = 1;
		$ratio /= 5;

		echo "\t<div class=\"cards\">\n";
		foreach ($Backpack as $t) {
			$stats = array(array('title' => $Lang['Price'], 'icon' => 'images/credits.jpg', 'html' => '<span class="result">' . div(round(num($t['price'] * $ratio / $mod))) . '</span>'));
			$form = "<form action=\"equipment.php\" method=\"POST\"><input type=\"hidden\" name=\"action\" value=\"sellitem\"><input type=\"hidden\" name=\"view\" value=\"sellitem\"><input type=\"hidden\" name=\"id\" value=\"{$t['id']}\">"
				. ($t['count'] ? "<input class=\"amount\" type=\"text\" size=\"4\" name=\"amount\" value=\"{$t['count']}\" title=\"{$Lang['Count']}\">" : '')
				. "<input type=\"submit\" value=\"{$Lang['Sell']}\"></form>";
			item_card($t, $stats, $form);
		}
		echo "\t</div>\n";
	}

	tableend(anchor('itemshop.php', $Lang['GoBack']));
}

// ===========================================================================
// EQUIPMENT
// ===========================================================================
else {
	tablebegin($pagename);

	echo "\t<script>\n\t<!--\n\n\tfunction ask(\$url) {\n\t\tif (confirm('{$Lang['AreYouSure?']}')) location.href = \$url;\n\t}\n\n\tfunction avatar() {\n\t\t\$msg = prompt('{$Lang['EnterAvatarURL']}', '');\n\t\tif (\$msg > '') {\n\t\t\t\$msg = \$msg.replace(/\\+/g,\"%2B\"); // code: kot\n\t\t\t\$msg = \$msg.replace(/\\&/g,\"%26\");\n\t\t\t\$msg = \$msg.replace(/\\#/g,\"%23\");\n\t\t\t\$url = '{$_SERVER['PHP_SELF']}?name={$name}&rid={$rid}&action=changeplayeravatar&url=' + \$msg;\n\t\t\tdocument.location.href = \$url;\n\t\t}\n";
	if ($Player['avatar']) echo "\t\telse if (\$msg != null) {\n\t\t\t\$url = '{$_SERVER['PHP_SELF']}?name={$name}&rid={$rid}&action=changeplayeravatar';\n\t\t\tdocument.location.href = \$url;\n\t\t}\n";
	echo "\t}\n\t//-->\n\t</script>\n";

	// atributos do herói (os opcionais só aparecem se tiverem valor, como antes)
	$extra = array('hit' => array('Hit', 0), 'criticalhit' => array('CriticalHit', 0), 'critical' => array('Critical', null), 'block' => array('Block', 0),
		'deaf' => array('Deafness', 0), 'hide' => array('Hiding', 0), 'protection' => array('Protection', 0));
?>	<div class="colony">
		<div class="colony-info">
			<dl class="facts">
				<dt><?php echo $Lang['Name']; ?></dt><dd><a href="whois.php?name=<?php echo $Player['login']; ?>"><?php echo $Player['login']; ?></a></dd>
				<dt><?php echo $Lang['Level']; ?></dt><dd class="plus"><?php echo $Player['level']; ?></dd>
<?php if ($Player['clan']) { ?>				<dt><?php echo $Lang['Group']; ?></dt><dd><a href="clan.php"><?php echo $Player['clan']; ?></a></dd>
<?php } ?>				<dt><?php echo $Lang['Reputation']; ?></dt><dd><span class="result"><?php echo $Player['reputation']; ?></span> (<?php echo $playernature; ?>)</dd>
				<dt><?php echo $Lang['Strength']; ?></dt><dd class="result"><?php echo floor(num(100 * $Player['strength'])) / 100; ?></dd>
				<dt><?php echo $Lang['Damage']; ?></dt><dd class="plus"><?php echo $Player['min']; ?> - <?php echo $Player['max']; ?></dd>
				<dt><?php echo $Lang['Agility']; ?></dt><dd class="work"><?php echo floor(num(100 * $Player['agility'])) / 100; ?></dd>
				<dt><?php echo $Lang['Armor']; ?></dt><dd class="minus"><?php echo $Player['armor']; ?></dd>
				<dt>MP</dt><dd><span class="<?php echo $Player['mp'] > $Player['mpmax'] ? 'work' : 'result'; ?>"><?php echo $Player['mp']; ?></span> / <span class="capacity"><?php echo $Player['mpmax']; ?></span> <?php echo amount($Player['mpgain']); ?></dd>
				<dt>HP</dt><dd><span class="<?php echo $Player['hp'] > $Player['hpmax'] ? 'work' : 'result'; ?>"><?php echo $Player['hp']; ?></span> / <span class="capacity"><?php echo $Player['hpmax']; ?></span> <?php echo amount($Player['hpgain']); ?></dd>
<?php
	foreach ($extra as $key => $e) {
		if (!$Player[$key]) continue;
		echo "\t\t\t\t<dt>{$Lang[$e[0]]}</dt><dd>" . ($e[1] === null ? amount($Player[$key]) : amount($Player[$key], $e[1])) . "</dd>\n";
	}
?>			</dl>
		</div>
		<a class="colony-avatar" href="javascript:avatar()"><img src="<?php echo $avatar; ?>" alt="" width="160" height="160" /></a>
	</div>
<?php
	$Active = null;
	$Backpack = null;

	if ($Equipment)
		foreach ($Equipment as $t)
			if ($t['active']) $Active[] = $t;
			else $Backpack[] = $t;

	if ($Active) {
		tablebreak();
		echo "\t<div class=\"cards\">\n";
		foreach ($Active as $t)
			item_card($t, item_stats($t), "<a class=\"action\" href=\"equipment.php?action=unequip&amp;id={$t['id']}&amp;rid=$rid\">{$Lang['Unequip']}</a>");
		echo "\t</div>\n";
	}

	tableend($Lang['Equipment']);

	// nave
	tablebegin($Lang['Ship']);

	$ship = $Player['ship'];
	$shipname = $Lang['units'][$ship]['name'];
	$shiphref = "description.php?type=unit&amp;back=equipment.php&amp;page=$page&amp;subject=$ship";
?>	<div class="cards">
		<article class="card">
			<?php echo card_image(array("gallery/units/$ship.jpg", "gallery/units/icons/$ship.jpg"), $shiphref, $shipname); ?>

			<div class="card-body">
				<h4 class="card-title"><a href="<?php echo $shiphref; ?>"><span class="capacity"><?php echo $shipname; ?></span></a></h4>
				<?php echo card_chips(array(array('title' => $Lang['Speed'], 'label' => $Lang['Speed'], 'html' => '<span class="plus">' . $Var['units'][$ship]['speed'] . '</span>'))); ?>

			</div>
		</article>
	</div>
<?php
	// mochila
	if ($Backpack) {
		tablebreak();
		echo "\t<div class=\"cards\">\n";
		foreach ($Backpack as $t) {
			if ($view == 'giveitems' && $name) {
				$actions = '<form action="equipment.php" method="POST"><input type="hidden" name="action" value="giveitems" /><input type="hidden" name="view" value="giveitems" /><input type="hidden" name="id" value="' . $t['id'] . '" /><input type="hidden" name="name" value="' . $name . '" />'
					. ($t['count'] ? '<input class="amount" type="text" size="5" name="amount" value="0" title="' . $Lang['Count'] . '" />' : '')
					. '<input type="submit" value="' . $Lang['Give'] . '" /></form>';
			}
			else {
				$actions = '';
				switch ($t['type']) {
				case 'guns': case 'shields': case 'engine': case 'belt': case 'helmet': case 'armor': case 'gloves': case 'implant': case 'artifact': case 'weapon': case 'weapon2':
					$actions .= "<a class=\"action\" href=\"equipment.php?rid=$rid&amp;action=equip&amp;id={$t['id']}\">{$Lang['Equip']}</a> ";
					break;
				case 'item':
					$actions .= "<a class=\"action\" href=\"equipment.php?rid=$rid&amp;action=use&amp;id={$t['id']}\">{$Lang['Use']}</a> ";
					break;
				case 'drink':
					$actions .= "<a class=\"action\" href=\"equipment.php?rid=$rid&amp;action=use&amp;id={$t['id']}\">{$Lang['Drink']}</a> ";
					break;
				}
				$actions .= "<a href=\"javascript:ask('equipment.php?rid=$rid&action=dropitem&id={$t['id']}')\" class=\"delete\">{$Lang['DropItem']}&nbsp;&gt;&gt;</a>";
			}
			item_card($t, item_stats($t), $actions);
		}
		echo "\t</div>\n";
	}

	tableend("<a href=\"$back\">{$Lang['GoBack']} &gt;&gt;</a>");
}

require('include/footer.php');
