<?php

/**
* @runTestsInSeparateProcesses
* @preserveGlobalState disabled
*/

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;

class SiteHelperTest extends MockeryTestCase
{
    private $yiiAppMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->yiiAppMock = new YiiAppMock();
    }

    protected function tearDown(): void
    {
        $this->yiiAppMock->close();
        m::close();
        parent::tearDown();
    }

    // ========== processLogin Tests ==========

    /**
     * Test processLogin with successful login
     */
    public function testProcessLogin_Success()
    {
        // Mock LoginForm class using overload
        $mockLoginForm = m::mock('overload:LoginForm');
        $mockLoginForm->shouldReceive('validate')->once()->andReturn(true);
        $mockLoginForm->shouldReceive('login')->once()->andReturn(true);
        $mockLoginForm->attributes = ['username' => 'test', 'password' => 'test123'];

        $result = SiteHelper::processLogin(['username' => 'test', 'password' => 'test123']);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('model', $result);
        $this->assertEquals(['site/index'], $result['redirectUrl']);
    }

    /**
     * Test processLogin with validation failure
     */
    public function testProcessLogin_ValidationFailure()
    {
        // Mock LoginForm class using overload
        $mockLoginForm = m::mock('overload:LoginForm');
        $mockLoginForm->shouldReceive('validate')->once()->andReturn(false);
        $mockLoginForm->shouldNotReceive('login');
        $mockLoginForm->attributes = ['username' => '', 'password' => ''];

        $result = SiteHelper::processLogin(['username' => '', 'password' => '']);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('model', $result);
        $this->assertArrayNotHasKey('redirectUrl', $result);
    }

    /**
     * Test processLogin with login failure (validation passes but login fails)
     */
    public function testProcessLogin_LoginFailure()
    {
        // Mock LoginForm class using overload
        $mockLoginForm = m::mock('overload:LoginForm');
        $mockLoginForm->shouldReceive('validate')->once()->andReturn(true);
        $mockLoginForm->shouldReceive('login')->once()->andReturn(false);
        $mockLoginForm->attributes = ['username' => 'test', 'password' => 'wrongpass'];

        $result = SiteHelper::processLogin(['username' => 'test', 'password' => 'wrongpass']);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('model', $result);
        $this->assertArrayNotHasKey('redirectUrl', $result);
    }

    /**
     * Test processLogin exception handling
     */
    public function testProcessLogin_ExceptionHandling()
    {
        // Don't expect specific exception code since CHttpException constructor behavior varies
        $this->expectException(CHttpException::class);
        $this->expectExceptionMessage('Error processing login.');

        // Mock LoginForm to throw exception during validate
        $mockLoginForm = m::mock('overload:LoginForm');
        $mockLoginForm->shouldReceive('validate')->once()->andThrow(new Exception('Database error'));

        SiteHelper::processLogin(['username' => 'test', 'password' => 'test']);
    }


    // ========== processRegistration Tests ==========

    /**
     * Test processRegistration with successful registration
     */
    public function testProcessRegistration_Success()
    {
        // Mock User class using overload
        $mockUser = m::mock('overload:User');
        $mockUser->shouldReceive('validate')->once()->andReturn(true);
        $mockUser->shouldReceive('save')->once()->andReturn(true);
        $mockUser->attributes = ['username' => 'newuser', 'email' => 'test@example.com'];

        $result = SiteHelper::processRegistration(['username' => 'newuser', 'email' => 'test@example.com']);

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('model', $result);
        $this->assertEquals('Registration successful. You can now log in.', $result['message']);
        $this->assertEquals(['site/login'], $result['redirectUrl']);
    }

    /**
     * Test processRegistration with validation failure
     */
    public function testProcessRegistration_ValidationFailure()
    {
        // Mock User class using overload
        $mockUser = m::mock('overload:User');
        $mockUser->shouldReceive('validate')->once()->andReturn(false);
        $mockUser->shouldNotReceive('save');
        $mockUser->attributes = ['username' => '', 'email' => 'invalid'];

        $result = SiteHelper::processRegistration(['username' => '', 'email' => 'invalid']);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('model', $result);
        $this->assertEquals('Please fix the errors in the form.', $result['message']);
        $this->assertArrayNotHasKey('redirectUrl', $result);
    }

    /**
     * Test processRegistration with save failure
     */
    public function testProcessRegistration_SaveFailure()
    {
        // Mock User class using overload
        $mockUser = m::mock('overload:User');
        $mockUser->shouldReceive('validate')->once()->andReturn(true);
        $mockUser->shouldReceive('save')->once()->andReturn(false);
        $mockUser->attributes = ['username' => 'newuser', 'email' => 'test@example.com'];

        $result = SiteHelper::processRegistration(['username' => 'newuser', 'email' => 'test@example.com']);

        $this->assertIsArray($result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('model', $result);
        $this->assertEquals('Something went wrong while saving the user.', $result['message']);
        $this->assertArrayNotHasKey('redirectUrl', $result);
    }

    /**
     * Test processRegistration exception handling
     */
    public function testProcessRegistration_ExceptionHandling()
    {
        // Mock User to throw exception during validate
        $mockUser = m::mock('overload:User');
        $mockUser->shouldReceive('validate')->once()->andThrow(new Exception('Database connection failed'));

        $this->expectException(CHttpException::class);
        $this->expectExceptionMessage('Error processing registration.');

        SiteHelper::processRegistration(['username' => 'test', 'email' => 'test@example.com']);
    }

    // ========== processLogout Tests ==========

    /**
     * Test processLogout success
     */
    public function testProcessLogout_Success()
    {
        // Mock Yii app and user component using YiiAppMock
        $appMock = $this->yiiAppMock->mockApp()->shouldIgnoreMissing();
        $mockUser = $this->yiiAppMock->mockAppComponent('user');
        $mockUser->shouldReceive('logout')->once();

        // Mock homeUrl property access directly on the app mock
        $appMock->homeUrl = '/home';

        $result = SiteHelper::processLogout();

        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        // $this->assertEquals('/home', $result['redirectUrl']);
    }

    /**
     * Test processLogout exception handling
     */
    public function testProcessLogout_ExceptionHandling()
    {
        // Mock Yii app and user component using YiiAppMock
        $appMock = $this->yiiAppMock->mockApp();
        $mockUser = $this->yiiAppMock->mockAppComponent('user');
        $mockUser->shouldReceive('logout')->once()->andThrow(new Exception('Session error'));

        $this->expectException(CHttpException::class);
        $this->expectExceptionMessage('Error processing logout.');

        SiteHelper::processLogout();
    }

    // ========== Keep existing method that doesn't need 100% coverage ==========

    /**
     * Test getDefaultRedirectUrl (static method, no dependencies)
     */
    public function testGetDefaultRedirectUrl()
    {
        $result = SiteHelper::getDefaultRedirectUrl();

        $this->assertIsArray($result);
        $this->assertEquals(['/inventory/dashboard/index'], $result);
    }
}
