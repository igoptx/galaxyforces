<?php
/**
 * Driver for MySQL (mysqli)
 *
 * The original mysql_* extension was removed in PHP 7. This driver keeps the
 * same class name and API but uses mysqli, with exceptions disabled so that
 * failed queries return false like they used to.
 *
 * @package OXO
 * @version 29, 28/09/26
 * @since 1
 * @author Filip Golewski <zoltarx@o2.pl>
 *
 */

$CLASSNAME='mysql_db';
$SUPPORTS=array('mysql');

if (!class_exists($CLASSNAME, false)) {

class mysql_db
{
	var $layer='mysql', $host='localhost', $port=null, $user, $password, $name, $prefix;
	var $charset='UTF-8', $persistent;
	var $security="BASE64";
	var $link, $result, $queries, $last;
	var $lasterr="";

	function __construct($Database=null)
	{
		global $Errors;
		if (!function_exists('mysqli_connect')) { $Errors[] = "Extension <b>mysqli</b> is missing"; return; }
		if (!is_array($Database)) return;
		$this->user = @$Database['user'];
		$this->password = @$Database['password'];
		$this->name = @$Database['name'];
		$this->prefix = @$Database['prefix'];
		if (isset($Database['host'])) $this->host = $Database['host'];
		if (isset($Database['port'])) $this->port = (int)$Database['port'];
		if (isset($Database['persistent'])) $this->persistent=$Database['persistent'];
		if (isset($Database['security'])) $this->security=strtoupper($Database['security']);
		if (isset($Database['charset'])) $this->charset=$Database['charset'];
	}

	function connect()
	{
		if (!function_exists('mysqli_connect')) return false;
		mysqli_report(MYSQLI_REPORT_OFF);
		$secret = function_exists('secure_decode') ? secure_decode($this->password, $this->security) : $this->password;
		$host = ($this->persistent ? 'p:' : '') . $this->host;
		$this->link = @mysqli_connect($host, $this->user, $secret, '', $this->port ? $this->port : 3306);
		if (!$this->link) {
			$this->lasterr = mysqli_connect_errno().': '.mysqli_connect_error();
			$this->link = null;
			return false;
		}
		if ($this->name && !@mysqli_select_db($this->link, $this->name)) {
			$this->lasterr = $this->error();
			$this->close();
			return false;
		}
		$this->set_charset();
		return $this->link;
	}

	function escape($value)
	{
		if (!$this->link && !$this->connect()) return addslashes((string)$value);
		return mysqli_real_escape_string($this->link, (string)$value);
	}

	function free()
	{
		if ($this->result instanceof mysqli_result) @mysqli_free_result($this->result);
		$this->result = null;
	}

	function query($sql='')
	{
		if (!$this->link && !$this->connect()) return false;
		$this->free();
		$this->queries++;
		return $this->result = @mysqli_query($this->link, str_replace('#__', $this->prefix, $this->last=$sql));
	}

	function fetch_row()
	{
		if (!($this->result instanceof mysqli_result)) return false;
		$row = mysqli_fetch_assoc($this->result);
		return $row === null ? false : $row;
	}

	function fetch_all()
	{
		$result = array();
		while ($row = $this->fetch_row()) $result[] = $row;
		$this->free();
		return $result;
	}

	function table_rows($table)
	{
		$stored = $this->result;
		$this->result = null;
		$result = ($this->query("SHOW TABLE STATUS LIKE '".$this->escape($table)."';") and $row = $this->fetchrow()) ? $row['Rows'] : false;
		$this->free();
		$this->result = $stored;
		return $result;
	}

	function num_rows()
	{
		return $this->result instanceof mysqli_result ? mysqli_num_rows($this->result) : 0;
	}

	function affected_rows()
	{
		return $this->link ? mysqli_affected_rows($this->link) : 0;
	}

	function insert_id()
	{
		return $this->link ? mysqli_insert_id($this->link) : 0;
	}

	function error()
	{
		if ($this->lasterr) { $result=$this->lasterr; $this->lasterr=""; return $result; }
		if ($this->link) {
			if ($e=mysqli_errno($this->link)) return "$e: ".mysqli_error($this->link);
			else return false;
		}
		return false;
	}

	function close()
	{
		$this->free();
		if ($this->link && !$this->persistent) @mysqli_close($this->link);
		$this->link = null;
	}

	function set_charset($charset=null)
	{
		if (is_null($charset)) $charset=$this->charset;
		if (!$charset) return true;
		switch ($charset=strtoupper($charset)) {
			case 'UTF-8': $charset='utf8'; break;
			case 'ISO-8859-2': $charset='latin2'; break;
		}
		return @mysqli_set_charset($this->link, strtolower($charset));
	}

	function __destruct()
	{
		$this->close();
	}

	function fetchrow() { return $this->fetch_row(); }
	function fetchall() { return $this->fetch_all(); }
	function numrows() { return $this->num_rows(); }
	function rows($table) { return $this->table_rows($table); }
	function affectedrows() { return $this->affected_rows(); }
	function setcharset($charset='') { return $this->set_charset($charset); }
	function safe($str) { return $this->escape($str); }

}

}
