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

// contadores de qualquer <span data-countdown="segundos"> (construção, investigação, produção...)
(function () {
	var els = [].slice.call(document.querySelectorAll('[data-countdown]'));
	if (!els.length) return;
	els.forEach(function (el) { el.dataset.left = parseInt(el.getAttribute('data-countdown'), 10) || 0; });
	function fmt(t) {
		if (t <= 0) return el_done;
		var d = Math.floor(t / 86400), h = Math.floor(t % 86400 / 3600), m = Math.floor(t % 3600 / 60), s = t % 60, o = '';
		if (d) o += d + 'd ';
		if (d || h) o += h + 'h ';
		if (!d) o += (h ? (m < 10 ? '0' : '') : '') + m + 'm ' + (s < 10 ? '0' : '') + s + 's';
		return o.trim();
	}
	var el_done = els[0].getAttribute('data-done') || '';
	setInterval(function () {
		els.forEach(function (el) {
			var t = parseInt(el.dataset.left, 10);
			el.textContent = fmt(t);
			if (t > 0) el.dataset.left = t - 1;
		});
	}, 1000);
	els.forEach(function (el) { el.textContent = fmt(parseInt(el.dataset.left, 10)); });
})();
</script>
