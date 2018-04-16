<?php
//this is for automatic updates, called from github after push to master branch
//if you would like get realtime updates email me vidmantas.norkus@gw.lt your http://project.com/pullupdates.php link

//switch to deploy user is needed - sudo is used
//
//add to /etc/sudoers line:
//www-data ALL = (root) NOPASSWD: /usr/bin/php /your/directory/pullupdates.php

if(isset($_SERVER['REMOTE_ADDR'])){
	header('Content-type: text/plain');
		
	echo "exec res:\n";
	$res=shell_exec($cmd="sudo /usr/bin/php ".__FILE__.' 2>&1');
	//echo $cmd;
	echo $res;
	echo ".";
	exit;
}

if(!isset($_SERVER['HTTP_HOST'])){
	echo "usr {$_SERVER['USER']}. pulling\n";
	$dir = __DIR__;
	echo shell_exec("cd '$dir' && git pull 2>&1");
	//echo shell_exec("cd '$dir' && rm repository/.sys/templates_c/*");
	
	$fn="$dir/versionnum";$v=file_get_contents($fn);file_put_contents($fn, $v+1);
}
