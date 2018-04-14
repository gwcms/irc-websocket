<?php

class GW_User extends GW_Data_Object
{

	public $table = 'users';
	public $validators = [
		'username' => Array('gw_string', Array('min_length' => 3, 'max_length' => 20, 'required' => 1))
	];
	public $ignore_fields = Array('pass_old' => 1, 'pass_new' => 1, 'pass_new_repeat' => 1);
	public $clients=[];
	

	function setRegisterValidators()
	{
		$this->validators['unique_username'] = 1;
		$this->validators['username_chars'] = 1;
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


		if (isset($this->validators['username_chars'])) {
			if (preg_replace('/[^a-zA-Z0-9-_]/i', '', $this->get('username')) != $this->get('username'))
				$this->errors['username'] = ['/G/USER/INVALID_CHARACTERS_CONTAINS_USERNAME', 'Valid chars: a-z,A-Z,0-9,-,_'];
		}

		if (isset($this->validators['unique_username']))
			if ($this->count(Array('username=? AND removed=0', $this->get('username'))))
				$this->errors['username'] = '/G/USER/USERNAME_TAKEN';

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
}
