<?php

// ===========================================================================
// Tema "nova": cabeçalho, barra de recursos e menu lateral {header.php}
// ===========================================================================

global $thicklength, $begining;

// "Galaxy Forces - Milky Way" -> marca + nome do universo
$nova_title = explode(' - ', $Config['Title'], 2);

// ---------------------------------------------------------------------------
// Recursos da colónia
// ---------------------------------------------------------------------------

function nova_resource($key, $label, $amount, $capacity = null)
{
	$full = $capacity !== null && $amount > $capacity;
	$tip = $label . ': ' . strip_tags(strdiv($amount)) . ($capacity !== null ? ' / ' . strip_tags(strdiv($capacity)) : '');
	echo "\t\t<li class=\"res res-$key" . ($full ? ' full' : '') . "\" title=\"" . htmlspecialchars(html_entity_decode($tip)) . "\">"
		. "<img src=\"images/$key.jpg\" alt=\"\" width=\"16\" height=\"16\" />"
		. "<span class=\"res-label\">$label</span><span class=\"res-value\">" . strdiv($amount) . "</span></li>\n";
}

?><div id="nova">

<header class="topbar">
	<a class="brand" href="<?php echo $logged ? 'control.php' : 'welcome.php'; ?>">
		<span class="brand-name"><?php echo htmlspecialchars($nova_title[0]); ?></span>
<?php if (isset($nova_title[1])) { ?>		<span class="brand-universe"><?php echo htmlspecialchars($nova_title[1]); ?></span>
<?php } ?>	</a>

<?php if ($logged && $auth) { ?>
	<ul class="resources">
<?php
	if (!empty($Colony)) {
		if (@$Planet['technology'] == 'tron') {
			nova_resource('energy', $Lang['Energy'], $Colony['energy'], $Colony['energycapacity']);
			nova_resource('silicon', $Lang['Silicon'], $Colony['silicon'], $Colony['siliconcapacity']);
			nova_resource('metal', $Lang['Metal'], $Colony['metal'], $Colony['metalcapacity']);
			nova_resource('plutonium', $Lang['Plutonium'], $Colony['plutonium'], $Colony['plutoniumcapacity']);
			nova_resource('deuterium', $Lang['Deuterium'], $Colony['deuterium'], $Colony['deuteriumcapacity']);
		}
		else {
			nova_resource('energy', $Lang['Energy'], $Colony['energy'], $Colony['energycapacity']);
			nova_resource('metal', $Lang['Metal'], $Colony['metal'], $Colony['metalcapacity']);
			nova_resource('uran', $Lang['Uran'], $Colony['uran'], $Colony['urancapacity']);
			nova_resource('food', $Lang['Food'], $Colony['food'], $Colony['foodcapacity']);
			nova_resource('crystals', $Lang['Crystals'], $Colony['crystals']);
		}
	}
	nova_resource('credits', $Lang['Credits'], $Player['credits']);
?>
	</ul>

	<div class="status">
<?php
	if (!empty($stardate) && !empty($thicklength)) {
		// segundos até ao próximo ciclo, a partir da duração real do ciclo deste universo
		$nova_next = $thicklength - ((time() - $begining) % $thicklength);
?>		<a class="stardate" href="stardate.php" title="<?php echo htmlspecialchars($Lang['CSD']); ?>">
			<span class="stardate-value"><?php echo div($stardate); ?></span>
			<span class="tick" data-left="<?php echo $nova_next; ?>" data-length="<?php echo (int)$thicklength; ?>"></span>
		</a>
<?php
	}
	if (isset($Player['exp']) && $Player['exp4level'] > $Player['expbegin']) {
		$nova_bar = function ($value, $max) { return max(2, min(100, round(num(100 * $value / max(1, $max))))); };
		$nova_exp = $nova_bar($Player['exp'] - $Player['expbegin'], $Player['exp4level'] - $Player['expbegin']);
?>		<div class="hero" title="<?php echo htmlspecialchars("{$Lang['Level']} {$Player['level']}"); ?>">
			<span class="hero-name"><?php echo htmlspecialchars($Player['login']); ?> <b><?php echo (int)$Player['level']; ?></b></span>
			<span class="bar bar-exp" title="<?php echo htmlspecialchars($Lang['Experience'] ?? 'EXP'); ?>"><i style="width: <?php echo $nova_exp; ?>%"></i></span>
			<span class="bar bar-hp" title="HP <?php echo (int)$Player['hp'] . ' / ' . (int)$Player['hpmax']; ?>"><i style="width: <?php echo $nova_bar($Player['hp'], $Player['hpmax']); ?>%"></i></span>
			<span class="bar bar-mp" title="MP <?php echo (int)$Player['mp'] . ' / ' . (int)$Player['mpmax']; ?>"><i style="width: <?php echo $nova_bar($Player['mp'], $Player['mpmax']); ?>%"></i></span>
		</div>
<?php
	}
	if (!empty($Player['planet'])) {
?>		<a class="planet" href="galaxy.php?galaxy=<?php echo $Player['galaxy']; ?>&amp;object=<?php echo $Player['planet']; ?>" title="<?php echo htmlspecialchars(strcap($Player['planet'])); ?>">
			<img src="gallery/space/icons/<?php echo $Player['planet']; ?>.jpg" alt="<?php echo htmlspecialchars($Player['planet']); ?>" width="48" height="48" />
		</a>
<?php
	}
?>	</div>
<?php } else { ?>
	<div class="motd"><?php echo @file_get_contents("{$ROOT}MOTD.txt"); ?></div>
<?php } ?>
</header>

<div class="layout">

<nav class="sidebar">
<?php

tablebegin($Lang['Menu']);

$Style['menu.prefix'] = "\t\t<ul class=\"menu\">\n";
$Style['menu.suffix'] = "\t\t</ul>\n";
$Style['menu.item.prefix'] = "\t\t\t<li>";
$Style['menu.item.suffix'] = "</li>\n";
$Style['menu.separator'] = "\t\t</ul>\n\t\t<ul class=\"menu\">\n";

// destaca a página atual
ob_start();
style_menu_galaxy();
$nova_menu = ob_get_clean();
$nova_page = basename($_SERVER['PHP_SELF']);
echo str_replace("<li><a href=\"$nova_page\">", "<li class=\"active\"><a href=\"$nova_page\">", $nova_menu);

tableend('v' . trim(@file_get_contents("{$ROOT}VERSION.txt")));

if (!$logged) {
	tablebegin('GF');
?>		<ul class="menu">
			<li><a href="documentation.php"><?php __('MenuDocumentation'); ?></a></li>
			<li><a href="http://www.sourceforge.net/projects/galaxyforces"><?php __('MenuDownload'); ?></a></li>
			<li><a href="licence.php"><?php __('MenuLicence'); ?></a></li>
		</ul>
<?php
	tableend($Lang['Project']);
}

?></nav>

<main class="content">
<?php

if (@$sound) sound($sound);

style_module_section(@$Sections["top"], "section-top");
