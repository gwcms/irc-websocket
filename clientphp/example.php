<?php


function __autoload($class_name) 
{
	$parts = explode('\\', strtolower($class_name));	
	
	include __DIR__.'/lib/'.implode('/', $parts) . '.class.php';
}

include dirname(__DIR__).'/config/main.php';

$port = GW::s('WSS/PORT');
$hostname = GW::s('WSS/HOSTNAME');

GW::s('WSS/USER', 'petras');
GW::s('WSS/PASS', 'test123');


$GLOBALS['start'] = microtime(true);
$GLOBALS['time'] = function (){ return round(microtime(true) - $GLOBALS['start'], 5); };



//$client = new WebSocket\Client("wss://gw.lt:9000/demo");

	$user = GW::s('WSS/USER');
	$pass = GW::s('WSS/PASS');
	
	$client = new WebSocket\Client("wss://$user:$pass@$hostname:$port/irc");

$test = 'EASY_PRIVATE_MESSAGE';


if($test == 'KEEP_ALIVE'){
	

	$client->registerEvent('connected', function($client){
		echo "connected";

		//$client->authoriseOrRegister(GW::s('WSS/USER'), GW::s('WSS/PASS'));

		$client->joinChannel('test1','');
	});


	$client->registerEvent('onjoinchan', function($channel){

		//list joined users
		$users = $channel->listUsers();

		if(is_array($users))
			echo "Channel #$channel->name users: ".implode(', ', array_keys($users))."\n";
		//say something
		$channel->say('Hi!');

		echo "Message sent to channel, time from start: ". $GLOBALS['time']() ."s\n";
	});

	/*
	 * incoming data
	 * */
	$client->registerEvent('incoming', function($data){

		if(!is_int($data) || !is_string($data))
			$data = print_r($data, true);


		echo date('H:i:s')." $data\n";
	});
	/**/



	$client->init();
	$client->connect();

	
	while(true){
		$client->heartBeat();
		usleep(50000);
	}

}

if($test == 'EASY_CHANEL_MESSAGE'){
	

	$error_code = $client->fastChanMessage(['channel' => 'test1','channel_pass' => ''], "Hellou!");
	
	echo ($error_code ? "Error sending message: $error_code" : "Message sent!"). "\n";
	echo "Time from start ".$GLOBALS['time']()."\n";
	
}


if($test == 'EASY_PRIVATE_MESSAGE'){
	

	GW::s('WSS/USER', 'wdm');
	GW::s('WSS/PASS', 'test');	
	
	$error_code = $client->fastPrivateMessage(['username' => 'wdm'], "Hellou!");
	
	echo ($error_code ? "Error sending message: $error_code" : "Message sent!"). "\n";
	echo "Time from start ".$GLOBALS['time']()."\n";
	
}

if($test == 'PRIVATE_MESSAGE_WAIT_FOR_RESPONSE'){
	
	
	$waittorespond = 20;
	
		
	$client = new WebSocket\Client("wss://wdm:test@$hostname:$port/irc");



	list($error_code, $error_message) = $client->fastPrivateMessage([
		'username' => 'm1'
	], "Hellou!", $privatemsgid, $waittorespond);

	echo "privmsgid: ".$privatemsgid."\n";

	echo $waittorespond===null ? "FAIL: timed out" : "REPLY: $waittorespond";
	echo "\n";	 

		
	if($client->errors)
		print_r($client->errors);
	
	
	echo ($error_code ? "Error sending message: ($error_code) $error_message" : "Message sent!"). "\n";
	echo "Time from start ".$GLOBALS['time']()."\n";
	
}

if($test == "TEST_2CLIENT")
{	
	$u1 = "test1";$p1="g5s1e5r1ggs1";
	$u2 = "test2";$p2="1sgfg20s2df1";

	$c1 = new WebSocket\Client("wss://$u1:$p1@$hostname:$port/irc");
	$c2 = new WebSocket\Client("wss://$u2:$p2@$hostname:$port/irc");
	
	
	$testphrase = "TESTAS".date('Ymd_His');
	$testsuccess = false;
	
	$c2->registerEvent('connected', function($client) use ($c2, $u1, $testphrase){
		$c2->messagePrivate($u1, $testphrase);	
	});	
	
	$c1->registerEvent('incoming_messageprivate', function($data) use ($testphrase, &$testsuccess) {

		print_r(['incoming'=>$data]);
		
		if($data['data']==$testphrase){
			$testsuccess = true;
		}
	});	
	
	
	$c1->connect();
	$c2->connect();
	
	
	if(!$c1->is_connected || !$c2->is_connected)
	{
		print_r([$c1->errors, $c2->errors]);
		die("Test failed. cant connect\n");
	}
	
	
	$seconds_to_wait = 20;
	
	while($seconds_to_wait > 0){
		$c1->heartBeat();
		$c2->heartBeat();
		
		usleep(50000);//
		$seconds_to_wait -= 0.05;
		
		if($testsuccess)
		{
			echo "test success";
			break;
		}
	}

	if(!$testsuccess)
		echo "Test failed\n";		
}

