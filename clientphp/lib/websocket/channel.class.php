<?php

namespace WebSocket;

class Channel
{
	/**
	 * @var Client
	 */
	private $client;
	public $name;
	public $users = [];
	
	function __construct($client, $name)
	{
		$this->client = $client;
		$this->name = $name;
	}
	
	/**
	 * 
	 * @param string $msg
	 * @param int $wait // miliseconds, 0 - dont wait
	 */
	function say($msg, $wait=false)
	{
		return $this->client->messageChannel($this->name, $msg, $wait);

	}
	
	function usernameF($userdata)
	{
		return ($userdata['isadmin'] ? '@': '') . $userdata['name'];
	}
	
	function listUsers($wait = 1000)
	{
		$msgid = $this->client->writeData('infochan', ['channel' => $this->name]);
		
		if($wait){
			$data = $this->client->waitForResponse($msgid, $wait);
			
			if($data['data']=='SUCCESS') {
				foreach($data['info']['listusers'] as $user)
				{
					$userid = $this->usernameF($user);
					$this->users[$userid]=1;
				}
				
				return $this->users;
			}else{
				$this->users = [];
			}
		}
	}
	
}