<?php

class GW_Channel extends GW_Data_Object
{

	public $table = 'channels';
	public $validators = [
		'name' => Array('gw_string', Array('min_length' => 3, 'max_length' => 20, 'required' => 1))
	];
	public $ignore_fields = Array('pass_old' => 1, 'pass_new' => 1, 'pass_new_repeat' => 1);
	public $members = []; //for use in irc app

	function setRegisterValidators()
	{
		$this->validators['unique_name'] = 1;
		$this->validators['name_chars'] = 1;
		
		if($this->get('pass_new'))
			$this->validators['pass_new'] = Array('gw_string', Array('min_length' => 6, 'max_length' => 200));
	}

	function eventHandler($event, &$context_data = [])
	{
		switch ($event) {
			case 'PREPARE_SAVE':
				if (isset($this->content_base['pass_new']) && $this->content_base['pass_new'])
					$this->set('pass', $this->cryptPass($this->get('pass_new')));

				break;
		}

		parent::eventHandler($event, $context_data);
	}

	function validate()
	{
		parent::validate();


		if (isset($this->validators['name_chars'])) {
			if (preg_replace('/[^a-zA-Z0-9-_]/i', '', $this->get('name')) != $this->get('name'))
				$this->errors['username'] = ['/G/CHANNEL/INVALID_CHARACTERS_CONTAINS_USERNAME', 'Valid chars: a-z,A-Z,0-9,-,_'];
		}

		if (isset($this->validators['unique_name']))
			if ($this->count(Array('name=? AND removed=0', $this->get('name'))))
				$this->errors['name'] = '/G/CHANNEL/NAME_TAKEN';

		return $this->errors ? false : true;
	}

	function cryptPass($pass, $salt = null)
	{
		if ($pass) {//cant be empty
			return $salt ? crypt($pass, $salt) : crypt($pass, 'salt');
		} else {
			//d::dumpas('Password cant be empty');
			die('Password cant be empty');
		}
	}

	function checkPass($pass)
	{
		if (!$pass)
			return false;

		$tmp = $this->get('pass');

		return $tmp == $this->cryptPass($pass, $tmp);
	}

	function isPrivate()
	{
		return strlen($this->get('pass')) > 0 ? true : false;
	}

	function isAdmin($user_id)
	{
		return $this->get('user_id') == $user_id || strpos(','.$this->get('admin_ids').',', ",$user_id,")!==false;
	}
}
