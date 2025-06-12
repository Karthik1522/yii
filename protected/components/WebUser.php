<?php


use MongoDB\BSON\ObjectId;

class WebUser extends CWebUser
{
	private $_model = null;

	public function getRole()
	{
        if ($this->isGuest) {
            return null;
        }
		return $this->getState("role");
	}

	public function getName()
	{
		if ($this->isGuest) {
            return null;
        }
		return $this->getState("username");
	}

	public function hasRole($roleName)
	{
		if ($this->isGuest) {
            return false;
        }

		$userRole = $this->getRole();

		if (is_array($roleName)) {
            return in_array($userRole, $roleName);
        }

		return $userRole === $roleName;
	}

	public function isAdmin()
	{
		return $this->hasRole("admin");
	}

	public function isStaff()
	{
		return $this->hasRole("staff");
	}

	public function isManager()
	{
		return $this->hasRole('manager');
	}

	// public function getModel()
    // {
    //     if (!$this->isGuest && $this->_model === null) {
    //         // Ensure $this->id (which is _id from MongoDB) is correctly used
    //         // $this->id is set by CUserIdentity::getId()
    //         if ($this->id) {
    //              try {
    //                 $this->_model = User::model()->findByPk(new ObjectId($this->id));
    //             } catch (Exception $e) {
    //                 Yii::log("Error fetching user model by ID: {$this->id}. Error: " . $e->getMessage(), CLogger::LEVEL_WARNING);
    //                 $this->_model = null;
    //             }
    //         }
    //     }
    //     return $this->_model;
    // }

}