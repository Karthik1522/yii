<?php

use Mockery\Adapter\Phpunit\MockeryTestCase;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex; // Assuming YiiMongoDbSuite or your setup uses this

// User.php and EMongoDocument.php are autoloaded

class UserTest extends MockeryTestCase
{
    /** @var MongoMock */
    private $mongoMock;
    /** @var User */
    private $user;
    /** @var \Mockery\MockInterface */
    private $appMock; // To mock Yii::app()

    protected function mockeryTestSetUp(): void
    {
        parent::mockeryTestSetUp();
        $this->mongoMock = new MongoMock();

        // Mock Yii::app() and its params
        // We need to ensure that Yii::app() returns our mock application object.
        // If Yii::app() is already initialized by a bootstrap, this is tricky.
        // A common approach is to set a test application instance.

        $paramsMock = new CAttributeCollection(); // Yii's way of handling params usually
        $paramsMock['roles'] = ['admin' => 'Administrator', 'staff' => 'Staff Member', 'editor' => 'Editor'];
        $paramsMock['bcryptCost'] = 4; // Use the lowest possible cost for fast tests

        $this->appMock = Mockery::mock('CWebApplication'); // Or CConsoleApplication if appropriate
        $this->appMock->shouldReceive('getParams')->andReturn($paramsMock)->byDefault();
        $this->appMock->shouldReceive('getComponent')->with('mongodb')->andReturn(Mockery::mock(['getConnection' => null]))->byDefault(); // Prevent DB connection errors if EMongoDocument tries to connect early

        // If Yii::app() is already set by a bootstrap, replacing it can be problematic.
        // The ideal way is if your bootstrap allows setting a test application.
        // For now, let's assume we can set it or Yii::app() will pick up our mock.
        // This might need adjustment based on your specific Yii testing setup.
        if (Yii::app() === null) {
            Yii::setApplication($this->appMock);
        } else {
            // If app exists, we might not be able to easily replace Yii::app()->params globally
            // without more invasive mocking or a proper test application setup in bootstrap.
            // For now, we'll assume the test methods will mock Yii::app() directly if needed.
            // This is a common pain point in Yii 1.1 testing.
            // A robust solution involves a test-specific Yii entry script (e.g., index-test.php)
            // that allows configuring a test application with mocked components.
        }


        $this->mongoMock->mock(User::class); // For unique validators (mocks User::model()->count())

        $this->user = new User();
    }

    protected function mockeryTestTearDown(): void
    {
        // If Yii::setApplication was used with a mock, try to restore the original or nullify
        // Yii::setApplication(null); // Or restore original if you have it
        $this->mongoMock->close();
        parent::mockeryTestTearDown();
    }

    private function setAppParams()
    {
        // Helper to ensure our mocked params are used by Yii::app()
        // This is a workaround if global Yii::setApplication in setUp is problematic
        $paramsMock = new CAttributeCollection();
        $paramsMock['roles'] = ['admin' => 'Administrator', 'staff' => 'Staff Member', 'editor' => 'Editor'];
        $paramsMock['bcryptCost'] = 4;

        $currentApp = Yii::app();
        if ($currentApp && method_exists($currentApp, 'setParams')) { // CApplication has setParams
            $currentApp->setParams($paramsMock);
        } elseif ($currentApp) { // Fallback if setParams isn't available or direct access
            $currentApp->params = $paramsMock;
        } else { // If no app, create a simple mock for params access
            $appMock = Mockery::mock(stdClass::class);
            $appMock->params = $paramsMock;
            Yii::setApplication($appMock); // This might fail if app already exists
        }
    }


    public function testGetCollectionName()
    {
        $this->assertEquals('users', $this->user->getCollectionName());
    }

    public function testModelStaticMethod()
    {
        $this->assertInstanceOf(User::class, User::model());
    }

    public function testDefaultRoleIsStaff()
    {
        $this->assertEquals('staff', $this->user->role);
    }

    public function testAttributeLabelsAreDefined()
    {
        $this->assertIsArray($this->user->attributeLabels());
        $this->assertArrayHasKey('username', $this->user->attributeLabels());
    }

    // --- Validation Tests ---
    public function testValidation_RequiredFields_DefaultScenario()
    {
        $this->user->setScenario('default'); // Or 'update' if that's your default for existing
        $this->user->username = null;
        $this->user->email = null;
        $this->user->role = null;

        $this->assertFalse($this->user->validate());
        $this->assertTrue($this->user->hasErrors('username'));
        $this->assertTrue($this->user->hasErrors('email'));
        $this->assertTrue($this->user->hasErrors('role'));
    }

    public function testValidation_PasswordRequired_OnInsertScenario()
    {
        $this->user->setScenario('insert');
        $this->user->username = 'test';
        $this->user->email = 'test@example.com';
        $this->user->role = 'staff'; // Satisfy other rules
        $this->user->password = null;

        $this->assertFalse($this->user->validate(['password']));
        $this->assertTrue($this->user->hasErrors('password'));
    }

    public function testValidation_PasswordRepeatCompare_OnInsertScenario()
    {
        $this->user->setScenario('insert');
        $this->user->username = 'test';
        $this->user->email = 'test@example.com';
        $this->user->role = 'staff';
        $this->user->password = 'pass123';
        $this->user->password_repeat = 'pass456'; // Mismatch

        $this->assertFalse($this->user->validate(['password_repeat']));
        $this->assertTrue($this->user->hasErrors('password_repeat'));
        $this->assertContains("Passwords don't match", $this->user->getErrors('password_repeat'));

        $this->user->clearErrors();
        $this->user->password_repeat = 'pass123'; // Match
        $this->assertTrue($this->user->validate(['password_repeat']));
        $this->assertFalse($this->user->hasErrors('password_repeat'));
    }

    public function testValidation_UsernameUnique_FailsWhenTaken()
    {
        $this->setAppParams(); // Ensure params are set for role validation
        $this->user->setScenario('insert');
        $this->user->username = 'existinguser';
        $this->user->email = 'new@example.com';
        $this->user->role = 'staff';
        $this->user->password = 'pass';
        $this->user->password_repeat = 'pass';

        $usernameRegex = new Regex('^'.preg_quote('existinguser').'$', 'i');
        $this->mongoMock->mockCount(User::class, 1, ['username' => $usernameRegex]);

        $this->assertFalse($this->user->validate(['username']));
        $this->assertTrue($this->user->hasErrors('username'));
        $this->assertContains('This username is already taken.', $this->user->getErrors('username'));
    }

    public function testValidation_EmailUnique_FailsWhenTaken()
    {
        $this->setAppParams();
        $this->user->setScenario('insert');
        $this->user->username = 'newuser';
        $this->user->email = 'existing@example.com';
        $this->user->role = 'staff';
        $this->user->password = 'pass';
        $this->user->password_repeat = 'pass';

        // Mock unique validator for username to pass
        $usernameRegex = new Regex('^'.preg_quote('newuser').'$', 'i');
        $this->mongoMock->mockCount(User::class, 0, ['username' => $usernameRegex]);

        // Mock unique validator for email to fail
        $emailRegex = new Regex('^'.preg_quote('existing@example.com').'$', 'i');
        $this->mongoMock->mockCount(User::class, 1, ['email' => $emailRegex]);


        $this->assertFalse($this->user->validate(['email']));
        $this->assertTrue($this->user->hasErrors('email'));
        $this->assertContains('This email is already taken.', $this->user->getErrors('email'));
    }

    public function testValidation_Role_Invalid()
    {
        $this->setAppParams(); // Crucial for Yii::app()->params['roles']
        $this->user->setScenario('default');
        $this->user->username = 'test';
        $this->user->email = 'test@example.com'; // Satisfy other rules
        $this->user->role = 'invalid_role';

        $this->assertFalse($this->user->validate(['role']));
        $this->assertTrue($this->user->hasErrors('role'));
        $this->assertContains('Invalid role selected.', $this->user->getErrors('role'));
    }

    public function testValidation_Role_Valid()
    {
        $this->setAppParams();
        $this->user->setScenario('default');
        $this->user->username = 'test';
        $this->user->email = 'test@example.com';
        $this->user->role = 'admin'; // Assuming 'admin' is in your mocked params['roles']

        // Mock unique validators to pass
        $usernameRegex = new Regex('^'.preg_quote('test').'$', 'i');
        $this->mongoMock->mockCount(User::class, 0, ['username' => $usernameRegex]);
        $emailRegex = new Regex('^'.preg_quote('test@example.com').'$', 'i');
        $this->mongoMock->mockCount(User::class, 0, ['email' => $emailRegex]);


        $this->assertTrue($this->user->validate(['role']));
        $this->assertFalse($this->user->hasErrors('role'));
    }

    // --- Password Handling ---
    public function testValidatePassword_CorrectPassword()
    {
        $password = 'password123';
        $this->user->password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 4]);
        $this->assertTrue($this->user->validatePassword($password));
    }

    public function testValidatePassword_IncorrectPassword()
    {
        $this->user->password_hash = password_hash('password123', PASSWORD_BCRYPT, ['cost' => 4]);
        $this->assertFalse($this->user->validatePassword('wrongpassword'));
    }

    public function testHashPassword_ReturnsValidHash()
    {
        $this->setAppParams(); // For bcryptCost
        $password = 'mySecurePassword';
        $hash = $this->user->hashPassword($password);
        $this->assertIsString($hash);
        $this->assertTrue(password_verify($password, $hash));
    }

    // --- beforeSave ---
    public function testBeforeSave_NewRecord_SetsTimestampsAndHashesPassword()
    {
        $this->setAppParams(); // For bcryptCost in hashPassword
        $userMock = Mockery::mock(User::class . '[getIsNewRecord, getScenario, parent::beforeSave]')->makePartial()->shouldAllowMockingProtectedMethods();
        $userMock->shouldReceive('getIsNewRecord')->andReturn(true);
        $userMock->shouldReceive('getScenario')->andReturn('insert'); // Or any non-'login' scenario
        $userMock->shouldReceive('parent::beforeSave')->andReturn(true);

        $userMock->password = 'newPassword';
        $userMock->created_at = null;
        $userMock->updated_at = null;
        $userMock->password_hash = null;

        $this->assertTrue($userMock->beforeSave()); // Call protected method for testing

        $this->assertInstanceOf(MongoDate::class, $userMock->created_at);
        $this->assertInstanceOf(MongoDate::class, $userMock->updated_at);
        $this->assertNotNull($userMock->password_hash);
        $this->assertTrue(password_verify('newPassword', $userMock->password_hash));
        $this->assertNull($userMock->password); // Password should be cleared
        $this->assertNull($userMock->password_repeat);
    }

    public function testBeforeSave_ExistingRecord_UpdatesTimestampAndHashesPasswordIfSet()
    {
        $this->setAppParams();
        $userMock = Mockery::mock(User::class . '[getIsNewRecord, getScenario, parent::beforeSave]')->makePartial()->shouldAllowMockingProtectedMethods();
        $userMock->shouldReceive('getIsNewRecord')->andReturn(false);
        $userMock->shouldReceive('getScenario')->andReturn('update'); // Or 'changePassword'
        $userMock->shouldReceive('parent::beforeSave')->andReturn(true);

        $initialCreateTime = new MongoDate(time() - 3600);
        $userMock->created_at = $initialCreateTime;
        $userMock->updated_at = $initialCreateTime;
        $userMock->password = 'updatedPass';
        $userMock->password_hash = 'oldhash'; // Will be overwritten

        $this->assertTrue($userMock->beforeSave());

        $this->assertEquals($initialCreateTime, $userMock->created_at);
        $this->assertInstanceOf(MongoDate::class, $userMock->updated_at);
        $this->assertNotEquals($initialCreateTime->sec, $userMock->updated_at->sec);
        $this->assertNotNull($userMock->password_hash);
        $this->assertNotEquals('oldhash', $userMock->password_hash);
        $this->assertTrue(password_verify('updatedPass', $userMock->password_hash));
        $this->assertNull($userMock->password);
    }

    public function testBeforeSave_LoginScenario_DoesNotHashPassword()
    {
        $this->setAppParams();
        $userMock = Mockery::mock(User::class . '[getIsNewRecord, getScenario, parent::beforeSave]')->makePartial()->shouldAllowMockingProtectedMethods();
        $userMock->shouldReceive('getIsNewRecord')->andReturn(false); // Does not matter much for login
        $userMock->shouldReceive('getScenario')->andReturn('login');
        $userMock->shouldReceive('parent::beforeSave')->andReturn(true);

        $userMock->password = 'somepassword';
        $userMock->password_hash = 'existing_hash';

        $this->assertTrue($userMock->beforeSave());
        $this->assertEquals('existing_hash', $userMock->password_hash); // Hash should not change
        $this->assertEquals('somepassword', $userMock->password); // Password should remain
    }

    public function testBeforeSave_NoPasswordChange_DoesNotRehash()
    {
        $this->setAppParams();
        $userMock = Mockery::mock(User::class . '[getIsNewRecord, getScenario, parent::beforeSave]')->makePartial()->shouldAllowMockingProtectedMethods();
        $userMock->shouldReceive('getIsNewRecord')->andReturn(false);
        $userMock->shouldReceive('getScenario')->andReturn('update');
        $userMock->shouldReceive('parent::beforeSave')->andReturn(true);

        $userMock->password = null; // Password not being changed
        $userMock->password_hash = 'current_secure_hash';

        $this->assertTrue($userMock->beforeSave());
        $this->assertEquals('current_secure_hash', $userMock->password_hash);
    }
}
