<?php

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
class UserIdentity extends CUserIdentity
{
	public $userId;

	/**
	 * Authenticates a user.
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate()
	{
		$user = RalmUser::model()->findByAttributes(array(
			'username' => $this->username,
		));

		if($user === null)
		{
			$this->errorCode = self::ERROR_USERNAME_INVALID;
			return false;
		}

		if((int)$user->status !== 1)
		{
			$this->errorCode = self::ERROR_PASSWORD_INVALID;
			return false;
		}

		$plainPasswordMatches = ($user->password === $this->password);
		$sha1PasswordMatches = ($user->password === sha1($this->password));

		if(!$plainPasswordMatches && !$sha1PasswordMatches)
		{
			$this->errorCode = self::ERROR_PASSWORD_INVALID;
			return false;
		}

		if($plainPasswordMatches && !$sha1PasswordMatches)
		{
			$user->password = sha1($this->password);
			$user->save(false, array('password'));
		}

		$this->userId = $user->id;
		$this->errorCode = self::ERROR_NONE;
		return true;
	}
}