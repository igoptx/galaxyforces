<?php

// ===========================================================================
// Highscores {highscores.php}
// ===========================================================================

// ---------------------------------------------------------------------------
//	Version:	1.8
//	Modified:	2005-11-13
//	Author(s):	zoltarx, unk
// ---------------------------------------------------------------------------

// ===========================================================================
// This file is a part of Galaxy Forces project.
// ===========================================================================

$index = 'control';
$auth = true;

require("include/header.php");
require("include/functions.php");

$category = preg_replace('/[^a-z0-9_]/i', '', (string)getvar('category'));   // ecoado nos links
$page = abs(num(getvar('page')));

$pagecount = 100;

switch ($category) {
	case 'level': $order = "`level` DESC, `score` DESC, `login`"; break;
	case 'leveldesc': $order = "`level`, `score`, `login` DESC"; break;
	case 'voyaged': $order = "`voyaged` DESC,`level` DESC,`score` DESC,`login`"; break;
	case 'voyageddesc': $order = "`voyaged`,`level`,`score`,`login` DESC"; break;
	case 'login': $order = "`login` ASC"; break;
	case 'logindesc': $order = "`login` DESC"; break;
	case 'clan': $order = "`clan`,`login`"; break;
	case 'clandesc': $order = "`clan` DESC,`login` DESC"; break;
	case 'ip': $order = "`ip`, `login`"; break;
	case 'ipdesc': $order = "`ip` DESC, `login` DESC"; break;
	case 'scoredesc': $order = "`score`,`level`, `login` DESC"; break;
	case 'score':
	default: $order = "`score` DESC,`level` DESC,`login`"; break;
}

if ($max = $db->rows("{$prefix}users")) $max--; // administrator account (id=0) is not counted

if ($page > $m = floor(num($max / $pagecount))) $page = $m;
$l = $page * $pagecount;
$clan = $Player['clan'];

if (!$db->query("SELECT login,clan,level,score,voyaged,ip,lastip FROM {$prefix}users WHERE id>0 ORDER BY $order LIMIT $l,$pagecount;")) {
//if (!$db->query("SELECT ${prefix}users.login,refs,usergroup,clan,level,score,voyaged,ip,lastip, ${prefix}colonies.name AS colony, ${prefix}diplomacy.type FROM ${prefix}users LEFT JOIN ${prefix}colonies ON ${prefix}users.login = ${prefix}colonies.owner LEFT JOIN ${prefix}diplomacy ON (${prefix}diplomacy.clan1 = '$clan' AND ${prefix}diplomacy.clan2 = ${prefix}users.clan) OR (${prefix}diplomacy.clan1 = ${prefix}users.clan AND ${prefix}diplomacy.clan2 = '$clan') WHERE ${prefix}users.id>0 ORDER BY ${prefix}users.$order LIMIT $l,$pagecount;")); {
	echo $errors = '<br /><span class="error">'.$Lang['ErrorQueryFailed'].'!<br />'.$db->error().'!<br /><br />';
	$s = $Lang['Error'];
}
else {

$staff = ($User['usergroup'] == 'wheel' || $User['usergroup'] == 'moderators');

// cabeçalho ordenável: alterna entre ascendente e descendente na mesma coluna
$sortlink = function ($asc, $desc, $label) use ($category, $page, $Lang) {
	$active = ($category === $asc || $category === $desc);
	$next = ($category === $asc) ? $desc : $asc;
	$arrow = ($category === $asc) ? ' ▲' : (($category === $desc) ? ' ▼' : '');
	return '<a href="highscores.php?category=' . $next . '&amp;page=' . (int)$page . '"'
		. ($active ? ' class="result"' : '') . ' title="' . htmlspecialchars($Lang['ReverseOrder']) . '">'
		. htmlspecialchars($label) . $arrow . '</a>';
};

tablebegin($Lang['Score'] . ' <span class="muted">(' . div($max) . ')</span>');

echo "\t<table class=\"list highscores\">\n\t<thead><tr>";
echo '<th class="rank">#</th>';
echo '<th class="left">' . $sortlink('login', 'logindesc', $Lang['Name']) . '</th>';
echo '<th class="left">' . $sortlink('clan', 'clandesc', $Lang['Clan']) . '</th>';
if ($staff) echo '<th>' . $sortlink('ip', 'ipdesc', 'IP') . '</th>';
echo '<th>' . $sortlink('level', 'leveldesc', $Lang['Level']) . '</th>';
echo '<th>' . $sortlink('voyaged', 'voyageddesc', $Lang['Voyaged']) . '</th>';
echo '<th>' . $sortlink('score', 'scoredesc', $Lang['Score']) . '</th>';
echo "</tr></thead>\n\t<tbody>\n";

$i = 0;
while ($t = $db->fetchrow()) {
	$i++;
	$rank = $l + $i;
	$cls = ($t['login'] == $login) ? ' class="here"' : '';
	$medal = $rank <= 3 ? ' rank-' . $rank : '';

	echo "\t<tr$cls>";
	echo '<td class="rank"><span class="rank-badge' . $medal . '">' . $rank . '</span></td>';
	echo '<td class="left"><a href="whois.php?name=' . urlencode($t['login']) . '">' . htmlspecialchars($t['login']) . '</a></td>';
	echo '<td class="left">' . ($t['clan'] !== '' ? '<a href="clanhall.php?clan=' . urlencode($t['clan']) . '">' . htmlspecialchars($t['clan']) . '</a>' : '<span class="muted">—</span>') . '</td>';

	if ($User['usergroup'] == 'wheel')
		echo '<td class="nowrap"><a href="https://apps.db.ripe.net/db-web-ui/query?searchtext=' . urlencode($t['ip']) . '">' . htmlspecialchars($t['ip']) . '</a>'
			. ($t['lastip'] && $t['ip'] != $t['lastip'] ? ', <a href="https://apps.db.ripe.net/db-web-ui/query?searchtext=' . urlencode($t['lastip']) . '">' . htmlspecialchars($t['lastip']) . '</a>' : '') . '</td>';
	elseif ($User['usergroup'] == 'moderators')
		echo '<td>' . htmlspecialchars(ip_camuflage($t['ip'])) . '</td>';

	echo '<td class="plus">' . (int)$t['level'] . '</td>';
	echo '<td class="capacity nowrap">' . htmlspecialchars(number_format(num($t['voyaged']), 2, $Lang['DecPoint'], ' ')) . '</td>';
	echo '<td class="result nowrap">' . div($t['score']) . "</td>";
	echo "</tr>\n";
}

echo "\t</tbody></table>\n";

$n = $page;

$s = ($n ? "<a href=\"highscores.php?category=$category&page=0\">" : '').'&lt;&lt;&nbsp;'.$Lang['Begin'].($n ? '</a>' : '').' &nbsp; ';
$s .= ($n ? "<a href=\"highscores.php?category=$category&page=".($n - 1).'">' : '').'&lt;&lt;&nbsp;'.$Lang['Previous'].($n ? '</a>' : '').' &nbsp; ';

$a = $n > 5 ? $n - 5 : 0;
$b = $n < $m - 5 ? $n + 5 : $m;

if ($a > 0) $s .= '... ';

for ($i = $a; $i <= $b; $i++) {
	if ($i != $n) $s .= "<a href=\"highscores.php?category=$category&page=$i\">";
	$s .= $i + 1;
	if ($i != $n) $s .= "</a>";
	$s .= ' ';
}

if ($b < $m) $s .= ' ...';

$s .= " &nbsp; ".($n < $m ? "<a href=\"highscores.php?category=$category&page=".($n + 1).'">' : '').$Lang['Next'].'&nbsp;&gt;&gt;'.($n < $m ? '</a>' : '');
$s .= ' &nbsp; '.($n < $m ? "<a href=\"highscores.php?category=$category&page=$m\">" : '').$Lang['End'].'&nbsp;&gt;&gt;'.($n < $m ? '</a>' : '');

}

tableend($s);

require('include/footer.php');
