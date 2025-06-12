<?php

class User extends EMongoDocument
{
    public $username;
    public $password;
    public $password_repeat;
    public $password_hash;
    public $email;
    public $role = "staff";
    public $last_login_at;
    public $created_at;
    public $updated_at;

    public function getCollectionName()
    {
        return "users";
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function rules()
    {
        return [
            ['username, email, role', 'required'],
            ['password', 'required', 'on' => 'insert, changePassword, register'],
            ['password_repeat', 'compare', 'compareAttribute' => 'password', 'on' => 'insert, changePassword, register', 'message' => "Passwords don't match"],

            ['username', 'length', 'max' => 50],
            ['email', 'email'],

            ['username', 'ext.YiiMongoDbSuite.extra.EMongoUniqueValidator',
                'className' => 'User',
                'attributeName' => 'username',
                'caseSensitive' => false,
                'message' => 'This username is already taken.'
            ],
            ['email', 'ext.YiiMongoDbSuite.extra.EMongoUniqueValidator',
                'className' => 'User',
                'attributeName' => 'email',
                'caseSensitive' => false,
                'message' => 'This email is already taken.'
            ],

            ['role', 'in', 'range' => array_keys(Yii::app()->params['roles']), 'message' => 'Invalid role selected.'],

            ['username, password', 'required', 'on' => 'login'],

            ['created_at, updated_at, last_login_at', 'safe'],
            ['username, email, role, last_login_at', 'safe', 'on' => 'search'],
        ];
    }

    public function attributeLabels()
    {
        return array(
            '_id' => 'ID',
            'username' => 'Username',
            'password' => 'Password',
            'password_repeat' => 'Repeat Password',
            'email' => 'Email',
            'role' => 'Role',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'last_login_at' => 'Last Login At',
        );
    }

    public function validatePassword($password)
    {
        return password_verify($password, $this->password_hash);
    }

    public function hashPassword($password)
    {
        $cost = Yii::app()->params['bcryptCost'] ?? 12;
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $cost]);
    }

    public function beforeSave()
    {
        if (!parent::beforeSave()) {
            return false;
        }

        $now = new MongoDate();

        if ($this->isNewRecord) {
            $this->created_at = $now;
        }

        $this->updated_at = $now;

        if (!empty($this->password) && $this->getScenario() !== 'login') {
            $this->password_hash = $this->hashPassword($this->password);
            $this->password = null;
            $this->password_repeat = null;
        }

        return true;
    }
}
