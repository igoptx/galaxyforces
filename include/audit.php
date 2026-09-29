<?php

// ===========================================================================
// Registo de auditoria {audit.php}
// ===========================================================================
// Guarda na tabela {prefix}audit o que acontece no jogo (logins, registos,
// colónias, ataques, ações de administração...) para o Centro de Operações.
// Nunca pode partir uma página: qualquer falha é ignorada.

if (!defined('__AUDIT_PHP__')) {

define('__AUDIT_PHP__', 1);

function audit_schema()
{
	global $prefix;
	return "CREATE TABLE IF NOT EXISTS `{$prefix}audit` (
		`id` int(11) NOT NULL auto_increment,
		`time` datetime NOT NULL,
		`actor` varchar(32) NOT NULL default '',
		`ip` varchar(64) NOT NULL default '',
		`category` varchar(16) NOT NULL default '',
		`action` varchar(32) NOT NULL default '',
		`target` varchar(64) NOT NULL default '',
		`details` varchar(255) NOT NULL default '',
		PRIMARY KEY (`id`),
		KEY `time` (`time`),
		KEY `category` (`category`),
		KEY `actor` (`actor`)
	)";
}

// audit('auth', 'login', 'nome', 'detalhes')
function audit($category, $action, $target = '', $details = '', $actor = null)
{
	global $db, $prefix, $login;
	static $ready = false;
	if (empty($db)) return false;

	if ($actor === null) $actor = isset($login) ? (string)$login : '';
	$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'cli';
	if (!empty($_SERVER['HTTP_X_REAL_IP'])) $ip = $_SERVER['HTTP_X_REAL_IP'];

	$fields = array(
		'actor' => mb_substr((string)$actor, 0, 32),
		'ip' => mb_substr((string)$ip, 0, 64),
		'category' => mb_substr((string)$category, 0, 16),
		'action' => mb_substr((string)$action, 0, 32),
		'target' => mb_substr(strip_tags((string)$target), 0, 64),
		'details' => mb_substr(strip_tags((string)$details), 0, 255),
	);
	$values = array();
	foreach ($fields as $v) $values[] = "'" . $db->safe($v) . "'";

	// guarda e repõe o resultado atual: as páginas usam o último query() em curso
	$saved = $db->result;
	$db->result = null;
	$sql = "INSERT INTO `{$prefix}audit` (`time`, `actor`, `ip`, `category`, `action`, `target`, `details`) VALUES (NOW(), " . implode(', ', $values) . ")";
	$ok = @mysqli_query($db->link, $sql);
	if (!$ok && !$ready) {
		// tabela ainda não existe numa base antiga: cria e tenta de novo
		@mysqli_query($db->link, audit_schema());
		$ok = @mysqli_query($db->link, $sql);
	}
	$ready = true;
	$db->result = $saved;
	return (bool)$ok;
}

}
