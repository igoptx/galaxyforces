<?php

// ===========================================================================
// Centro de Operações {ops.php}
// ===========================================================================
// Ferramenta interna de manutenção, gestão e monitorização do jogo, só para
// administradores (grupo wheel). Cada universo tem a sua, porque cada um tem a
// sua base de dados.
//
// Segurança: só wheel; ações só por POST com token ligado à sessão do admin;
// tudo o que se mostra é escapado; cada ação fica no registo de auditoria.

$auth = true;

require('include/common.php');

locale('ops');

$ops_admin = $logged && @$User['usergroup'] == 'wheel';
$view = preg_replace('/[^a-z]/', '', (string)getvar('view'));
if (!$view) $view = 'dashboard';

// ---------------------------------------------------------------------------
// Utilitários
// ---------------------------------------------------------------------------

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function L($key) { global $Lang; return isset($Lang[$key]) ? $Lang[$key] : $key; }

function ops_one($sql)
{
	global $db;
	if (!$db->query($sql) || !($r = $db->fetchrow())) return null;
	return reset($r);
}

function ops_rows($sql)
{
	global $db;
	return $db->query($sql) ? $db->fetchall() : array();
}

function ops_url($view, $params = array())
{
	return 'ops.php?' . http_build_query(array('view' => $view) + $params);
}

// token anti-CSRF: muda a cada login (seed) e a cada dia
function ops_token()
{
	global $User, $login;
	return hash_hmac('sha256', $login . '|' . date('Y-m-d'), (string)@$User['seed'] . 'ops');
}

function ops_form($action, $fields = '', $button = '', $class = '')
{
	$b = $button ? $button : L('OpsApply');
	return '<form method="POST" action="ops.php" class="ops-form ' . $class . '">'
		. '<input type="hidden" name="token" value="' . h(ops_token()) . '" />'
		. '<input type="hidden" name="op" value="' . h($action) . '" />'
		. $fields . '<input type="submit" value="' . h($b) . '" /></form>';
}

function ops_hidden($name, $value) { return '<input type="hidden" name="' . h($name) . '" value="' . h($value) . '" />'; }

// YmdHis -> "Y-m-d H:i"
function ops_ts($ts)
{
	$ts = (string)$ts;
	if (strlen($ts) < 12) return '—';
	return substr($ts, 0, 4) . '-' . substr($ts, 4, 2) . '-' . substr($ts, 6, 2) . ' ' . substr($ts, 8, 2) . ':' . substr($ts, 10, 2);
}

function ops_ago($seconds) { return date('YmdHis', time() - $seconds); }

function ops_bytes($n)
{
	$u = array('B', 'KB', 'MB', 'GB', 'TB'); $i = 0;
	while ($n >= 1024 && $i < 4) { $n /= 1024; $i++; }
	return number_format($n, $i ? 1 : 0, ',', ' ') . ' ' . $u[$i];
}

function ops_num($n) { return number_format((float)$n, 0, ',', ' '); }

function ops_player_exists($name)
{
	global $db, $prefix;
	return (bool)ops_one("SELECT COUNT(*) FROM `{$prefix}users` WHERE `login`='" . $db->safe($name) . "'");
}

// barras simples para séries por dia: array('Y-m-d' => n)
function ops_bars($series, $title)
{
	$max = max(1, max($series ? $series : array(0)));
	$out = '<figure class="ops-chart"><figcaption>' . h($title) . '</figcaption><div class="ops-bars">';
	foreach ($series as $day => $n) {
		$pct = round(100 * $n / $max);
		$out .= '<div class="ops-bar" title="' . h($day . ': ' . $n) . '"><i style="height: ' . max(2, $pct) . '%"></i><span>' . h(substr($day, 8, 2)) . '</span></div>';
	}
	return $out . '</div></figure>';
}

function ops_days($n)
{
	$days = array();
	for ($i = $n - 1; $i >= 0; $i--) $days[date('Y-m-d', strtotime("-$i day"))] = 0;
	return $days;
}

// ---------------------------------------------------------------------------
// Ações (POST -> ação -> redirecionamento)
// ---------------------------------------------------------------------------

$ops_flash = getvar('flash');

if ($ops_admin && $_SERVER['REQUEST_METHOD'] == 'POST') {
	$op = (string)postvar('op');
	$back = array('view' => preg_replace('/[^a-z]/', '', (string)postvar('back', 'dashboard')));
	$player = (string)postvar('player');
	if ($player !== '') $back['name'] = $player;
	$safe_player = $db->safe($player);
	$flash = 'OpsDone';

	if (!hash_equals(ops_token(), (string)postvar('token'))) $flash = 'OpsBadToken';
	elseif ($player !== '' && !ops_player_exists($player)) $flash = 'OpsNotFound';
	else switch ($op) {

		case 'group':
			$group = (string)postvar('group');
			if (!in_array($group, array('', 'wheel', 'moderators', 'jailchief', 'forum'))) { $flash = 'OpsFailed'; break; }
			$db->query("UPDATE `{$prefix}users` SET `usergroup`='" . $db->safe($group) . "' WHERE `login`='$safe_player' LIMIT 1");
			audit('ops', 'group', $player, $group === '' ? '(none)' : $group);
			break;

		case 'credits':
			$amount = (int)postvar('amount');
			$db->query("UPDATE `{$prefix}users` SET `credits`=GREATEST(0, `credits`+($amount)) WHERE `login`='$safe_player' LIMIT 1");
			audit('ops', 'credits', $player, ($amount >= 0 ? '+' : '') . $amount);
			break;

		case 'resources':
			$set = array(); $log = array();
			foreach (array('energy', 'silicon', 'metal', 'uran', 'plutonium', 'deuterium', 'food', 'crystals') as $r) {
				$v = (int)postvar($r);
				if ($v) { $set[] = "`$r`=GREATEST(0, `$r`+($v))"; $log[] = "$r " . ($v > 0 ? '+' : '') . $v; }
			}
			if ($set) $db->query("UPDATE `{$prefix}colonies` SET " . implode(', ', $set) . " WHERE `owner`='$safe_player' LIMIT 1");
			audit('ops', 'resources', $player, implode(', ', $log));
			break;

		case 'lock':
			$hours = max(1, min(24 * 365, (int)postvar('hours')));
			$until = date('YmdHis', time() + 3600 * $hours);
			$db->query("UPDATE `{$prefix}users` SET `locked`='$until', `seed`='' WHERE `login`='$safe_player' LIMIT 1");
			audit('ops', 'lock', $player, "$hours h");
			break;

		case 'unlock':
			$db->query("UPDATE `{$prefix}users` SET `locked`='' WHERE `login`='$safe_player' LIMIT 1");
			audit('ops', 'unlock', $player);
			break;

		case 'ban':
			$days = max(1, min(3650, (int)postvar('days')));
			$until = date('YmdHis', time() + 86400 * $days);
			$db->query("UPDATE `{$prefix}users` SET `banned`='$until' WHERE `login`='$safe_player' LIMIT 1");
			audit('ops', 'ban', $player, "$days d");
			break;

		case 'unban':
			$db->query("UPDATE `{$prefix}users` SET `banned`='' WHERE `login`='$safe_player' LIMIT 1");
			audit('ops', 'unban', $player);
			break;

		case 'move':
			$planet = (string)postvar('planet');
			if (!ops_one("SELECT COUNT(*) FROM `{$prefix}space` WHERE `name`='" . $db->safe($planet) . "'")) { $flash = 'OpsNotFound'; break; }
			$db->query("UPDATE `{$prefix}users` SET `planet`='" . $db->safe($planet) . "', `destination`='', `time`=0 WHERE `login`='$safe_player' LIMIT 1");
			audit('ops', 'move', $player, $planet);
			break;

		case 'password':
			$new = substr(strtr(base64_encode(random_bytes(9)), '+/', 'xy'), 0, 12);
			$db->query("UPDATE `{$prefix}users` SET `password`='" . $db->safe(gf_password_hash($new)) . "', `seed`='' WHERE `login`='$safe_player' LIMIT 1");
			audit('ops', 'password', $player, 'reset');
			// mostrada nesta resposta (sem redirecionar): nunca vai para URLs, logs ou registo
			$ops_newpw = $new;
			break;

		case 'logout':
			$db->query("UPDATE `{$prefix}users` SET `seed`='', `online`='' WHERE `login`='$safe_player' LIMIT 1");
			audit('ops', 'logout', $player);
			break;

		case 'message':
			$subject = strip_tags((string)postvar('subject'));
			$message = strip_tags((string)postvar('message'));
			if ($subject === '' || $message === '') { $flash = 'OpsFailed'; break; }
			sendmessage($subject, $message, $login, $player);   // sendmessage() já escapa
			audit('ops', 'message', $player, $subject);
			break;

		case 'delete':
			$secret = (string)getenv('GALAXY_ADMIN_CONFIRM');
			if ($secret === '' || !hash_equals($secret, (string)postvar('confirm'))) { $flash = 'OpsConfirmFailed'; break; }
			if ($player == $login) { $flash = 'OpsFailed'; break; }
			foreach (array('colonies' => 'owner', 'buildings' => 'login', 'researches' => 'login', 'productions' => 'login', 'exploration' => 'login', 'attacks' => 'login', 'equipment' => 'owner') as $table => $col)
				$db->query("DELETE FROM `{$prefix}$table` WHERE `$col`='$safe_player'");
			$db->query("DELETE FROM `{$prefix}users` WHERE `login`='$safe_player' LIMIT 1");
			audit('ops', 'delete', $player);
			$back = array('view' => 'players');
			break;

		case 'maintenance':
			$on = postvar('mode') == '1' ? '1' : '';
			set_config('MaintenanceMode', $on);
			audit('ops', 'maintenance', $on ? 'on' : 'off');
			break;

		case 'broadcast':
			$text = strip_tags((string)postvar('message'));
			if ($text === '') { $flash = 'OpsFailed'; break; }
			$db->query("INSERT INTO `{$prefix}chat` (`timestamp`, `author`, `message`) VALUES ('" . date('YmdHis') . "', '<font class=\"robot\">system</font>', '" . $db->safe(h($text)) . "')");
			audit('ops', 'broadcast', '', $text);
			break;

		case 'news':
			$text = strip_tags((string)postvar('message'));
			$lang = preg_replace('/[^a-z]/', '', (string)postvar('locale'));
			if ($text === '') { $flash = 'OpsFailed'; break; }
			$db->query("INSERT INTO `{$prefix}news` (`timestamp`, `from`, `locale`, `message`) VALUES ('" . date('YmdHis') . "', '" . $db->safe($login) . "', '" . $db->safe($lang) . "', '" . $db->safe(h($text)) . "')");
			audit('ops', 'news', $lang, mb_substr($text, 0, 80));
			break;

		case 'catchup':
			$owners = ops_rows("SELECT DISTINCT `owner` FROM `{$prefix}colonies` WHERE `thicks` < " . (int)$stardate . " - 1 ORDER BY `thicks` LIMIT 25");
			foreach ($owners as $o) engine(0, $o['owner']);
			audit('ops', 'catchup', '', count($owners) . ' players');
			break;

		case 'prune':
			$days = max(1, (int)postvar('days'));
			$what = (string)postvar('what');
			$cut = date('YmdHis', time() - 86400 * $days);
			if ($what == 'messages') $db->query("DELETE FROM `{$prefix}messages` WHERE `read`=1 AND `timestamp` < '$cut'");
			elseif ($what == 'chat') $db->query("DELETE FROM `{$prefix}chat` WHERE `timestamp` < '$cut'");
			elseif ($what == 'audit') $db->query("DELETE FROM `{$prefix}audit` WHERE `time` < NOW() - INTERVAL $days DAY");
			else { $flash = 'OpsFailed'; break; }
			audit('ops', 'prune', $what, "$days d, " . $db->affected_rows() . ' rows');
			break;

		case 'optimize':
			foreach (ops_rows("SHOW TABLES LIKE '" . $db->safe($prefix) . "%'") as $t) $db->query("OPTIMIZE TABLE `" . reset($t) . "`");
			audit('ops', 'optimize');
			break;

		default:
			$flash = 'OpsFailed';
	}

	if (isset($ops_newpw)) { $view = 'player'; $_GET['name'] = $player; $ops_flash = $flash; }
	else {
		$back['flash'] = $flash;
		header('Location: ' . ops_url($back['view'], array_diff_key($back, array('view' => 1))));
		die;
	}
}

// ---------------------------------------------------------------------------
// Página
// ---------------------------------------------------------------------------

$title = L('OpsTitle');
require('include/header.php');

if (!$ops_admin) {
	tablebegin('<span class="error">' . $Lang['Error'] . '</span>', 400);
	echo '<p>' . $Lang['ErrorAccessDenied'] . '</p>';
	tableend();
	require('include/footer.php');
	exit;
}

$views = array('dashboard' => 'OpsDashboard', 'players' => 'OpsPlayers', 'colonies' => 'OpsColonies', 'activity' => 'OpsActivity',
	'queues' => 'OpsQueues', 'economy' => 'OpsEconomy', 'maintenance' => 'OpsMaintenance', 'system' => 'OpsSystem');
if ($view == 'player') $current = 'players'; else $current = isset($views[$view]) ? $view : 'dashboard';

echo "<nav class=\"ops-tabs\">\n";
foreach ($views as $v => $label) echo "\t<a href=\"" . h(ops_url($v)) . "\"" . ($v == $current ? ' class="active"' : '') . '>' . h(L($label)) . "</a>\n";
echo "</nav>\n";

if (!empty($Config['MaintenanceMode'])) echo '<p class="ops-alert warning">' . h(L('OpsMaintenanceOn')) . "</p>\n";
if ($ops_flash && isset($Lang[$ops_flash])) echo '<p class="ops-alert ' . ($ops_flash == 'OpsDone' ? 'ok' : 'bad') . '">' . h(L($ops_flash)) . "</p>\n";

$online_since = ops_ago(900);

switch ($view) {

// ===========================================================================
// PAINEL
// ===========================================================================

default:
case 'dashboard':
	$kpi = array(
		'OpsPlayersTotal' => ops_one("SELECT COUNT(*) FROM `{$prefix}users`"),
		'OpsActive24' => ops_one("SELECT COUNT(*) FROM `{$prefix}users` WHERE `seen` >= '" . ops_ago(86400) . "'"),
		'OpsOnline' => ops_one("SELECT COUNT(*) FROM `{$prefix}users` WHERE `online` >= '$online_since'"),
		'OpsNew7' => ops_one("SELECT COUNT(*) FROM `{$prefix}users` WHERE `registered` >= CURDATE() - INTERVAL 7 DAY"),
		'OpsColoniesTotal' => ops_one("SELECT COUNT(*) FROM `{$prefix}colonies`"),
		'OpsClans' => ops_one("SELECT COUNT(*) FROM `{$prefix}groups`"),
	);
	$queues = array(
		'OpsBuildings' => ops_one("SELECT COUNT(*) FROM `{$prefix}buildings`"),
		'OpsResearch' => ops_one("SELECT COUNT(*) FROM `{$prefix}researches`"),
		'OpsProductions' => ops_one("SELECT COUNT(*) FROM `{$prefix}productions`"),
		'OpsExpeditions' => ops_one("SELECT COUNT(*) FROM `{$prefix}exploration`"),
		'OpsAttacks' => ops_one("SELECT COUNT(*) FROM `{$prefix}attacks`"),
	);
	$lagging = ops_one("SELECT COUNT(*) FROM `{$prefix}colonies` WHERE `thicks` < " . (int)$stardate . " - 12");
	$maxlag = (int)ops_one("SELECT " . (int)$stardate . " - MIN(`thicks`) FROM `{$prefix}colonies`");
	$failed = ops_one("SELECT COUNT(*) FROM `{$prefix}audit` WHERE `action`='login_failed' AND `time` >= NOW() - INTERVAL 1 DAY");
	$messages = ops_one("SELECT COUNT(*) FROM `{$prefix}messages` WHERE `timestamp` >= '" . ops_ago(86400) . "'");

	tablebegin(L('OpsTitle') . ' &middot; ' . h(isset($nova_title[1]) ? $nova_title[1] : $Config['Title']));
	echo "\t<div class=\"ops-kpis\">\n";
	foreach ($kpi as $k => $v) echo "\t\t<div class=\"ops-kpi\"><b>" . ops_num($v) . '</b><span>' . h(L($k)) . "</span></div>\n";
	echo "\t</div>\n";
	tablebreak();
	echo "\t<div class=\"ops-kpis ops-kpis-small\">\n";
	foreach ($queues as $k => $v) echo "\t\t<a class=\"ops-kpi\" href=\"" . h(ops_url('queues')) . '"><b>' . ops_num($v) . '</b><span>' . h(L($k)) . "</span></a>\n";
	echo "\t\t<div class=\"ops-kpi\"><b>" . ops_num($messages) . '</b><span>' . h(L('OpsMessages24')) . "</span></div>\n";
	echo "\t\t<a class=\"ops-kpi" . ($failed > 20 ? ' bad' : '') . '" href="' . h(ops_url('activity', array('category' => 'auth'))) . '"><b>' . ops_num($failed) . '</b><span>' . h(L('OpsFailedLogins')) . "</span></a>\n";
	echo "\t\t<div class=\"ops-kpi" . ($lagging ? ' warn' : '') . '"><b>' . ops_num($lagging) . '</b><span>' . h(L('OpsEngineLag')) . ' &middot; ' . h(L('OpsMaxLag')) . ': ' . ops_num($maxlag) . "</span></div>\n";
	echo "\t</div>\n";
	tablebreak();

	$reg = ops_days(14);
	foreach (ops_rows("SELECT `registered` d, COUNT(*) n FROM `{$prefix}users` WHERE `registered` >= CURDATE() - INTERVAL 13 DAY GROUP BY d") as $r) if (isset($reg[$r['d']])) $reg[$r['d']] = (int)$r['n'];
	$logins = ops_days(14);
	foreach (ops_rows("SELECT DATE(`time`) d, COUNT(*) n FROM `{$prefix}audit` WHERE `action`='login' AND `time` >= CURDATE() - INTERVAL 13 DAY GROUP BY d") as $r) if (isset($logins[$r['d']])) $logins[$r['d']] = (int)$r['n'];
	echo "\t<div class=\"ops-charts\">" . ops_bars($reg, L('OpsRegistrations')) . ops_bars($logins, L('OpsLogins')) . "</div>\n";
	tableend(h(L('OpsStardate')) . ': ' . ops_num($stardate) . ' &middot; ' . h(L('OpsTick')) . ': ' . (int)$thicklength . ' ' . L('OpsSeconds'));

	echo "<div class=\"ops-columns ops-columns-wide\">\n";
	tablebegin(L('OpsRecent'));
	ops_audit_table(ops_rows("SELECT * FROM `{$prefix}audit` ORDER BY `id` DESC LIMIT 15"));
	tableend('<a href="' . h(ops_url('activity')) . '">' . h(L('OpsActivity')) . ' &gt;&gt;</a>');

	tablebegin(L('OpsTopPlayers'));
	$rows = ops_rows("SELECT `login`, `level`, `score` FROM `{$prefix}users` ORDER BY `score` DESC LIMIT 10");
	echo "\t<table class=\"list\"><thead><tr><th class=\"left\">" . h(L('OpsName')) . '</th><th>' . h(L('OpsLevel')) . '</th><th>' . h(L('OpsScore')) . "</th></tr></thead><tbody>\n";
	foreach ($rows as $r) echo "\t<tr><td class=\"left\"><a href=\"" . h(ops_url('player', array('name' => $r['login']))) . '">' . h($r['login']) . '</a></td><td>' . (int)$r['level'] . '</td><td>' . ops_num($r['score']) . "</td></tr>\n";
	echo "\t</tbody></table>\n";
	tableend();
	echo "</div>\n";
	break;

// ===========================================================================
// JOGADORES
// ===========================================================================

case 'players':
	$q = (string)getvar('q');
	$group = (string)getvar('group');
	$sorts = array('score' => '`score` DESC', 'level' => '`level` DESC', 'credits' => '`credits` DESC', 'registered' => '`registered` DESC', 'seen' => '`seen` DESC', 'login' => '`login`');
	$sort = isset($sorts[getvar('sort')]) ? getvar('sort') : 'seen';
	$page = max(0, (int)getvar('page'));
	$where = array('1');
	if ($q !== '') { $s = $db->safe(addcslashes($q, '%_')); $where[] = "(u.`login` LIKE '%$s%' OR u.`email` LIKE '%$s%' OR u.`ip` LIKE '%$s%' OR u.`lastip` LIKE '%$s%')"; }
	if ($group !== '') $where[] = "u.`usergroup`='" . $db->safe($group == '-' ? '' : $group) . "'";
	$w = implode(' AND ', $where);
	$total = (int)ops_one("SELECT COUNT(*) FROM `{$prefix}users` u WHERE $w");
	$rows = ops_rows("SELECT u.`login`, u.`usergroup`, u.`level`, u.`score`, u.`credits`, u.`planet`, u.`destination`, u.`seen`, u.`online`, u.`locked`, u.`banned`, u.`registered`, c.`name` colony
		FROM `{$prefix}users` u LEFT JOIN `{$prefix}colonies` c ON c.`owner`=u.`login` WHERE $w ORDER BY u.{$sorts[$sort]} LIMIT " . ($page * 50) . ", 50");

	tablebegin(L('OpsPlayers') . ' (' . ops_num($total) . ')');
	echo "\t<form method=\"GET\" action=\"ops.php\" class=\"ops-filter\">" . ops_hidden('view', 'players')
		. '<input type="text" name="q" value="' . h($q) . '" placeholder="' . h(L('OpsSearch')) . ': login, e-mail, IP" />'
		. '<select name="group"><option value="">' . h(L('OpsAll')) . '</option>';
	foreach (array('-' => '—', 'wheel' => 'wheel', 'moderators' => 'moderators', 'jailchief' => 'jailchief', 'forum' => 'forum') as $k => $v) echo '<option value="' . h($k) . '"' . ($group == $k ? ' selected' : '') . '>' . h($v) . '</option>';
	echo '</select><select name="sort">';
	foreach (array_keys($sorts) as $k) echo '<option value="' . $k . '"' . ($sort == $k ? ' selected' : '') . '>' . h($k) . '</option>';
	echo '</select><input type="submit" value="' . h(L('OpsFilter')) . "\" /></form>\n";

	echo "\t<table class=\"list\"><thead><tr><th class=\"left\">" . h(L('OpsName')) . '</th><th>' . h(L('OpsGroup')) . '</th><th>' . h(L('OpsLevel')) . '</th><th>' . h(L('OpsScore')) . '</th><th>' . h(L('OpsCredits')) . '</th><th>' . h(L('OpsColony')) . '</th><th>' . h(L('OpsPlanet')) . '</th><th>' . h(L('OpsLastSeen')) . '</th><th>' . h(L('OpsStatus')) . "</th></tr></thead><tbody>\n";
	foreach ($rows as $r) echo "\t<tr><td class=\"left\"><a href=\"" . h(ops_url('player', array('name' => $r['login']))) . '">' . h($r['login']) . '</a></td><td>' . h($r['usergroup']) . '</td><td>' . (int)$r['level'] . '</td><td>' . ops_num($r['score']) . '</td><td>' . ops_num($r['credits']) . '</td><td>' . h($r['colony']) . '</td><td>' . h(strcap($r['planet'])) . '</td><td>' . ops_ts($r['seen']) . '</td><td>' . ops_status($r) . "</td></tr>\n";
	if (!$rows) echo "\t<tr><td colspan=\"9\">" . h(L('OpsNoData')) . "</td></tr>\n";
	echo "\t</tbody></table>\n";
	tableend(ops_pager('players', $page, $total, 50, array('q' => $q, 'group' => $group, 'sort' => $sort)));
	break;

// ===========================================================================
// JOGADOR
// ===========================================================================

case 'player':
	$name = (string)getvar('name');
	$safe = $db->safe($name);
	$u = ops_rows("SELECT * FROM `{$prefix}users` WHERE `login`='$safe' LIMIT 1");
	if (!$u) { tablebegin(L('OpsPlayers'), 400); echo '<p>' . h(L('OpsNotFound')) . '</p>'; tableend(); break; }
	$u = $u[0];
	$c = ops_rows("SELECT * FROM `{$prefix}colonies` WHERE `owner`='$safe' LIMIT 1");
	$c = $c ? $c[0] : null;

	if (!empty($ops_newpw) && ($pw = $ops_newpw)) echo '<p class="ops-alert ok">' . h(L('OpsNewPassword')) . ': <code>' . h($pw) . "</code></p>\n";

	tablebegin(h($u['login']));
	$facts = array(
		'OpsGroup' => $u['usergroup'] ? $u['usergroup'] : '—', 'OpsStatus' => ops_status($u, false), 'OpsLevel' => (int)$u['level'], 'OpsScore' => ops_num($u['score']),
		'OpsCredits' => ops_num($u['credits']), 'OpsBank' => ops_num($u['bank']), 'OpsReputation' => h($u['reputation']), 'OpsPlanet' => h(strcap($u['planet'])) . ($u['destination'] ? ' &rarr; ' . h(strcap($u['destination'])) : ''),
		'OpsEmail' => h($u['email']), 'OpsLanguage' => h($u['language']), 'OpsRegistered' => h($u['registered']), 'OpsLastSeen' => ops_ts($u['seen']),
		'OpsIP' => h($u['ip']), 'OpsLastIP' => h($u['lastip']), 'OpsEquipment' => ops_num(ops_one("SELECT COUNT(*) FROM `{$prefix}equipment` WHERE `owner`='$safe'")),
	);
	echo "\t<dl class=\"facts\">\n";
	foreach ($facts as $k => $v) echo "\t\t<dt>" . h(L($k)) . "</dt><dd>$v</dd>\n";
	echo "\t</dl>\n";
	if ($c) {
		tablebreak();
		echo "\t<h3>" . h(L('OpsColony')) . ': ' . h($c['name']) . "</h3>\n\t<ul class=\"costs\">";
		foreach (array('energy', 'silicon', 'metal', 'uran', 'plutonium', 'deuterium', 'food', 'crystals') as $r)
			if ($c[$r]) echo '<li class="cost"><img src="images/' . $r . '.jpg" alt="" width="16" height="16" /><span>' . ops_num($c[$r]) . '</span></li>';
		echo '<li class="cost"><b class="chip-label">' . h(L('OpsPopulation')) . '</b><span>' . ops_num($c['colonists'] + $c['scientists'] + $c['soldiers']) . '</span></li>';
		echo '<li class="cost"><b class="chip-label">' . h(L('OpsTicksBehind')) . '</b><span>' . ops_num(max(0, $stardate - $c['thicks'])) . "</span></li></ul>\n";
	}
	tableend('<a href="whois.php?name=' . h(urlencode($u['login'])) . '">whois &gt;&gt;</a>');

	// ações
	$p = ops_hidden('player', $u['login']) . ops_hidden('back', 'player');
	tablebegin(L('OpsAction'));
	echo "\t<div class=\"ops-actions\">\n";
	$groups = '';
	foreach (array('' => '—', 'wheel' => 'wheel', 'moderators' => 'moderators', 'jailchief' => 'jailchief', 'forum' => 'forum') as $k => $v) $groups .= '<option value="' . h($k) . '"' . ($u['usergroup'] == $k ? ' selected' : '') . '>' . h($v) . '</option>';
	echo "\t\t<div><h4>" . h(L('OpsChangeGroup')) . '</h4>' . ops_form('group', $p . '<select name="group">' . $groups . '</select>') . "</div>\n";
	echo "\t\t<div><h4>" . h(L('OpsAdjustCredits')) . '</h4>' . ops_form('credits', $p . '<input type="number" name="amount" value="0" step="1" />') . "</div>\n";
	if ($c) {
		$res = '';
		foreach (array('energy', 'silicon', 'metal', 'uran', 'plutonium', 'deuterium', 'food', 'crystals') as $r) $res .= '<label><img src="images/' . $r . '.jpg" alt="' . $r . '" title="' . $r . '" width="16" height="16" /><input type="number" name="' . $r . '" value="0" step="1" /></label>';
		echo "\t\t<div class=\"wide\"><h4>" . h(L('OpsAdjustResources')) . '</h4>' . ops_form('resources', $p . '<span class="ops-res">' . $res . '</span>') . "</div>\n";
	}
	echo "\t\t<div><h4>" . h(L('OpsLock')) . '</h4>' . ops_form('lock', $p . '<input type="number" name="hours" value="24" min="1" /> ' . h(L('OpsLockHours'))) . ($u['locked'] ? ops_form('unlock', $p, L('OpsUnlock')) : '') . "</div>\n";
	echo "\t\t<div><h4>" . h(L('OpsBan')) . '</h4>' . ops_form('ban', $p . '<input type="number" name="days" value="7" min="1" /> ' . h(L('OpsBanDays'))) . ($u['banned'] ? ops_form('unban', $p, L('OpsUnban')) : '') . "</div>\n";
	$planets = '';
	foreach (ops_rows("SELECT `name`, `galaxy` FROM `{$prefix}space` ORDER BY `galaxy`, `name`") as $s) $planets .= '<option value="' . h($s['name']) . '"' . ($s['name'] == $u['planet'] ? ' selected' : '') . '>' . h(strcap($s['name']) . ' (' . $s['galaxy'] . ')') . '</option>';
	echo "\t\t<div><h4>" . h(L('OpsMove')) . '</h4>' . ops_form('move', $p . '<select name="planet">' . $planets . '</select>') . "</div>\n";
	echo "\t\t<div><h4>" . h(L('OpsResetPassword')) . ' / ' . h(L('OpsForceLogout')) . '</h4>' . ops_form('password', $p, L('OpsResetPassword')) . ops_form('logout', $p, L('OpsForceLogout')) . "</div>\n";
	echo "\t\t<div class=\"wide\"><h4>" . h(L('OpsSendMessage')) . '</h4>' . ops_form('message', $p . '<input type="text" name="subject" placeholder="' . h(L('OpsSubject')) . '" /><textarea name="message" rows="2" placeholder="' . h(L('OpsMessage')) . '"></textarea>', L('OpsSend'), 'stack') . "</div>\n";
	if ($u['login'] != $login) echo "\t\t<div class=\"wide danger\"><h4>" . h(L('OpsDeleteAccount')) . '</h4>' . ops_form('delete', $p . '<input type="password" name="confirm" placeholder="' . h(L('OpsConfirmPassword')) . '" autocomplete="off" />', L('OpsDeleteAccount')) . "</div>\n";
	echo "\t</div>\n";
	tableend();

	tablebegin(L('OpsHistory'));
	ops_audit_table(ops_rows("SELECT * FROM `{$prefix}audit` WHERE `actor`='$safe' OR `target`='$safe' ORDER BY `id` DESC LIMIT 30"));
	tableend();
	break;

// ===========================================================================
// COLÓNIAS
// ===========================================================================

case 'colonies':
	$q = (string)getvar('q');
	$page = max(0, (int)getvar('page'));
	$w = $q !== '' ? "WHERE `name` LIKE '%" . $db->safe(addcslashes($q, '%_')) . "%' OR `owner` LIKE '%" . $db->safe(addcslashes($q, '%_')) . "%' OR `planet` LIKE '%" . $db->safe(addcslashes($q, '%_')) . "%'" : '';
	$total = (int)ops_one("SELECT COUNT(*) FROM `{$prefix}colonies` $w");
	$rows = ops_rows("SELECT * FROM `{$prefix}colonies` $w ORDER BY `thicks`, `name` LIMIT " . ($page * 50) . ", 50");
	tablebegin(L('OpsColonies') . ' (' . ops_num($total) . ')');
	echo "\t<form method=\"GET\" action=\"ops.php\" class=\"ops-filter\">" . ops_hidden('view', 'colonies') . '<input type="text" name="q" value="' . h($q) . '" placeholder="' . h(L('OpsSearch')) . '" /><input type="submit" value="' . h(L('OpsFilter')) . "\" /></form>\n";
	echo "\t<table class=\"list\"><thead><tr><th class=\"left\">" . h(L('OpsName')) . '</th><th>' . h(L('OpsOwner')) . '</th><th>' . h(L('OpsPlanet')) . '</th><th>' . h(L('OpsPopulation')) . '</th><th><img src="images/energy.jpg" alt="E" width="14" height="14" /></th><th><img src="images/metal.jpg" alt="M" width="14" height="14" /></th><th><img src="images/food.jpg" alt="F" width="14" height="14" /></th><th><img src="images/crystals.jpg" alt="C" width="14" height="14" /></th><th>' . h(L('OpsDamage')) . '</th><th>' . h(L('OpsTicksBehind')) . "</th></tr></thead><tbody>\n";
	foreach ($rows as $r) {
		$lag = max(0, $stardate - $r['thicks']);
		echo "\t<tr><td class=\"left capacity\">" . h($r['name']) . '</td><td><a href="' . h(ops_url('player', array('name' => $r['owner']))) . '">' . h($r['owner']) . '</a></td><td>' . h(strcap($r['planet'])) . '</td><td>' . ops_num($r['colonists'] + $r['scientists'] + $r['soldiers']) . '</td><td>' . ops_num($r['energy']) . '</td><td>' . ops_num($r['metal']) . '</td><td>' . ops_num($r['food']) . '</td><td>' . ops_num($r['crystals']) . '</td><td>' . round($r['damage'], 2) . '%</td><td class="' . ($lag > 12 ? 'minus' : 'plus') . '">' . ops_num($lag) . "</td></tr>\n";
	}
	if (!$rows) echo "\t<tr><td colspan=\"10\">" . h(L('OpsNoData')) . "</td></tr>\n";
	echo "\t</tbody></table>\n";
	tableend(ops_pager('colonies', $page, $total, 50, array('q' => $q)));
	break;

// ===========================================================================
// ATIVIDADE
// ===========================================================================

case 'activity':
	$category = preg_replace('/[^a-z]/', '', (string)getvar('category'));
	$q = (string)getvar('q');
	$page = max(0, (int)getvar('page'));
	$where = array('1');
	if ($category) $where[] = "`category`='$category'";
	if ($q !== '') { $s = $db->safe(addcslashes($q, '%_')); $where[] = "(`actor` LIKE '%$s%' OR `target` LIKE '%$s%' OR `ip` LIKE '%$s%' OR `details` LIKE '%$s%')"; }
	$w = implode(' AND ', $where);
	$total = (int)ops_one("SELECT COUNT(*) FROM `{$prefix}audit` WHERE $w");
	tablebegin(L('OpsActivity') . ' (' . ops_num($total) . ')');
	echo "\t<form method=\"GET\" action=\"ops.php\" class=\"ops-filter\">" . ops_hidden('view', 'activity') . '<select name="category"><option value="">' . h(L('OpsAll')) . '</option>';
	foreach (ops_rows("SELECT DISTINCT `category` FROM `{$prefix}audit` ORDER BY `category`") as $r) echo '<option value="' . h($r['category']) . '"' . ($category == $r['category'] ? ' selected' : '') . '>' . h($r['category']) . '</option>';
	echo '</select><input type="text" name="q" value="' . h($q) . '" placeholder="' . h(L('OpsSearch')) . '" /><input type="submit" value="' . h(L('OpsFilter')) . "\" /></form>\n";
	ops_audit_table(ops_rows("SELECT * FROM `{$prefix}audit` WHERE $w ORDER BY `id` DESC LIMIT " . ($page * 100) . ", 100"));
	tableend(ops_pager('activity', $page, $total, 100, array('category' => $category, 'q' => $q)));
	break;

// ===========================================================================
// FILAS
// ===========================================================================

case 'queues':
	$sd = (int)$stardate;
	$sections = array(
		'OpsBuildings' => "SELECT `login` who, `name` what, `amount`, `begin`+`time`-$sd lefts FROM `{$prefix}buildings` ORDER BY lefts",
		'OpsResearch' => "SELECT `login` who, `name` what, 1 amount, `begin`+`time`-$sd lefts FROM `{$prefix}researches` ORDER BY lefts",
		'OpsProductions' => "SELECT `login` who, `name` what, `amount`, `begin`+`time`-$sd lefts FROM `{$prefix}productions` ORDER BY lefts",
		'OpsExpeditions' => "SELECT `login` who, CONCAT(`type`, ': ', `target`) what, `colonists`+`scientists`+`soldiers` amount, `begin`+`time`-$sd lefts FROM `{$prefix}exploration` ORDER BY lefts",
		'OpsAttacks' => "SELECT `login` who, CONCAT('&rarr; ', `target`, ' (', `owner`, ')') what, `status` amount, `begin`+`time`-$sd lefts FROM `{$prefix}attacks` ORDER BY lefts",
	);
	foreach ($sections as $label => $sql) {
		$rows = ops_rows($sql . ' LIMIT 100');
		tablebegin(L($label) . ' (' . count($rows) . ')');
		if ($rows) {
			echo "\t<table class=\"list\"><thead><tr><th class=\"left\">" . h(L('OpsActor')) . '</th><th class="left">' . h(L('OpsType')) . '</th><th>' . h(L('OpsAmount')) . '</th><th>' . h(L('OpsETA')) . "</th></tr></thead><tbody>\n";
			foreach ($rows as $r) echo "\t<tr><td class=\"left\"><a href=\"" . h(ops_url('player', array('name' => $r['who']))) . '">' . h($r['who']) . '</a></td><td class="left">' . str_replace('&amp;rarr;', '&rarr;', h($r['what'])) . '</td><td>' . ops_num($r['amount']) . '</td><td class="' . ($r['lefts'] < 0 ? 'minus' : 'plus') . '">' . ($r['lefts'] < 0 ? h(L('OpsTicksBehind')) . ': ' . ops_num(-$r['lefts']) : eta($r['lefts'])) . "</td></tr>\n";
			echo "\t</tbody></table>\n";
		}
		else echo "\t<p class=\"muted\">" . h(L('OpsNoData')) . "</p>\n";
		tableend();
	}
	break;

// ===========================================================================
// ECONOMIA
// ===========================================================================

case 'economy':
	$tot = ops_rows("SELECT SUM(`energy`) energy, SUM(`silicon`) silicon, SUM(`metal`) metal, SUM(`uran`) uran, SUM(`plutonium`) plutonium, SUM(`deuterium`) deuterium, SUM(`food`) food, SUM(`crystals`) crystals, SUM(`colonists`+`scientists`+`soldiers`) population FROM `{$prefix}colonies`");
	$money = ops_rows("SELECT SUM(`credits`) credits, SUM(`bank`) bank FROM `{$prefix}users`");
	tablebegin(L('OpsTotals'));
	echo "\t<ul class=\"costs ops-totals\">";
	echo '<li class="cost"><img src="images/credits.jpg" alt="" width="16" height="16" /><b class="chip-label">' . h(L('OpsCredits')) . '</b><span>' . ops_num($money[0]['credits']) . '</span></li>';
	echo '<li class="cost"><b class="chip-label">' . h(L('OpsBank')) . '</b><span>' . ops_num($money[0]['bank']) . '</span></li>';
	foreach (array('energy', 'silicon', 'metal', 'uran', 'plutonium', 'deuterium', 'food', 'crystals') as $r) echo '<li class="cost"><img src="images/' . $r . '.jpg" alt="" width="16" height="16" /><b class="chip-label">' . h($Lang[strcap($r)]) . '</b><span>' . ops_num($tot[0][$r]) . '</span></li>';
	echo '<li class="cost"><b class="chip-label">' . h(L('OpsPopulation')) . '</b><span>' . ops_num($tot[0]['population']) . "</span></li></ul>\n";
	tableend();

	echo "<div class=\"ops-columns\">\n";
	tablebegin(L('OpsRichest'));
	echo "\t<table class=\"list\"><thead><tr><th class=\"left\">" . h(L('OpsName')) . '</th><th>' . h(L('OpsCredits')) . '</th><th>' . h(L('OpsBank')) . "</th></tr></thead><tbody>\n";
	foreach (ops_rows("SELECT `login`, `credits`, `bank` FROM `{$prefix}users` ORDER BY `credits`+`bank` DESC LIMIT 15") as $r) echo "\t<tr><td class=\"left\"><a href=\"" . h(ops_url('player', array('name' => $r['login']))) . '">' . h($r['login']) . '</a></td><td>' . ops_num($r['credits']) . '</td><td>' . ops_num($r['bank']) . "</td></tr>\n";
	echo "\t</tbody></table>\n";
	tableend();

	tablebegin(L('OpsMarkets'));
	$markets = ops_rows("SELECT * FROM `{$prefix}markets` LIMIT 20");
	if ($markets) {
		echo "\t<table class=\"list\"><thead><tr><th class=\"left\">" . h(L('OpsPlanet')) . '</th>';
		$res = array('energy', 'silicon', 'metal', 'uran', 'food', 'crystals');
		foreach ($res as $r) echo '<th><img src="images/' . $r . '.jpg" alt="' . $r . '" width="14" height="14" /></th>';
		echo "</tr></thead><tbody>\n";
		foreach ($markets as $m) {
			echo "\t<tr><td class=\"left\">" . h(strcap($m['position'])) . '</td>';
			foreach ($res as $r) echo '<td title="' . h(L('OpsBuy') . ' / ' . L('OpsSell')) . '"><span class="plus">' . h(round($m[$r . 'buyaverage'], 2)) . '</span> / <span class="minus">' . h(round($m[$r . 'sellaverage'], 2)) . '</span></td>';
			echo "</tr>\n";
		}
		echo "\t</tbody></table>\n";
	}
	else echo "\t<p class=\"muted\">" . h(L('OpsNoData')) . "</p>\n";
	tableend();
	echo "</div>\n";
	break;

// ===========================================================================
// MANUTENÇÃO
// ===========================================================================

case 'maintenance':
	$b = ops_hidden('back', 'maintenance');
	$on = !empty($Config['MaintenanceMode']);
	tablebegin(L('OpsMaintenanceMode'));
	echo "\t<p>" . h($on ? L('OpsMaintenanceOn') : L('OpsMaintenanceOff')) . "</p>\n";
	echo ops_form('maintenance', $b . ops_hidden('mode', $on ? '0' : '1'), $on ? L('OpsDisable') : L('OpsEnable'), $on ? '' : 'danger');
	tableend();

	echo "<div class=\"ops-columns\">\n";
	tablebegin(L('OpsBroadcast'));
	echo ops_form('broadcast', $b . '<textarea name="message" rows="2" maxlength="250"></textarea>', L('OpsSend'), 'stack');
	tableend();
	tablebegin(L('OpsNews'));
	$langs = '<option value="">' . h(L('OpsAll')) . '</option>';
	foreach (locales() as $code => $label) $langs .= '<option value="' . h($code) . '">' . h($label) . '</option>';
	echo ops_form('news', $b . '<select name="locale">' . $langs . '</select><textarea name="message" rows="3"></textarea>', L('OpsSend'), 'stack');
	tableend();
	echo "</div>\n";

	echo "<div class=\"ops-columns\">\n";
	tablebegin(L('OpsEngineCatchUp'));
	echo "\t<p class=\"muted\">" . h(L('OpsEngineCatchUpHelp')) . "</p>\n" . ops_form('catchup', $b, L('OpsRun'));
	tableend();
	tablebegin(L('OpsPrune'));
	foreach (array('messages' => array('OpsPruneMessages', 30), 'chat' => array('OpsPruneChat', 30), 'audit' => array('OpsPruneAudit', 180)) as $what => $cfg)
		echo ops_form('prune', $b . ops_hidden('what', $what) . h(L($cfg[0])) . ' <input type="number" name="days" value="' . $cfg[1] . '" min="1" /> ' . h(L('OpsDays')), L('OpsRun'));
	echo ops_form('optimize', $b, L('OpsOptimize'));
	tableend();
	echo "</div>\n";
	break;

// ===========================================================================
// SISTEMA
// ===========================================================================

case 'system':
	$dbname = ops_one("SELECT DATABASE()");
	$tables = ops_rows("SELECT `table_name` n, `table_rows` r, `data_length`+`index_length` s FROM information_schema.tables WHERE `table_schema`=DATABASE() ORDER BY s DESC");
	$dbsize = 0; foreach ($tables as $t) $dbsize += $t['s'];

	echo "<div class=\"ops-columns\">\n";
	tablebegin(L('OpsSystem'));
	$facts = array(
		'OpsUniverse' => h($Config['Title']), 'OpsPHP' => h(PHP_VERSION . ' (' . PHP_SAPI . ')'),
		'OpsDatabase' => h('MySQL ' . ops_one("SELECT VERSION()") . " · $dbname · " . ops_bytes($dbsize)),
		'sql_mode' => h(ops_one("SELECT @@sql_mode") ?: '—'), 'charset' => h(ops_one("SELECT @@character_set_database") . ' / ' . ops_one("SELECT @@collation_database")),
		'OpsStardate' => ops_num($stardate), 'OpsTick' => (int)$thicklength . ' ' . L('OpsSeconds'),
		'OpsDisk' => ops_bytes((float)@disk_free_space('.')), 'memory_limit' => h(ini_get('memory_limit')), 'max_execution_time' => h(ini_get('max_execution_time')),
	);
	echo "\t<dl class=\"facts\">\n";
	foreach ($facts as $k => $v) echo "\t\t<dt>" . h(L($k)) . "</dt><dd>$v</dd>\n";
	echo "\t</dl>\n";
	tablebreak();
	echo "\t<p class=\"muted small\">" . h(L('OpsExtensions')) . ': ' . h(implode(', ', get_loaded_extensions())) . "</p>\n";
	tableend();

	tablebegin(L('OpsConfig'));
	$cfg = array('Style' => @$Config['Style'], 'DefaultLanguage' => @$Config['DefaultLanguage'], 'Registration' => @$Config['Registration'], 'AuthType' => @$Config['AuthType'],
		'Debug' => $Config['Debug'] ? 'on' : 'off', 'Logging' => @$Config['Logging'] ? 'on' : 'off', 'MessageLife' => @$Config['MessageLife'], 'prefix' => $prefix,
		'MaintenanceMode' => !empty($Config['MaintenanceMode']) ? 'on' : 'off', 'AdminConfirm' => getenv('GALAXY_ADMIN_CONFIRM') ? 'set' : 'empty');
	echo "\t<dl class=\"facts\">\n";
	foreach ($cfg as $k => $v) echo "\t\t<dt>" . h($k) . '</dt><dd>' . h($v) . "</dd>\n";
	echo "\t</dl>\n";
	tableend();
	echo "</div>\n";

	tablebegin(L('OpsTables') . ' (' . count($tables) . ')');
	echo "\t<table class=\"list\"><thead><tr><th class=\"left\">" . h(L('OpsName')) . '</th><th>' . h(L('OpsRows')) . '</th><th>' . h(L('OpsSize')) . "</th></tr></thead><tbody>\n";
	foreach ($tables as $t) echo "\t<tr><td class=\"left\">" . h($t['n']) . '</td><td>' . ops_num($t['r']) . '</td><td>' . ops_bytes($t['s']) . "</td></tr>\n";
	echo "\t</tbody></table>\n";
	tableend();

	tablebegin(L('OpsLogs'));
	$logdir = $ROOT . $Config['LogPath'];
	foreach (glob($logdir . '*.log') as $f) {
		$lines = @file($f, FILE_IGNORE_NEW_LINES);
		echo "\t<h4>" . h(basename($f)) . ' <span class="muted">(' . ops_bytes(filesize($f)) . ")</span></h4>\n";
		echo "\t<pre class=\"ops-log\">" . h(implode("\n", array_slice($lines ? $lines : array(), -25))) . "</pre>\n";
	}
	tableend();
	break;
}

// ---------------------------------------------------------------------------
// Blocos partilhados
// ---------------------------------------------------------------------------

function ops_audit_table($rows)
{
	if (!$rows) { echo "\t<p class=\"muted\">" . h(L('OpsNoData')) . "</p>\n"; return; }
	echo "\t<table class=\"list\"><thead><tr><th class=\"left\">" . h(L('OpsTime')) . '</th><th>' . h(L('OpsActor')) . '</th><th>' . h(L('OpsCategory')) . '</th><th>' . h(L('OpsAction')) . '</th><th>' . h(L('OpsTarget')) . '</th><th class="left">' . h(L('OpsDetails')) . '</th><th>' . h(L('OpsIP')) . "</th></tr></thead><tbody>\n";
	foreach ($rows as $r) {
		$bad = in_array($r['action'], array('login_failed', 'delete', 'ban', 'lock'));
		echo "\t<tr" . ($bad ? ' class="alert"' : '') . '><td class="left nowrap">' . h(substr($r['time'], 5, 11)) . '</td><td>' . ($r['actor'] !== '' ? '<a href="' . h(ops_url('player', array('name' => $r['actor']))) . '">' . h($r['actor']) . '</a>' : '—') . '</td><td><span class="tag tag-' . h($r['category']) . '">' . h($r['category']) . '</span></td><td>' . h($r['action']) . '</td><td>' . h($r['target']) . '</td><td class="left">' . h($r['details']) . '</td><td class="muted">' . h($r['ip']) . "</td></tr>\n";
	}
	echo "\t</tbody></table>\n";
}

function ops_status($u, $short = true)
{
	global $online_since;
	$now = date('YmdHis');
	$out = array();
	if ($u['banned'] > $now) $out[] = '<span class="tag tag-bad">' . h(L('OpsBanned')) . ($short ? '' : ' → ' . ops_ts($u['banned'])) . '</span>';
	if ($u['locked'] > $now) $out[] = '<span class="tag tag-warn">' . h(L('OpsLocked')) . ($short ? '' : ' → ' . ops_ts($u['locked'])) . '</span>';
	if ($u['online'] >= $online_since) $out[] = '<span class="tag tag-ok">' . h(L('OpsOnlineNow')) . '</span>';
	if (!empty($u['destination'])) $out[] = '<span class="tag">' . h(L('OpsTravelling')) . '</span>';
	return $out ? implode(' ', $out) : '<span class="muted">—</span>';
}

function ops_pager($view, $page, $total, $per, $params)
{
	$pages = max(1, (int)ceil($total / $per));
	$out = h(L('OpsPage')) . ' ' . ($page + 1) . ' / ' . $pages;
	if ($page > 0) $out = '<a href="' . h(ops_url($view, $params + array('page' => $page - 1))) . '">&lt;&lt; ' . h(L('OpsPrevious')) . '</a> &nbsp; ' . $out;
	if ($page + 1 < $pages) $out .= ' &nbsp; <a href="' . h(ops_url($view, $params + array('page' => $page + 1))) . '">' . h(L('OpsNext')) . ' &gt;&gt;</a>';
	return $out;
}

require('include/footer.php');
