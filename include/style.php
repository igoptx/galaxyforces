<?php

// ===========================================================================
// Style {style.php}
// ===========================================================================

define('TAB', "\t");
define('LF', "\n");
define('BR', '<br />');
define('NL', "\t<br />\n");

define('STYLE', $ROOT.'style/'.$Config['Style'].'/');

$LINKBOXBEGIN = "<br />";
$LINKBOXEND = "<br />";
$LINKBOXPREFIX = "";
$LINKBOXPOSTFIX = "&nbsp;&gt;&gt;";

function swf($id, $path, $width, $height, $bgcolor = 'none', $prefix = "\t\t", $wmode = '')
{
	if ($wmode) {
		$wmode = "wmode=\"$wmode\" ";
		$param = "<param name=\"wmode\" value=\"$wmode\">";
	}
	else $param = '';
	echo "$prefix<object classid=\"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000\" codebase=\"http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,79,0\" id=\"$id\" width=\"$width\" height=\"$height\">\n";
	echo "$prefix<param name=\"movie\" value=\"$path\"><param name=\"quality\" value=\"high\">$param<param name=\"bgcolor\" value=\"$bgcolor\">\n";
	echo "$prefix<embed name=\"$id\" src=\"$path\" quality=\"high\" {$wmode}bgcolor=\"$bgcolor\" width=\"$width\" height=\"$height\" type=\"application/x-shockwave-flash\" swLiveConnect=\"true\" pluginspage=\"http://www.macromedia.com/go/getflashplayer\"></embed>\n";
	echo "$prefix</object>\n";
}

function sound($file)
{
	global $Player;
	if (! (@$Player['soundsoff'])) swf($file, "sounds/$file.swf", 1, 1, '#000000');
}
 
// ---------------------------------------------------------------------------
// Style
// ---------------------------------------------------------------------------

function style_linkcaption($caption)
{
	if (function_exists('custom_linkcaption')) return custom_linkcaption($caption);
	global $PRELINK, $POSTLINK;
	return @$PRELINK.$caption.@$POSTLINK;
}

function style_boxbegin($caption='')
{
	global $BOXBEGIN, $BOXCAPTIONBEGIN, $BOXCAPTIONEND;
	if ($caption = str_eval($caption)) {
		if ($BOXCAPTIONBEGIN || $BOXCAPTIONEND) return $BOXCAPTIONBEGIN.$caption.$BOXCAPTIONEND;
		return $BOXBEGIN.$caption;
	}
	return ($BOXBEGIN ? '' : $BOXCAPTIONBEGIN.$BOXCAPTIONEND).$BOXBEGIN;
}

function style_boxend($status='')
{
	global $BOXEND, $BOXSTATUSBEGIN, $BOXSTATUSEND;
	if (!@$BOXENDBEGIN && (@$BOXSTATUSBEGIN || @$BOXSTATUSEND)) return @$BOXSTATUSBEGIN.str_eval($status).@$BOXSTATUSEND;
	return (($status = str_eval($status)) ? @$BOXSTATUSBEGIN.$status.@$BOXSTATUSEND : '').@$BOXEND;
}

function style_boxbreak()
{
	global $BOXBREAK;
	return $BOXBREAK;
}

function style_linkbox($location, $caption, $style='')
{
	global $LINKBOXBEGIN, $LINKBOXEND, $LINKBOXPREFIX, $LINKBOXPOSTFIX;
	return @$LINKBOXBEGIN.'<a href="'.$location.($style ? '" class="'.$style: '').'">'.@$LINKBOXPREFIX.$caption.@$LINKBOXPOSTFIX.'</a>'.$LINKBOXEND;
}

function style_menu_galaxy()
{	
	global $Menu, $Lang, $Style, $Media;
	global $logged;
	global $Player, $Colony;
	
	if (!$logged) $group=$clan=$colony=false;
	else {
		$group=$Player['usergroup'];
		$clan=$Player['clan'];
		$colony=!empty($Colony);
	}

	ob_start();
	
	$i=0;
	while ($i<count((array)($Menu)))
	{
		$m=$Menu[$i];
		$x=++$i;
		if 
		(
			($u=@$m['*'])&&$u!='*'&&
			(
			$u=='-'&&$logged||
			$u=='+'&&!$logged||
			$u=='@'&&!$group||
			$u=='%'&&!$clan||
			$u=='#'&&!$colony||
			strlen($u)&&$u[0]=='@'&&$group!=substr($u,1)||
			strlen($u)&&$u[0]=='%'&&$clan!=substr($u,1)
			)
		)
		continue;			

		$id=isset($m['$'])?$m['$']:$x;
		
		if (isset($m['@'])) $link=$m['@'];
		elseif (isset($m['$'])) $link=$m['$'];
		else $link="menu-$x.php";

		if (ctype_alnum($link)) $link.='.php';		

		if ($ext=str_to_alnum(@$Style['MenuIconExtension'])) $ext='.'.$ext; else $ext='.gif';	
		if ($dim=(int)@$Style['MenuIconDimesion']) $dim='-'.$dim; else $dim="";
	
		$image=isset($m['&'])?$m['&']:'icon'.$x.$dim.$ext;
	
		$lang=isset($m['_'])?$m['_']:"Menu$id";
		if (isset($Lang[$lang])) $lang=$Lang[$lang];
		
		if ($id=="-") {
			echo @$Style['menu.separator'];
			continue;
		}
		
		echo @$Style['menu.item.prefix'];
		if ($link) echo '<a href="'.$link.'">';
		echo $lang;
		if ($link) echo '</a>';
		echo @$Style['menu.item.suffix'];
//		echo "$id\n$link\n$lang\n$image\n";
	}

	$out = ob_get_contents();
	
	ob_end_clean();
	
	if (!$out) return;
	
	echo @$Style['menu.prefix'];
	echo $out;
	echo @$Style['menu.suffix'];
}	

function style_box_head($title="")
{
	if (handler_call("style_box_head", $title)) return;
	echo '
<div>
';
	if ($title) echo '
	<div class="title">
	'.$title.'
	</div>
';
	echo '
	<div>
';
}

function style_box_foot($status="")
{
	if (handler_call("style_box_foot", $status)) return;
	echo '
	</div>
';
	if ($status) echo '
	<div class="status">
	'.$status.'
	</div>
';
	echo '
</div>
';
}

// ---------------------------------------------------------------------------
// Cartões (construção, investigação, colónia)
// ---------------------------------------------------------------------------
// Markup independente do tema: cada tema estiliza .cards, .card, .costs...

// Primeira imagem que existe da lista; sem nenhuma, um marcador com as iniciais.
function card_image($candidates, $href = '', $label = '')
{
	global $ROOT;
	$img = '';
	foreach ((array)$candidates as $c) if ($c && file_exists(@$ROOT . $c)) { $img = $c; break; }
	$inner = $img
		? '<img src="' . $img . '" alt="' . htmlspecialchars(strip_tags($label)) . '" />'
		: '<span class="card-noimg">' . htmlspecialchars(mb_strtoupper(mb_substr(strip_tags($label), 0, 2))) . '</span>';
	return '<' . ($href ? 'a href="' . $href . '"' : 'span') . ' class="card-img">' . $inner . '</' . ($href ? 'a' : 'span') . '>';
}

// Custos como chips com o ícone de cada recurso.
function card_costs($costs, $credits_class = 'result', $keys = null)
{
	global $Lang;
	$names = array('credits' => 'Credits', 'energy' => 'Energy', 'silicon' => 'Silicon', 'metal' => 'Metal', 'uran' => 'Uran',
		'plutonium' => 'Plutonium', 'deuterium' => 'Deuterium', 'food' => 'Food', 'crystals' => 'Crystals');
	$out = '';
	foreach ($names as $key => $lang) {
		if ($keys !== null && !in_array($key, $keys)) continue;   // só as chaves que a página mostrava
		if (empty($costs[$key])) continue;
		$label = isset($Lang[$lang]) ? $Lang[$lang] : $lang;
		$out .= '<li class="cost cost-' . $key . '" title="' . htmlspecialchars($label) . '"><img src="images/' . $key . '.jpg" alt="" width="16" height="16" />'
			. '<span' . ($key == 'credits' ? ' class="' . $credits_class . '"' : '') . '>' . div($costs[$key]) . '</span></li>';
	}
	return $out ? '<ul class="costs">' . $out . '</ul>' : '';
}

// Chips genéricos: cada chip é array('html' => ..., 'title' => ..., 'icon' => imagem ou null, 'label' => rótulo curto ou null).
function card_chips($chips, $class = 'stats')
{
	$out = '';
	foreach ($chips as $c) {
		$out .= '<li class="cost"' . (!empty($c['title']) ? ' title="' . htmlspecialchars(strip_tags($c['title'])) . '"' : '') . '>'
			. (!empty($c['icon']) ? '<img src="' . $c['icon'] . '" alt="" width="16" height="16" />' : '')
			. (!empty($c['label']) ? '<b class="chip-label">' . $c['label'] . '</b>' : '')
			. '<span>' . $c['html'] . '</span></li>';
	}
	return $out ? '<ul class="costs ' . $class . '">' . $out . '</ul>' : '';
}

function style_module_section($elements, $id="", $section="box")
{
	global $Style, $Lang;
	
	if (!is_array($elements)) $elements=array($elements);
	if (!count((array)($elements))) return;

	$content="";
	
	foreach ((array)$elements as $element)
	{
		if (empty($element)) continue;

		ob_start();
		module($element, "box");
		$out = trim(ob_get_contents());
		ob_end_clean();

		if (empty($out)) continue;
		
		$div = ($id ? $id.'-' : '').$section.'-'.(@++$index);
		$class = 'class-'.$element;

		ob_start();
		style_box_head(@$Lang["module.".$element.".caption"]);
		echo '
<div id="'.$div.'" class="'.$class.'">
'.$out.'
</div>
';
		style_box_foot(@$Lang["module.".$element.".status"]);
		$out = ob_get_contents();
		ob_end_clean();
		$content.=$out;
	}

	if (!$content) return;
	
	echo '
<div'.(empty($id)?'':' id="'.$id.'"').'>
'.$content.'
</div>
';

}

include(STYLE.'style.php');
