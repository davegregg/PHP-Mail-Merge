<?php
	echo '<blockquote>$_SERVER[DOCUMENT_ROOT]</blockquote> is <strong>'.$_SERVER['DOCUMENT_ROOT'];
	echo '</strong><br><br><blockquote>__FILE__</blockquote> is <strong>'.__FILE__;
	echo '</strong><br><br><blockquote>dirname(__FILE__)</blockquote> is <strong>'.dirname(__FILE__);
	echo '</strong><br><br><blockquote>getcwd()</blockquote> is <strong>'.getcwd();
	
	echo '</strong><br><br><blockquote>dirname($_SERVER[PHP_SELF])</blockquote> is <strong>'.dirname($_SERVER['PHP_SELF']);
	echo '</strong><br><br><blockquote>dirname($_SERVER[REQUEST_URI])</blockquote> is <strong>'.dirname($_SERVER['REQUEST_URI']);
	echo '</strong><br><br><blockquote>$_SERVER[REMOTE_ADDR]</blockquote> is <strong>'.$_SERVER['REMOTE_ADDR'];
	echo '</strong><br><br><blockquote>$_SERVER[HTTP_HOST]</blockquote> is <strong>'.$_SERVER['HTTP_HOST'];
	echo '</strong><br><br><blockquote>$_SERVER[SERVER_NAME]</blockquote> is <strong>'.$_SERVER['SERVER_NAME'];
	echo '</strong><br><br><blockquote>$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI]</blockquote> is <strong>'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
	echo '</strong><br><br><blockquote>$_SERVER[SERVER_PROTOCOL]</blockquote> is <strong>'.$_SERVER['SERVER_PROTOCOL'];
	echo '</strong>';
?>
