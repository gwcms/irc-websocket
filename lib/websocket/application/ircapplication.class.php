<?php

namespace WebSocket\Application;

/**
 * Websocket-Server irc app
 * 
 * @author Vidmantas Norkus vidmantas.norkus@gw.lt
 */
class IRCApplication extends Application
{

	private $_clients = array();
	private $_filename = '';
	private $channels = [];
	public $controller;
	private $users = [];
	private $username_id_map = [];
	private $lastping = [];
	public $verbose_level = 0;
	
	public $queued_actions = [];

	function __construct()
	{
		parent::__construct();
	}

	function init()
	{
		$this->user0 = \GW_User::singleton();
		$this->chan0 = \GW_Channel::singleton();


		$this->msg('Count users: ' . $this->user0->count('removed=0'));
		$this->msg('Count channels: ' . $this->chan0->count('removed=0'));
	}

	public function onConnect($client)
	{

		if($client->authorization)
		{
			$userpass = explode(':', $client->authorization);
			
			$this->msg("On connect authorisation user: ".$userpass[0]);			
			
			//print_r($userpass);
			
			if(count($userpass) == 2)
			{
				$data = ['username' => $userpass[0], 'pass' => $userpass[1]];
								
				if(!$this->_actAuthorise(['action'=>'authorise', 'data' => $data], $client))
				{
					//$client->sendHttpResponse(401);
					
					//$client->close(1800);
					
				}
			}
				
			
		}

		$this->_clients[$client->id] = $client;
	}


	public function getInfo()
	{
		return [
			'channels' => $this->channels,
			'users' => $this->users,
			'clients_count' => count($this->_clients)];
	}

	public function onDisconnect($client, $exitcode)
	{

		$this->msg("Ext code $exitcode", $client);

		if(!$client->user)
			return true;
		//actions for authorised users
		
		//remove from users
		unset($this->users[$client->user->id]->clients[$client->id]);
		unset($this->_clients[$client->id]);
		
		//$this->infoChannels();
		//$this->infoUsers();
		
		$this->msg($client->user->username." disconnecting instances still connected: ".count($this->users[$client->user->id]->clients));
		
		if(count($this->users[$client->user->id]->clients) == 0){
			
			unset($this->username_id_map[$client->user->username]);

			$this->leaveAllChannels($client->user->id);
			unset($this->users[$client->user->id]);
		}
			
		
		//$this->infoChannels();
		//$this->infoUsers();
	}
	
	public function leaveAllChannels($userid)
	{
		foreach($this->channels as $channel => $chanobj)
		{
			if($this->isUserIDJoinedChan($userid, $chanobj)){
				$this->leaveChannel($userid, $chanobj);
			}
		}
	}
	
	public function leaveChannel($userid, $chanobj){

		$user = $this->users[$userid];
		
		
		unset($chanobj->members[$userid]);
		
		$this->sendToAll($chanobj, ['action'=>'leavechan','user'=> $user->username ,'channel'=>$chanobj->name]);		
	}
	
	function infoChannels()
	{
		$info = [];
		
		foreach($this->channels as $channel => $chanobj)
		{
			$info[$channel] = count($chanobj->members);
		}
		
		echo json_encode($info, JSON_PRETTY_PRINT);
	}
	
	function infoUsers()
	{
		$info = [];
		
		foreach($this->users as $userid => $instances)
		{
			$info[$userid] = count($instances);
		}
		
		echo json_encode($info, JSON_PRETTY_PRINT);
	}

	function getUseridByUser($username)
	{
		return $this->username_id_map[$username];
	}

	/*
	private function unsubscribeAllChannels($client)
	{
		$cid = $client->getClientId();

		foreach ($this->channels as $channelname => $channel)
			foreach ($channel as $cclient => $x)
				if ($cclient == $cid) {
					unset($this->channels[$channelname][$cid]);
					$this->channelCountChange($channelname);

					$this->msg("#$channelname leaving", $client);
				}
	}
	 * 
	 */


	private function _actCreateUser($payload, $client)
	{
		$userdata = $payload['data'];

		$valid_expires = ['1 year', '6 month', '30 day', '24 hour'];

		if (!in_array($userdata['expires'], $valid_expires)) {
			$userdata['expires'] = $valid_expires[0];
			$this->sendEncoded0($client, ['data' => 'Valid expires: ' . implode(', ', $valid_expires) . '. Changed to: ' . $userdata['expires']]);
		}

		$user = $this->user0->createNewObject([
			'username' => $userdata['username'],
			'pass_new' => $userdata['pass'],
			'expires' => $userdata['expires'],
			'reg_ip' => $client->ip
		]);
		
		//set admin user id
		if($client->user && $client->user->id)
		{
			$user->adm_user_id = $client->user->id;
		}

		$user->setRegisterValidators();
		$user->prepareSave();
		$user->validate();

		if ($user->errors) {
			$this->reply($payload, $client, ['data' => 'FAIL', 'datarec' => $payload, 'errors' => $user->errors]);
		} else {
			$user->insert();

			$this->reply($payload, $client, ['data' => 'SUCCESS', 'userid' => $user->id]);
		}
	}
	
	//temp pass is used when real pass is insecure to reveal - for example autologin
	
	function _actSetTempPass($payload, $client)
	{
		$errors = false;
		
		if (!$client->user) {
			$errors[] = 'Only authorised user can do that';
			GOTO sFinish;
		}
		
		$data = $payload['data'];
		
		
		if(isset($data['username'])){
			
			if($otheruser = $this->user0->find(['username=? AND adm_user_id=?', $data['username'], $client->user->id])){
				$user = $otheruser;
			}else{
				$errors = "User not found";
				goto sFinish;
			}
			
			
		}else{
			$user = $client->user;
		}
		
		
		$user->set('temp_pass', md5($data['temp_pass']));
		
		if(preg_match('/\d{4}-\d{2}-\d{2}/', $data['temp_pass_expires']))
		{
			$user->set('temp_pass_expires', $data['temp_pass_expires']);
		}else{
			$user->set('temp_pass_expires', date('Y-m-d H:i:s',strtotime($data['temp_pass_expires'])));
		}
				
		
		$user->updateChanged();
		
		
		sFinish:
		if ($errors) {
			$this->reply($payload, $client, ['data' => 'FAIL', 'datarec' => $payload, 'errors' => $errors]);
		} else {
			$this->reply($payload, $client, ['data' => 'SUCCESS']);
		}		
	}
	
	//patikrinta ar paduotas slaptazodis yra temppass formato, ar yra galiojantis ir galu gale ar sutampa
	function checkTempPass($user, $pass)
	{
		//print_r(['temp_pass_check'=>[$user->temp_pass_expires,date('Y-m-d H:i:s'),$user->username,$pass]]);
		
		if($user->temp_pass_expires < date('Y-m-d H:i:s'))
			return false;
						
		if($user->temp_pass == md5($pass)){
			//print_r(['temp_pass_check'=>[$user->username,$pass]]);
			return true;
		}
		
		return false;
	}
	
	
	private function _actAuthorise($payload, $client)
	{
		$userdata = $payload['data'];

		$user = $this->user0->find(['username=?', $userdata['username']]);

		//galima autorizuotis dviem slaptazodziais, pastoviu arba laikinu
		if (!$user || !$user->checkPass($userdata['pass']) && !$this->checkTempPass($user, $userdata['pass'])) {
			$user = (object) ['errors' => ['Oops! wrong username / password']];
		} else {
			$user->last_ip = $client->ip;
			$user->login_count = $user->login_count + 1;
			$user->last_login = date('Y-m-d H:i:s');
			$user->updateChanged();
			
			
			//do application stuff
			
			$client->user = $user;
			
			if(!isset($this->users[$user->id]))
				$this->users[$user->id] = $user;	
			
			$this->users[$user->id]->clients[$client->id]=$client;
			
			$this->username_id_map[$user->username]=$user->id;
		}
		

		
		

		if ($user->errors) {
			$this->reply($payload, $client, ['data' => 'FAIL', 'datarec' => $payload, 'errors' => $user->errors]);
			return false;
		} else {
			$this->reply($payload, $client, ['data' => 'SUCCESS', 'userid' => $user->id, 'user'=>$user->username]);
			return true;
		}
	}

	private function _actCreateChan($payload, $client)
	{
		$errors = [];

		if (!$client->user) {
			$errors[] = 'Only authorised user can create channels';
			GOTO sFinish;
		}

		$vals = $payload['data'];
		$vals['expires'] = (isset($vals['expires'])) ? $vals['expires'] : '1 year';

		$valid_expires = ['1 year', '6 month', '30 day', '24 hour'];

		if (!in_array($vals['expires'], $valid_expires)) {
			$vals['expires'] = $valid_expires[0];
			$this->sendEncoded0($client, ['data' => 'Valid expires: ' . implode(', ', $valid_expires) . '. Changed to: ' . $vals['expires']]);
		}

		$chan = \GW_Channel::singleton()->createNewObject([
			'name' => $vals['channel'],
			'pass_new' => $vals['pass'],
			'user_id' => $client->user->id,
			'reg_ip' => $client->ip
		]);

		$chan->setRegisterValidators();
		$chan->prepareSave();
		$chan->validate();

		if ($chan->errors) {
			$errors = array_merge($errors, $chan->errors);
		} else {
			$chan->insert();
		}

		sFinish:
		if ($errors) {
			$this->reply($payload, $client, ['data' => 'FAIL', 'datarec' => $payload, 'errors' => $errors]);
		} else {
			$this->reply($payload, $client, ['data' => 'SUCCESS', 'chanid' => $chan->id]);
		}
	}

	private function _actChanList($payload, $client)
	{
		$channels = $this->chan0->findAll('removed=0',['key_field'=>'name','order'=>'last_join DESC']);
		
		$info = [];
		
		
		foreach($channels as $channame => $chan)
		{
			$info[] = ['channel'=>$chan->name, 'private'=>$chan->isPrivate(), 'members'=> (isset($this->channels[$chan->name])? count($this->channels[$chan->name]->members) : 0)];
		}
			
			
		$this->reply($payload, $client, ['data' => 'SUCCESS', 'data' => $info]);
	}	
	
	private function loadChan($channame)
	{
		if (isset($this->channels[$channame]))
			$chanobj = $this->channels[$channame];
		elseif ($chanobj = \GW_Channel::singleton()->find(['name=? AND removed=0', $channame])) {
			$this->channels[$channame] = $chanobj;
		}

		return $chanobj;
	}

	private function isClientJoinedChan($client, $chanobj)
	{
		return $this->isUserIdJoinedChan($client->user->id, $chanobj);
	}
	
	private function isUserJoinedChan($user, $chanobj)
	{
		return $this->isUserIdJoinedChan($user->id, $chanobj);
	}
	
	private function isUserIdJoinedChan($userid, $chanobj)
	{
		return isset($chanobj->members[$userid]);
	}

	private function chanListUsers($chanobj)
	{
		$list = [];

		foreach ($chanobj->members as $userid => $x) {
			$list[] = ['name' => $this->users[$userid]->username, 'isadmin' => $chanobj->isAdmin($this->users[$userid]->id)];
		}

		return $list;
	}

	private function _actJoinChan($payload, $client)
	{
		$errors = [];

		if (!$client->user) {
			$errors[] = 'Only authorised user can join channels';
			GOTO sFinish;
		}


		$chan = $payload['data'];
		$channame = $chan['channel'];
		$chanpass = isset($chan['pass']) ? $chan['pass'] : false;

		$chanobj = $this->loadChan($channame);


		if (!$chanobj) {
			$errors[] = "Channel not found";
			GOTO sFinish;
		}

		if($chanobj->pass)
		{
			if($chanobj->isAdmin($client->user->id))
			{
				GOTO sFinish;
			}
			
			if (!$chanpass) {
				$errors[] = "Channel is private";
				GOTO sFinish;
			}

			if (!$chanobj->checkPass($chanpass)) {
				$errors[] = "Incorect channel password";
				GOTO sFinish;
			}			
		}
		
		//print_r(['joined', $chanobj, $client->user->username]);		

		sFinish:	
			
		if ($errors) {
			$this->reply($payload, $client, ['data' => 'FAIL', 'datarec' => $payload, 'errors' => $errors]);
		} else {
			$this->queued_actions[] = ['joined', $chanobj, $client->user->username];
			
			$chanobj->members[$client->user->id] = 1;
			$this->msg("added to members {$client->user->id} ".json_encode($chanobj->members));
			
			$this->reply($payload, $client, ['data' => 'SUCCESS', 'chanid' => $chanobj->id, 'channel'=>$channame]);
		}
	}
	
	private function _actLeaveChan($payload, $client)
	{
		$errors = [];

		if (!$client->user) {
			$errors[] = 'Only authorised user can join channels';
			GOTO sFinish;
		}


		$chan = $payload['data'];
		$channame = $chan['channel'];

		$chanobj = $this->loadChan($channame);
		

		if (!$chanobj) {
			$errors[] = "Channel not found";
			GOTO sFinish;
		}
		
		if($this->isClientJoinedChan($client, $chanobj)){
			$this->leaveChannel($client->user->id, $chanobj);
		}
		

		sFinish:	
			
		if ($errors) {
			$this->reply($payload, $client, ['data' => 'FAIL', 'datarec' => $payload, 'errors' => $errors]);
		} else {
			$this->queued_actions[] = ['LeaveChan', $chanobj, $client->user->username];
						
			$this->reply($payload, $client, ['data' => 'SUCCESS', 'chanid' => $chanobj->id, 'channel'=>$channame]);
		}
	}	
	
	function processQueuedJoined($chanobj, $username)
	{		
		$chanobj->join_count = $chanobj->join_count+1;
		$chanobj->last_join = date('Y-m-d H:i:s');

		$chanobj->updateChanged();
		$this->sendToAll($chanobj, ['action'=>'joinchan','user'=> $username ,'channel'=>$chanobj->name]);
	}
	
	function processQueuedLeaveChan($chanobj, $username)
	{		
		$this->sendToAll($chanobj, ['action'=>'leavechan','user'=> $username ,'channel'=>$chanobj->name]);
	}
		
	
	function processQueued()
	{
		if($this->queued_actions)
		{
			$actionData = array_pop($this->queued_actions);
			$method = array_shift($actionData);
			
			//print_r(['method'=>"processQueued".$method, 'args'=>$actionData]);
			call_user_func_array([$this, "processQueued".$method] , $actionData);
		}
	}
	
	
	

	private function _actInfoChan($payload, $client)
	{
		$errors = [];

		if (!$client->user) {
			$errors[] = 'Only authorised user can request channel info';
			GOTO sFinish;
		}

		$channame = $payload['data']['channel'];

		if (!($chanobj = $this->loadChan($channame, $client))) {
			$errors[] = "Channel not found";
			GOTO sFinish;
		}

		
		$isjoined = $this->isClientJoinedChan($client, $chanobj);

		if ($chanobj->isPrivate() && !$isjoined) {
			$errors[] = "Channel is private, you must be logged in to get info";
			GOTO sFinish;
		}

		$info = [
			'channel' => $channame,
			'id' => $chanobj->id,
			'members_count' => count($chanobj->members)
		];

		if ($isjoined)
			$info['listusers'] = $this->chanListUsers($chanobj);


		sFinish:
		if ($errors) {
			$this->reply($payload, $client, ['data' => 'FAIL', 'datarec' => $payload, 'errors' => $errors]);
		} else {
			$this->reply($payload, $client, ['data' => 'SUCCESS', 'info' => $info]);
		}
	}
	
	private function _actMessageChan($payload, $client)
	{
		$errors=[];
		
		if (!$client->user) {
			$errors[] = 'Only authorised user can send channel message';
			GOTO sFinish;
		}		
		
		$channame = $payload['data']['channel'];
		$msg = $payload['data']['message'];
		
		if (!($chanobj = $this->loadChan($channame, $client))) {
			$errors[] = "Channel not found";
			GOTO sFinish;
		}
		
		
		
		if($chanobj->allow_not_joined_msg || $this->isClientJoinedChan($client, $chanobj) )
		{
			//echo "chanmsg #$channame {$client->id}({$client->user->username}): $msg\n";
			$this->sendToAll($chanobj, ['action'=>'messagechan','user'=> $client->user->username ,'channel'=>$channame,'data'=>$msg], $client->user);
			print_r(['action'=>'messagechan','user'=> $client->user->username ,'channel'=>$channame,'data'=>$msg]);
		}else{
			$errors[] = "You must be joined to send channel message";
		}
		
		
		
		sFinish:
		if ($errors) {
			$this->reply($payload, $client, ['data' => 'FAIL', 'datarec' => $payload, 'errors' => $errors]);
		} else {
			
			if(isset($payload['data']['request_reply']) && $payload['data']['request_reply']==1)
				$this->reply($payload, $client, ['data' => 'SUCCESS']);
		}
	}	
	
	private function _actMessagePrivate($payload, $client)
	{
		$errors = [];
		
		if (!$client->user) {
			$errors[] = 'Only authorised user can send private message';
			GOTO sFinish;
		}
		
		$user = $payload['data']['user'];
		$msg = $payload['data']['message'];	
		
		
		if(!($userid=$this->getUseridByUser($user)))
		{
			$errors[] = 'User is not available';
		}else{
			$send_payload =[
					'action'=>'messageprivate',
					'user'=> $client->user->username,
					'data'=>$msg
					];
			
			if(isset($payload['data']['privmsgid']))
				$send_payload['privmsgid'] = $payload['data']['privmsgid'];
			
			if(isset($payload['data']['replytoid']))
				$send_payload['replytoid'] = $payload['data']['replytoid'];
			
			print_r($payload);
			
			foreach($this->users[$userid]->clients as $sendc){
				$this->sendEncoded0($sendc, $send_payload);
			}
		}
		
		
		
		sFinish:
		if ($errors) {
			$this->reply($payload, $client, ['data' => 'FAIL', 'datarec' => $payload, 'errors' => $errors]);
		} else {
			$this->reply($payload, $client, ['data' => 'SUCCESS']);
		}		
	}




	private function _actPing($payload, $client)
	{
		$this->lastping[$client->getClientId()] = time();

		$this->reply($payload, $client, ['data' => memory_get_usage()]);
	}


	/**
	 * can pass client object or client id as argument
	 */
	function clientTitle($client)
	{
		$cid = is_string($client) ? $client : $client->getClientId();

		$nick = substr($cid, 0, 8);

		if (isset($this->nicknames[$cid]))
			$nick = $this->nicknames[$cid];

		return $nick;
	}





	public function onData($data, $client)
	{

		//echo "Data rcv: $data";
		if ($this->verbose_level > 1)
			$this->msg($data);


		$decodedData = $this->_decodeData($data);
		if ($decodedData === false) {
			// @todo: invalid request trigger error...
		}

		$actionName = '_act' . $decodedData['action'];

		//$this->msg($decodedData['action'], $client);

		if (method_exists($this, $actionName)) {
			$this->$actionName($decodedData, $client);
		} else {
			$this->msg("Method $actionName not exists", $client);
		}
	}

	/*
	public function onBinaryData($data, $client)
	{
		$filePath = substr(__FILE__, 0, strpos(__FILE__, 'server')) . 'tmp/';
		$putfileResult = false;
		if (!empty($this->_filename)) {
			$putfileResult = file_put_contents($filePath . $this->_filename, $data);
		}
		if ($putfileResult !== false) {

			$msg = 'File received. Saved: ' . $this->_filename;
		} else {
			$msg = 'Error receiving file.';
		}
		$client->send($this->_encodeData('echo', $msg));
		$this->_filename = '';
	}
	 * 
	 */

	private function _actEcho($text, $chan)
	{
		$this->sendToAll('echo', $text);
	}

	
	

	
	
	/*
	private function _actMsg($data, $chan, $client)
	{
		$this->sendToAllExceptMe('msg', $chan, $data, $client->getClientId());
	}*/

	/*
	private function _actCmd($data, $chan, $client)
	{
		$this->sendToAllExceptMe('cmd', $chan, $data, $client->getClientId());
	}
	 */

	
	
	function sendToAll($channel, $payload, $sender=false)
	{
		$chanobj = is_string($channel) ? $this->loadChan($channel) : $channel;
		
		//print_r(['users'=>$this->users, 'members'=>$chanobj->members]);
		
		$data = json_encode($payload);
		
		foreach($chanobj->members as $userid => $x)
			
			foreach($this->users[$userid]->clients as $client){
			
				if(($sender && $client->user->id != $sender->id) || !$sender){ 
					
					
					//dont send to self
					$client->send($data);
				}
			}
	}


	function sendEncoded($client, $action, $channel, $data)
	{
		if ($client)
			$client->send($this->_encodeData($action, $channel, $data));
	}

	function sendEncoded0($client, $payload)
	{
		if ($client)
			$client->send(json_encode($payload));
	}

	/*
	private function _actionSetFilename($filename)
	{
		if (strpos($filename, '\\') !== false) {
			$filename = substr($filename, strrpos($filename, '\\') + 1);
		} elseif (strpos($filename, '/') !== false) {
			$filename = substr($filename, strrpos($filename, '/') + 1);
		}
		if (!empty($filename)) {
			$this->_filename = $filename;
			return true;
		}
		return false;
	}
	 */


	public function msg($msg, $client = false)
	{
		if ($client)
			$msg = "<" . $this->clientTitle($client) . "> " . $msg;

		$this->controller->msg($msg);
	}

	function reply($payloadReceived, $client, $payloadSend)
	{
		if (isset($payloadReceived['msgid']))
			$payloadSend['msgid'] = $payloadReceived['msgid'];

		if (isset($payloadReceived['action']))
			$payloadSend['action'] = $payloadReceived['action'] . 'Reply';

		$this->sendEncoded0($client, $payloadSend);
	}
}
