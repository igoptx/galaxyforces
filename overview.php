<?php

// ===========================================================================
// Visão geral {overview.php}
// ===========================================================================
// Página de entrada ao estilo do OGame: a colónia num relance, a produção de
// recursos por hora com o tempo até encher o armazém, e os eventos em curso
// (construção, investigação, produção, expedição, viagem e ataques). Não muda
// nenhuma regra: só lê o estado que o motor já calcula.

$index = 'overview';
$auth = true;

require('include/header.php');

locale('overview');

$pagename = $Lang['OvTitle'];

if (!@$Colony) {
	tablebegin($Lang['OvTitle']);
	echo "\t<p class=\"muted\">" . htmlspecialchars($Lang['OvCreateColony']) . "</p>\n";
	echo "\t<p class=\"linkbox\">" . anchor('colony.php', $Lang['CreateColony']) . "</p>\n";
	tableend();
	require('include/footer.php');
	exit;
}

// segundos por ciclo -> multiplicador para "por hora"
$per_hour = $thicklength > 0 ? 3600 / $thicklength : 12;

// mensagens por ler
$db->query("SELECT COUNT(*) c FROM `{$prefix}messages` WHERE `to`='" . $db->safe($login) . "' AND `read`=0");
$unread = ($row = $db->fetchrow()) ? (int)$row['c'] : 0;
if ($unread) echo '<p class="ops-alert warning"><a href="messages.php">' . htmlspecialchars(str_replace('%s', $unread, $Lang['OvUnread'])) . ' &raquo;</a></p>' . "\n";

function ov_eta_full($stock, $capacity, $rate_hour)
{
	global $Lang;
	if ($capacity <= 0) return '';
	if ($stock >= $capacity) return '<span class="minus">' . $Lang['OvFull'] . '</span>';
	if ($rate_hour <= 0) return '—';
	$hours = ($capacity - $stock) / $rate_hour;
	$d = floor($hours / 24); $h = floor($hours) % 24; $m = floor(($hours - floor($hours)) * 60);
	$out = ($d ? $d . 'd ' : '') . ($h ? $h . 'h ' : '') . ($d ? '' : $m . 'm');
	return trim($out);
}

// ---------------------------------------------------------------------------
// Herói e colónia
// ---------------------------------------------------------------------------

// posição na classificação: nº de jogadores com pontuação superior + 1 (o
// administrador, id=0, não conta). Igual ao critério dos highscores.
$db->query("SELECT COUNT(*) c FROM `{$prefix}users` WHERE `id`>0 AND `score` > " . (int)$Player['score']);
$ov_rank = ($row = $db->fetchrow()) ? (int)$row['c'] + 1 : 0;
$db->query("SELECT COUNT(*) c FROM `{$prefix}users` WHERE `id`>0");
$ov_total = ($row = $db->fetchrow()) ? (int)$row['c'] : 0;

tablebegin($Lang['OvColony'] . ': ' . htmlspecialchars($Colony['name']));
?>	<div class="ov-head">
		<dl class="facts">
			<dt><?php echo $Lang['Planet']; ?></dt><dd><a href="galaxy.php?galaxy=<?php echo $Galaxy['name']; ?>&amp;object=<?php echo $Planet['name']; ?>"><?php echo strcap($Planet['name']); ?></a></dd>
			<dt><?php echo $Lang['OvHero']; ?></dt><dd><a href="equipment.php"><?php echo htmlspecialchars($Player['login']); ?></a> <span class="muted">(<?php echo $Lang['Level']; ?> <?php echo (int)$Player['level']; ?>)</span></dd>
			<dt><?php echo $Lang['OvPopulation']; ?></dt><dd class="result"><?php echo div($Colony['colonists'] + $Colony['scientists'] + $Colony['soldiers']); ?></dd>
			<dt><?php echo $Lang['OvScore']; ?></dt><dd class="result"><?php echo div($Player['score']); ?></dd>
<?php if ($ov_rank) { ?>			<dt><?php echo $Lang['OvRank']; ?></dt><dd><a href="highscores.php"><span class="result">#<?php echo div($ov_rank); ?></span><?php if ($ov_total) echo ' <span class="muted">/ ' . div($ov_total) . '</span>'; ?></a></dd>
<?php } ?>		</dl>
	</div>
<?php

// barras de experiência / HP / MP como no cabeçalho
if ($Player['exp4level'] > $Player['expbegin']) {
	$bar = function ($v, $max) { return max(1, min(100, round(num(100 * $v / max(1, $max))))); };
?>	<div class="ov-bars">
		<span class="bar bar-exp" title="EXP"><i style="width: <?php echo $bar($Player['exp'] - $Player['expbegin'], $Player['exp4level'] - $Player['expbegin']); ?>%"></i></span>
		<span class="bar bar-hp" title="HP <?php echo (int)$Player['hp']; ?>/<?php echo (int)$Player['hpmax']; ?>"><i style="width: <?php echo $bar($Player['hp'], $Player['hpmax']); ?>%"></i></span>
		<span class="bar bar-mp" title="MP <?php echo (int)$Player['mp']; ?>/<?php echo (int)$Player['mpmax']; ?>"><i style="width: <?php echo $bar($Player['mp'], $Player['mpmax']); ?>%"></i></span>
	</div>
<?php
}
tableend(anchor('colony.php', $Lang['OvColony']));

// ---------------------------------------------------------------------------
// Recursos: stock, capacidade, por hora e tempo até encher
// ---------------------------------------------------------------------------

tablebegin($Lang['OvProductionHour']);
echo "\t<table class=\"list ov-res\"><thead><tr><th class=\"left\">" . $Lang['OvResource'] . '</th><th>' . $Lang['OvStock'] . '</th><th>' . $Lang['OvCapacity'] . '</th><th>' . $Lang['OvPerHour'] . '</th><th>' . $Lang['OvFullIn'] . "</th></tr></thead><tbody>\n";
foreach (array('energy', 'silicon', 'metal', 'uran', 'plutonium', 'deuterium', 'food', 'crystals') as $r) {
	if (!($Colony[$r] || @$Colony[$r . 'capacity'] || @$Colony[$r . 'plus'])) continue;
	$rate = (@$Colony[$r . 'plus'] - @$Colony[$r . 'minus']) * $per_hour;
	$cap = @$Colony[$r . 'capacity'];
	$over = $cap && $Colony[$r] > $cap;
	echo "\t<tr><td class=\"left\"><img src=\"images/$r.jpg\" alt=\"\" width=\"16\" height=\"16\" /> " . htmlspecialchars($Lang[strcap($r)]) . '</td>'
		. '<td class="' . ($over ? 'minus' : 'value') . '">' . div($Colony[$r]) . '</td>'
		. '<td class="capacity">' . ($cap ? div($cap) : '—') . '</td>'
		. '<td class="' . ($rate < 0 ? 'minus' : ($rate > 0 ? 'plus' : 'muted')) . '">' . ($rate > 0 ? '+' : '') . div(round(num($rate)), 1, $Lang['DecPoint']) . '</td>'
		. '<td class="muted">' . ov_eta_full($Colony[$r], $cap, $rate) . "</td></tr>\n";
}
echo '<tr><td class="left"><img src="images/credits.jpg" alt="" width="16" height="16" /> ' . htmlspecialchars($Lang['Credits']) . '</td><td class="value">' . div($Player['credits']) . '</td><td class="capacity">—</td><td class="muted">—</td><td class="muted">—</td></tr>';
echo "\t</tbody></table>\n";
tableend();

// ---------------------------------------------------------------------------
// Em curso: construção, investigação, produção
// ---------------------------------------------------------------------------

$sd = (int)$stardate;
$progress = array();

if ($Buildings) $progress[] = array('icon' => "gallery/buildings/icons/{$Buildings['name']}.jpg", 'label' => $Lang['OvBuilding'],
	'name' => $Builds[$Buildings['name']]['name'] . ' x' . $Buildings['amount'], 'left' => $Buildings['end'] - $sd, 'link' => 'build.php');
if (@$Research) $progress[] = array('icon' => "gallery/technology/icons/{$Research['name']}.jpg", 'label' => $Lang['OvResearch'],
	'name' => $Technologies[$Research['name']]['name'], 'left' => $Research['end'] - $sd, 'link' => 'research.php');
if ($Productions) foreach ($Productions as $pr) $progress[] = array('icon' => "gallery/units/icons/{$pr['name']}.jpg", 'label' => $Lang['OvProduction'],
	'name' => $ProductionsAvailable[$pr['name']]['name'] . ' x' . div($pr['amount']), 'left' => $pr['end'] - $sd, 'link' => 'production.php');

// itens em fila (estilo OGame), com ETA acumulado a seguir ao item ativo
if (function_exists('readbuildqueue')) {
	$bcum = $Buildings ? max(0, $Buildings['end'] - $sd) : 0;
	foreach (readbuildqueue($login) as $q) {
		$bcum += (int)$q['time'];
		$progress[] = array('icon' => "gallery/buildings/icons/{$q['name']}.jpg", 'label' => $Lang['OvBuilding'] . ' · ' . $Lang['Queued'],
			'name' => (isset($Builds[$q['name']]) ? $Builds[$q['name']]['name'] : strcap($q['name'])) . ' x' . (int)$q['amount'], 'left' => $bcum, 'link' => 'build.php');
	}
	$rcum = @$Research ? max(0, $Research['end'] - $sd) : 0;
	foreach (readresearchqueue($login) as $q) {
		$rcum += (int)$q['time'];
		$progress[] = array('icon' => "gallery/technology/icons/{$q['name']}.jpg", 'label' => $Lang['OvResearch'] . ' · ' . $Lang['Queued'],
			'name' => (isset($Technologies[$q['name']]) ? $Technologies[$q['name']]['name'] : strcap($q['name'])), 'left' => $rcum, 'link' => 'research.php');
	}
}

tablebegin($Lang['OvInProgress']);
if ($progress) {
	echo "\t<ul class=\"queue\">\n";
	foreach ($progress as $q) {
		echo "\t\t<li>" . card_image(array($q['icon']), $q['link'], $q['name'])
			. '<div class="queue-body"><div class="queue-title"><span class="muted">' . htmlspecialchars($q['label']) . '</span> <span class="result">' . htmlspecialchars($q['name']) . '</span></div>'
			. '<div class="queue-meta">' . ($q['left'] > 0 ? '<span class="value" data-countdown="' . ($q['left'] * $thicklength) . '">' . eta($q['left']) . '</span>' : '<span class="plus">' . $Lang['OvDone'] . '</span>') . '</div></div></li>' . "\n";
	}
	echo "\t</ul>\n";
}
else echo "\t<p class=\"muted\">" . htmlspecialchars($Lang['OvNothing']) . "</p>\n";
tableend();

// ---------------------------------------------------------------------------
// Eventos: viagem, expedição, ataques
// ---------------------------------------------------------------------------

$events = array();

if (!empty($Player['destination'])) $events[] = array('class' => '', 'label' => $Lang['OvTravel'],
	'what' => strcap($Player['destination']), 'left' => $Player['time'] - $sd);

if (@$Exploration) $events[] = array('class' => '', 'label' => $Lang['OvExpedition'],
	'what' => strcap($Exploration['target']), 'left' => $Exploration['end'] - $sd);

// ataques a esta colónia e ataques enviados por este jogador
foreach (readattacks('', $Colony['name']) as $a)
	if ($a['login'] != $login) $events[] = array('class' => 'minus', 'label' => $Lang['OvIncoming'],
		'what' => $Lang['OvFrom'] . ' ' . htmlspecialchars($a['login']), 'left' => $a['end'] - $sd);
foreach (readattacks($login) as $a)
	$events[] = array('class' => 'plus', 'label' => $Lang['OvOutgoing'],
		'what' => $Lang['OvTo'] . ' ' . htmlspecialchars($a['target']), 'left' => $a['end'] - $sd);

tablebegin($Lang['OvEvents']);
if ($events) {
	echo "\t<table class=\"list\"><tbody>\n";
	foreach ($events as $e)
		echo "\t<tr><td class=\"left " . $e['class'] . '"><b>' . htmlspecialchars($e['label']) . '</b></td><td class="left">' . $e['what'] . '</td><td>' . ($e['left'] > 0 ? '<span data-countdown="' . ($e['left'] * $thicklength) . '">' . eta($e['left']) . '</span>' : $Lang['OvDone']) . "</td></tr>\n";
	echo "\t</tbody></table>\n";
}
else echo "\t<p class=\"muted\">" . htmlspecialchars($Lang['OvNoEvents']) . "</p>\n";
tableend(htmlspecialchars($Lang['OvStardate']) . ': ' . div($stardate));

require('include/footer.php');
