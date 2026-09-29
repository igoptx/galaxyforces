<?php

// ===========================================================================
// Tema "nova" {style.php}
// ===========================================================================
// Visual moderno de jogo espacial: painéis escuros semitransparentes com
// cabeçalho em gradiente, barra de recursos no topo e menu lateral.
// As funções têm as mesmas assinaturas do tema "galaxy", para as páginas não
// precisarem de alterações.

global $Style;

$Style['Stylesheet'] = 'style/nova/style.css';
$Style['ShortcutIcon'] = 'favicon.ico';
$Style['Doctype'] = '<!DOCTYPE html>';
$Style['Head'] = "\t<meta name=\"viewport\" content=\"width=device-width, initial-scale=1\" />\n";

global $HANDLER;

$HANDLER['style_box_head'] = 'tablebegin';
$HANDLER['style_box_foot'] = 'tableend';

// Largura pedida pelas páginas (ex.: 400, 500, '100%') como max-width do painel.
function nova_width_style($width)
{
	if (!$width || $width === '100%') return '';
	if (is_numeric($width)) return ' style="max-width: ' . (int)$width . 'px"';
	return ' style="max-width: ' . htmlspecialchars($width) . '"';
}

// ---------------------------------------------------------------------------
// Painéis
// ---------------------------------------------------------------------------

function tablebegin($header = '', $width = '100%', $height = '0', $id = '', $align = 'center', $cellalign = 'center', $prefix = "\t")
{
	echo "\n<section class=\"panel\"" . ($id ? " id=\"$id\"" : '') . nova_width_style($width) . ">\n";
	if ($header) echo "\t<header class=\"panel-head\"><h2>$header</h2></header>\n";
	echo "\t<div class=\"panel-body\">\n";
}

function tableend($footer = '', $prefix = "\t")
{
	echo "\t</div>\n";
	if ($footer) echo "\t<footer class=\"panel-foot\">$footer</footer>\n";
	echo "</section>\n";
}

function tablebreak($prefix = "\t")
{
	echo "\t<hr class=\"panel-break\" />\n";
}

// Colunas dentro de um painel: as páginas abrem com subbegin(), separam com
// subbreak() e fecham com subend(), por isso continua a ser uma tabela.
function subbegin($background = '')
{
	echo "\t<table class=\"sub\"><tr valign=\"top\"><td>\n";
}

function subbreak()
{
	echo "\t</td><td class=\"sub-gap\"></td><td>\n";
}

function subend()
{
	echo "\t</td></tr></table>\n";
}

function intbegin($size = 8, $background = '')
{
	echo "\t<div class=\"inset\" style=\"padding: " . (int)$size . "px\">\n";
}

function intend($size = 8)
{
	echo "\t</div>\n";
}

// Imagem com moldura (planetas, unidades, avatares...).
function tableimg($bg, $bgwidth, $bgheight, $image, $width, $height, $href = '', $align = '', $alt = '', $prefix = "\t\t")
{
	$float = ($align == 'left' || $align == 'right') ? " float-$align" : '';
	echo "$prefix<span class=\"thumb$float\" style=\"width: {$bgwidth}px; height: {$bgheight}px\">"
		. ($href ? "<a href=\"$href\">" : '')
		. "<img src=\"$image\"" . ($alt ? " alt=\"$alt\"" : ' alt=""') . " width=\"$width\" height=\"$height\" />"
		. ($href ? '</a>' : '')
		. "</span>\n";
}

// ---------------------------------------------------------------------------
// Títulos e ligações
// ---------------------------------------------------------------------------

function echotitle($s)
{
	echo "\t<h3 class=\"h3\">$s</h3>\n";
}

function anchor($l, $s, $c = '')
{
	return '<a class="action' . ($c ? " $c" : '') . "\" href=\"$l\">$s</a>";
}

function echolink($l, $s, $c = '')
{
	echo anchor($l, $s, $c);
}

function echolinkbox($l, $s, $c = '')
{
	echo "\t<p class=\"linkbox\">" . anchor($l, $s, $c) . "</p>\n";
}
