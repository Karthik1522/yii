<?php

/**
 * UserIdentity represents the data needed to identify a user.
 * It contains the authentication method that checks if the provided
 * data can identify the user.
 */
class UserIdentity extends CUserIdentity
{
    private $_id;

    /**
     * Authenticates a user.
     * This method authenticates the user by checking the username and hashed password.
     * @return boolean whether authentication succeeds.
     */

    public function authenticate()
    {
        $criteria = new EMongoCriteria();
        $criteria->addCond('username', '==', $this->username);
        // If you want to allow login with email too:
        // $criteria->addOrCond('email', '==', strtolower($this->username));

        // var_dump($this);
        // exit;
        $user = User::model()->find($criteria);

        if ($user === null) {
            $this->errorCode = self::ERROR_USERNAME_INVALID;
        } elseif (!$user->validatePassword($this->password)) {
            $this->errorCode = self::ERROR_PASSWORD_INVALID;
        } else {
            $this->_id = (string)$user->_id;
            $this->username = $user->username;
            $this->setState('username', $user->username);
            $this->setState('role', $user->role);
            $user->last_login_at = new MongoDate();

            $this->setState('lastLoginAt', $user->last_login_at);

            // $user->save(false, array('last_login_at'));
            $user->save();

            $this->errorCode = self::ERROR_NONE;
        }
        return !$this->errorCode;
    }

    public function getId()
    {
        return $this->_id;
    }


}
