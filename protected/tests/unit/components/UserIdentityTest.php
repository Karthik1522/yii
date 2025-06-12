<?php

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class UserIdentityTest extends MockeryTestCase
{
    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function testGetId_ReturnsCorrectId()
    {
        $userIdentity = new UserIdentity('testuser', 'testpass');

        // Use reflection to set the private _id property
        $reflection = new ReflectionClass($userIdentity);
        $idProperty = $reflection->getProperty('_id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($userIdentity, '507f1f77bcf86cd799439011');

        $this->assertEquals('507f1f77bcf86cd799439011', $userIdentity->getId());
    }

    public function testGetId_WhenNotSet_ReturnsNull()
    {
        $userIdentity = new UserIdentity('testuser', 'testpass');
        $this->assertNull($userIdentity->getId());
    }

    public function testAuthenticate_WithEmptyUsername_ReturnsFalse()
    {
        $userIdentity = new UserIdentity('', 'testpass');
        $result = $userIdentity->authenticate();
        $this->assertFalse($result);
        $this->assertEquals(CUserIdentity::ERROR_USERNAME_INVALID, $userIdentity->errorCode);
    }

    public function testAuthenticate_WithNonExistentUser_ReturnsFalse()
    {
        $userIdentity = new UserIdentity('nonexistentuser_12345', 'testpass');
        $result = $userIdentity->authenticate();
        $this->assertFalse($result);
        $this->assertEquals(CUserIdentity::ERROR_USERNAME_INVALID, $userIdentity->errorCode);
    }

    public function testUserIdentity_InitializesCorrectly()
    {
        $userIdentity = new UserIdentity('testuser', 'testpass');
        $this->assertEquals('testuser', $userIdentity->username);
        $this->assertEquals('testpass', $userIdentity->password);
        $this->assertNull($userIdentity->getId());
    }

    public function testAuthenticate_WithInvalidPassword_ReturnsFalse()
    {
        // Create a test user first, then try to authenticate with wrong password
        $testUser = new User();
        $testUser->username = 'testuser_invalid_pass';
        $testUser->email = 'testuser_invalid@example.com';
        $testUser->role = 'staff';
        $testUser->password = 'correctpassword';
        $testUser->password_repeat = 'correctpassword';
        $testUser->setScenario('insert');
        
        // Save the user (this will hash the password)
        $testUser->save();
        
        // Now try to authenticate with wrong password
        $userIdentity = new UserIdentity('testuser_invalid_pass', 'wrongpassword');
        $result = $userIdentity->authenticate();

        $this->assertFalse($result);
        $this->assertEquals(CUserIdentity::ERROR_PASSWORD_INVALID, $userIdentity->errorCode);
    }

    public function testAuthenticate_WithValidCredentials_ReturnsTrue()
    {
        // Create a test user first, then authenticate with correct credentials
        $testUser = new User();
        $testUser->username = 'testuser_valid';
        $testUser->email = 'testuser_valid@example.com';
        $testUser->role = 'admin';
        $testUser->password = 'correctpassword';
        $testUser->password_repeat = 'correctpassword';
        $testUser->setScenario('insert');
        
        // Save the user (this will hash the password)
        $testUser->save();
        
        // Now authenticate with correct credentials
        $userIdentity = new UserIdentity('testuser_valid', 'correctpassword');
        $result = $userIdentity->authenticate();

        $this->assertTrue($result);
        $this->assertEquals(CUserIdentity::ERROR_NONE, $userIdentity->errorCode);
        $this->assertNotNull($userIdentity->getId());
        $this->assertEquals('testuser_valid', $userIdentity->username);
    }
}
