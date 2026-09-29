<?php

// ===========================================================================
// Tema "nova": coluna direita e rodapé {footer.php}
// ===========================================================================

style_module_section(@$Sections["bottom"], "section-bottom");

?></main>

<aside class="sidebar sidebar-right">
<?php if (@$Modules) modules($Modules, 'right'); ?>
</aside>

</div>

<footer class="bottombar">
<?php if (!empty($Config["Disclaimer"])) { ?>	<span><?php echo $Config["Disclaimer"]; ?></span>
<?php } ?>
<?php
if ($Config['Debug'] && isset($db)) {
	$timing_stop = explode(' ', microtime());
	$rendertime = number_format(num((($timing_stop[0] + $timing_stop[1]) - ($timing_start[0] + $timing_start[1]))), 4, $Lang['DecPoint'], ' ');
	echo "\t<span title=\"{$Lang['RenderTime']}\">{$rendertime} s &middot; {$db->queries} queries</span>\n";
}
?>
</footer>

</div>

<script>
// contagem até ao próximo ciclo do motor de jogo
(function () {
	var el = document.querySelector('.topbar .tick');
	if (!el) return;
	var left = parseInt(el.getAttribute('data-left'), 10), length = parseInt(el.getAttribute('data-length'), 10);
	function show() {
		var m = Math.floor(left / 60), s = left % 60;
		el.textContent = m + ':' + (s < 10 ? '0' : '') + s;
		el.style.setProperty('--progress', (100 * (length - left) / length) + '%');
	}
	show();
	setInterval(function () { left = left > 1 ? left - 1 : length; show(); }, 1000);
})();
</script>
