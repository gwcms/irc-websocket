<?php

class WebSocket_App extends GW_App_Base
{

	public $develop = false;
	public $CAN_QUIT = true;
	public $command_interface;
	public $one_instance = true;

	
	
	/**
	 *
	 * @var \WebSocket\Server 
	 */
	private $server;

	function help()
	{
		$this->msg("
		help - help
		i - info
		");
	}

	function inputCMD($line)
	{
		$args = explode(';', $line);
		$cmd = array_shift($args);

		$state_of_collect_messages = $this->collect_messages;
		$this->enableConsoleMessages();

		switch ($cmd) {
			case 'i':
				$this->msg("todo info");
				$this->msg($this->server->applications['irc']->getInfo());
			case 'v':
				$this->server->applications['irc']->verbose_level = 2;
				$this->msg("Verbose level: " . $this->server->applications['irc']->verbose_level);
			case 'help':
				$this->msg('i - info');
				$this->msg('v - turn into verbose level');
				$this->msg('r - restart');
				$this->msg('t - test stop process');

				$this->msg('help');
				break;
			case '':
				if (!$state_of_collect_messages)
					$this->toogleConsoleMessages();
				break;
			case 'r':
				$this->restart();
				break;
			
			case 't':
				//test stop processing

				$this->server = new Anonymous_Object([
					"run" => function() { echo '!';sleep(1); }, 
					'applications' => ['irc' => new Anonymous_Object(["processQueued" => function() { echo '|'; sleep(1); }] )]
				]);			
				
				break;			
			case 'c':
				$this->beforeQuit();
				break;
			

			default:
				$this->msg("Unrecognized command: $cmd, args: " . json_encode($args));
				break;
		}
	}

	
	
	function testIrcAppConnect()
	{
		$this->msg("Testing connection");
		$o = $this;
				
		$u1 = GW::s('TEST_USERS/U1');$p1=GW::s('TEST_USERS/P1');
		$u2 = GW::s('TEST_USERS/U2');$p2=GW::s('TEST_USERS/P2');
		
		$hostname = GW::s('WSS/HOSTNAME');
		$port = GW::s('WSS/PORT');

		$c1 = new WebSocket\Client("wss://$u1:$p1@$hostname:$port/irc");
		$c2 = new WebSocket\Client("wss://$u2:$p2@$hostname:$port/irc");


		$testphrase = "TESTAS".date('Ymd_His');
		$testsuccess = false;

		

		$c2->registerEvent('connected', function($client) use ($c2, $u1, $testphrase, $o){
			$c2->messagePrivate($u1, $testphrase);	
			
			$o->msg("test sent : '$testphrase'");
		});	

		$c1->registerEvent('incoming_messageprivate', function($data) use ($testphrase, &$testsuccess, $o) {
			
			$o->msg("receiveing : ".json_encode($data));
			
			if($data['data']==$testphrase)
				$testsuccess = true;
		});	


		$c1->connect();
		$c2->connect();


		if(!$c1->is_connected || !$c2->is_connected)
		{
			$this->msg(print_r([$c1->errors, $c2->errors], true));
			$this->msg("Test failed. cant connect\n");
			
			return false;
		}


		$seconds_to_wait = 20;

		while($seconds_to_wait > 0){
			$c1->heartBeat();
			$c2->heartBeat();

			sleep(1);//
			$seconds_to_wait -= 1;
			
			if($testsuccess){
				$this->msg('Test successfull');
				return true;
			}
		}

		$this->msg('Failed. timed out');
		return false;		
	}
	
	function testIrcApp()
	{	
		if(!isset($this->params['test_irc_app']))
			return true;
		
		if(!$this->testIrcAppConnect()){
			$host = GW::s('WSS/HOSTNAME');
			mail(GW::s('REPORT_ERRORS'), "Websocket (host: $host) test failed",'time: :'.date('y-m-d H:i:s')."\n no error details ");
			
			$this->killOldInstance();
		}
	}
	
	function __construct() {
		
		$this->registerEvent("BEFORE_UPDATE_PID_FILE", [$this,'testIrcApp']);
		
		parent::__construct();
	}
	
	
	function testErrorReporting()
	{
		calllnotexistingfuncionnn();
	}
	
	function init()
	{
		
		$this->db = GW::db();

		
		$this->registerEvent("ON_ERROR", function($error){
			$host = GW::s('WSS/HOSTNAME');
			mail(GW::s('REPORT_ERRORS'), "Websocket $host error", json_encode($error, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) );
		});
		
		if (isset($this->params['help']))
			$this->help();
		
		

		$this->error_log_file = GW::s('REPOS_DIR').'error_log';
			
		$this->timeMessage();
		$this->versionMessage();


		$hostname = GW::s('WSS/HOSTNAME');
		$port = GW::s('WSS/PORT');

		$this->msg("Creating server wss://$hostname:$port");

		$this->server = new \WebSocket\Server('0.0.0.0', $port, GW::s('WSS/SSL_OPT/ENABLED'), $this, ['ssl_options' => GW::s('WSS/SSL_OPT')]);



		// server settings:
		$this->server->setMaxClients(GW::s('WSS/MAX_CLIENTS'));

		//not effective
		$this->server->setCheckOrigin(false);
		//$this->server->setAllowedOrigin($allowed_origin);

		$this->server->setMaxConnectionsPerIp(GW::s('WSS/MAX_CONNECTIONS_PER_IP'));
		$this->server->setMaxRequestsPerMinute(GW::s('WSS/MAX_REQUESTS_PER_MINUTE'));

		// Hint: Status application should not be removed as it displays usefull server informations:
		//$server->registerApplication('status', \WebSocket\Application\StatusApplication::getInstance());
		$this->server->registerApplication('irc', \WebSocket\Application\IRCApplication::getInstance());
		\WebSocket\Application\IRCApplication::getInstance()->init();

		$this->registerInnerMethod('versionCheck', '5');
		//$this->registerInnerMethod('testErrorReporting', '30');

		
		
		$cnt = 0;

		while (1) {
			
			$this->server->run();

			$cnt++;
			usleep(100);

			if ($cnt > 100) {
				$this->server->applications['irc']->processQueued();
				
				$this->readStdInInput();
				
				$this->msg('.');
				$this->processTimers();

				$cnt = 1;
			}
		}
	}

	function beforeQuit()
	{
		echo "Bye\n";
		
		if($this->server)
			$this->server->close();
		sleep(1);
	}

	function quit($exit = 1)
	{
		$this->msg('Initiating shutdown');
		
		
		//if($this->server)
		//	$this->server->close();
		
		$this->msg('server closed. beye');
		

		parent::quit($exit);
	}
	
	
/*
	function restartCmdEx($cmd)
	{
		return "screen -S ws_server $cmd";
	}
 * 
 */	
}

