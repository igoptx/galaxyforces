<?php

// ===========================================================================
// BB Code {bbcode.php)
// ===========================================================================

// ---------------------------------------------------------------------------
//	Version:	1.1
//	Created:	2005-05-13
//	Modified:	2005-11-19
//	Author(s):	zoltarx
// ---------------------------------------------------------------------------

// ===========================================================================
// This file is a part of Synapse project (http://phpsynapse.sourceforge.net).
// You may only use, modify, copy or distribute this content under the terms
// of GNU General Public License (GPL) or Synapse Artistic Licence (SAL).
// See LINCENSE file for details.
// =========================================================================== 

$bbcode_replace = $bbcode_with = array();

$bb_replace[] = '/\[img]javascript:(.*?)\[\/img]/si'; // thx to sharkpp
$bb_with[] = '';

$bb_replace[] = '/\[p](.*?)\[\/p]/si';
$bb_with[] = '<p />$1';
$bb_replace[] = '/\[p](.*?)/si';
$bb_with[] = '<p />$1';
$bb_replace[] = '/\[br](.*?)/si';
$bb_with[] = '<br />$1';
$bb_replace[] = '/\[br](.*?)\[\/br]/si';
$bb_with[] = '<br />$1';

$bb_replace[] = '/\[b](.*?)\[\/b]/si';
$bb_with[] = '<b>$1</b>';
$bb_replace[] = '/\[i](.*?)\[\/i]/si';
$bb_with[] = '<i>$1</i>';
$bb_replace[] = '/\[u](.*?)\[\/u]/si';
$bb_with[] = '<u>$1</u>';
$bb_replace[] = '/\[sup](.*?)\[\/sup]/si';
$bb_with[] = '<sup>$1</sup>';
$bb_replace[] = '/\[sub](.*?)\[\/sub]/si';
$bb_with[] = '<sub>$1</sub>';

$bb_replace[] = '/\[title](.*?)\[\/title]/si';
$bb_with[] = '<h3>$1</h3>';
$bb_replace[] = '/\[code](.*?)\[\/code]/si';
$bb_with[] = '<code>$1</code>';
$bb_replace[] = '/\[blockquote](.*?)\[\/blockquote]/si';
$bb_with[] = '<blockqoute>$1</blockquote>';

$bb_replace[] = '/\[center](.*?)\[\/center]/si';
$bb_with[] = '<center>$1</center>';
$bb_replace[] = '/\[left](.*?)\[\/left]/si';
$bb_with[] = '<div style="text-align: left">$1</div>';
$bb_replace[] = '/\[right](.*?)\[\/right]/si';
$bb_with[] = '<div style="text-align: right">$1</div>';
$bb_replace[] = '/\[justify](.*?)\[\/justify]/si';
$bb_with[] = '<div style="text-align: justify">$1</div>';

// [img] é tratado em bbcode() com valid_image_url()

if (@$Config['BBParseLinks']) {
	$bb_replace[] = '/\[link](.*?)\[\/link]/si';
	$bb_with[] = '<a href="$1">$1</a>';
	$bb_replace[] = '/\[link=(.*?)](.*?)\[\/link]/si';
	$bb_with[] = '<a href="$1">$2</a>';
}

if (!defined('__BBCODE__')) {

function bbcode($text)
{
	global $bb_replace, $bb_with;
	// imagens só com URL absoluto de imagem (sem query): um [img]admin.php?action=...
	// fazia quem lesse a mensagem executar ações, e aspas no URL davam XSS
	$text = preg_replace_callback('/\[img(?:=(.*?))?](.*?)\[\/img]/si', function ($m) {
		$src = valid_image_url($m[1] !== '' ? $m[1] : $m[2]);
		if ($src === '') return htmlspecialchars($m[0], ENT_QUOTES);
		return '<img src="' . $src . '"' . ($m[1] !== '' ? ' alt="' . htmlspecialchars($m[2], ENT_QUOTES) . '"' : '') . ' />';
	}, $text);
	$text = preg_replace($bb_replace, $bb_with, $text);
	// [link]: só http(s)
	return preg_replace('#<a href="(?!https?://)[^"]*"#i', '<a href="#"', $text);
}

define('__BBCODE_PHP__', 1);

}
