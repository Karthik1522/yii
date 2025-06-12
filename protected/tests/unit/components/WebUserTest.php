<?php

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use MongoDB\BSON\ObjectId;

class WebUserTest extends MockeryTestCase
{
    /** @var WebUser */
    private $webUser;

    /** @var \Mockery\MockInterface */
    private $userModelMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock User model for getModel() tests
        $this->userModelMock = m::mock('User');
        $this->userModelMock->shouldReceive('model')->andReturnSelf()->byDefault();

        // Create WebUser instance
        $this->webUser = new WebUser();
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    // Test role methods when user is guest
    public function testGetRole_WhenGuest_ReturnsNull()
    {
        $webUserMock = m::mock(WebUser::class)->makePartial();
        $webUserMock->shouldReceive('getIsGuest')->andReturn(true);
        $this->assertNull($webUserMock->getRole());
    }

    public function testGetName_WhenGuest_ReturnsNull()
    {
        $webUserMock = m::mock(WebUser::class)->makePartial();
        $webUserMock->shouldReceive('getIsGuest')->andReturn(true);
        $this->assertNull($webUserMock->getName());
    }

    public function testHasRole_WhenGuest_ReturnsFalse()
    {
        $webUserMock = m::mock(WebUser::class)->makePartial();
        $webUserMock->shouldReceive('getIsGuest')->andReturn(true);
        $this->assertFalse($webUserMock->hasRole('admin'));
        $this->assertFalse($webUserMock->hasRole(['admin', 'staff']));
    }

    // Test role methods when user is authenticated
    public function testGetRole_WhenAuthenticated_ReturnsRole()
    {
        $webUserMock = m::mock(WebUser::class)->makePartial();
        $webUserMock->shouldReceive('getIsGuest')->andReturn(false);
        $webUserMock->shouldReceive('getState')->with('role')->andReturn('admin');

        $this->assertEquals('admin', $webUserMock->getRole());
    }

    public function testGetName_WhenAuthenticated_ReturnsUsername()
    {
        $webUserMock = m::mock(WebUser::class)->makePartial();
        $webUserMock->shouldReceive('getIsGuest')->andReturn(false);
        $webUserMock->shouldReceive('getState')->with('username')->andReturn('testuser');

        $this->assertEquals('testuser', $webUserMock->getName());
    }

    // Test hasRole with different scenarios
    public function testHasRole_WithSingleRole_ReturnsCorrectBoolean()
    {
        $webUserMock = m::mock(WebUser::class)->makePartial();
        $webUserMock->shouldReceive('getIsGuest')->andReturn(false);
        $webUserMock->shouldReceive('getState')->with('role')->andReturn('admin');

        $this->assertTrue($webUserMock->hasRole('admin'));
        $this->assertFalse($webUserMock->hasRole('staff'));
    }

    public function testHasRole_WithArrayOfRoles_ReturnsCorrectBoolean()
    {
        $webUserMock = m::mock(WebUser::class)->makePartial();
        $webUserMock->shouldReceive('getIsGuest')->andReturn(false);
        $webUserMock->shouldReceive('getState')->with('role')->andReturn('manager');

        $this->assertTrue($webUserMock->hasRole(['admin', 'manager', 'staff']));
        $this->assertFalse($webUserMock->hasRole(['admin', 'staff']));
    }

    // Test convenience role methods
    public function testIsAdmin_ReturnsCorrectBoolean()
    {
        $webUserMock = m::mock(WebUser::class)->makePartial();
        $webUserMock->shouldReceive('hasRole')->with('admin')->andReturn(true);
        $this->assertTrue($webUserMock->isAdmin());

        $webUserMock2 = m::mock(WebUser::class)->makePartial();
        $webUserMock2->shouldReceive('hasRole')->with('admin')->andReturn(false);
        $this->assertFalse($webUserMock2->isAdmin());
    }

    public function testIsStaff_ReturnsCorrectBoolean()
    {
        $webUserMock = m::mock(WebUser::class)->makePartial();
        $webUserMock->shouldReceive('hasRole')->with('staff')->andReturn(true);
        $this->assertTrue($webUserMock->isStaff());
    }

    public function testIsManager_ReturnsCorrectBoolean()
    {
        $webUserMock = m::mock(WebUser::class)->makePartial();
        $webUserMock->shouldReceive('hasRole')->with('manager')->andReturn(true);
        $this->assertTrue($webUserMock->isManager());
    }

    // Test getModel method
    // public function testGetModel_WhenGuest_ReturnsNull()
    // {
    //     $webUserMock = m::mock(WebUser::class)->makePartial();
    //     $webUserMock->shouldReceive('getIsGuest')->andReturn(true);
    //     $this->assertNull($webUserMock->getModel());
    // }

    // public function testGetModel_WhenNoId_ReturnsNull()
    // {
    //     $webUserMock = m::mock(WebUser::class)->makePartial();
    //     $webUserMock->shouldReceive('getIsGuest')->andReturn(false);
    //     $webUserMock->id = null;
    //     $this->assertNull($webUserMock->getModel());
    // }

    // public function testGetModel_WithValidId_ReturnsUserModel()
    // {
    //     $webUserMock = m::mock(WebUser::class)->makePartial();
    //     $webUserMock->shouldReceive('getIsGuest')->andReturn(false);
    //     $webUserMock->id = '507f1f77bcf86cd799439011';

    //     $expectedUser = m::mock('stdClass');

    //     // Create a mock that will be returned by User::model()
    //     $userModelMock = m::mock();
    //     $userModelMock->shouldReceive('findByPk')
    //                  ->with(m::type(ObjectId::class))
    //                  ->andReturn($expectedUser);

    //     // Mock the getModel method directly to avoid class mocking issues
    //     $webUserMock->shouldReceive('getModel')->andReturn($expectedUser);

    //     $result = $webUserMock->getModel();
    //     $this->assertEquals($expectedUser, $result);
    // }

    // public function testGetModel_WithInvalidId_ReturnsNull()
    // {
    //     $webUserMock = m::mock(WebUser::class)->makePartial();
    //     $webUserMock->shouldReceive('getIsGuest')->andReturn(false);
    //     $webUserMock->id = 'invalid-id';

    //     // Instead of mocking the actual getModel method, let's test the behavior directly
    //     // by setting up the scenario where an exception would be thrown
    //     $webUserMock->shouldReceive('getModel')->andReturn(null);

    //     $result = $webUserMock->getModel();
    //     $this->assertNull($result);
    // }

    // public function testGetModel_WhenExceptionThrown_LogsWarningAndReturnsNull()
    // {
    //     // Create a partial mock that allows us to test the real exception handling
    //     $webUserMock = m::mock(WebUser::class)->makePartial();
    //     $webUserMock->shouldReceive('getIsGuest')->andReturn(false);
    //     $webUserMock->id = '507f1f77bcf86cd799439011';

    //     // Mock User model to throw an exception
    //     $userModelMock = m::mock('alias:User');
    //     $userModelMock->shouldReceive('model')
    //                  ->once()
    //                  ->andReturnSelf();
    //     $userModelMock->shouldReceive('findByPk')
    //                  ->once()
    //                  ->with(m::type(ObjectId::class))
    //                  ->andThrow(new Exception('Database connection failed'));

    //     // Call the actual method - it should handle the exception and return null
    //     $result = $webUserMock->getModel();
    //     $this->assertNull($result);

    //     // Verify that _model property is set to null after exception
    //     $reflection = new ReflectionClass($webUserMock);
    //     $property = $reflection->getProperty('_model');
    //     $property->setAccessible(true);
    //     $this->assertNull($property->getValue($webUserMock));
    // }
}
