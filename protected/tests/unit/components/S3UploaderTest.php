<?php

use Mockery as m;
use Mockery\Adapter\Phpunit\MockeryTestCase;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class S3UploaderTest extends MockeryTestCase
{
    /** @var S3Uploader|m\MockInterface */
    private $s3Uploader;

    /** @var S3Client|m\MockInterface */
    private $s3ClientMock;

    protected function setUp(): void
    {
        parent::setUp();

        // Set test environment variables
        $_ENV['AWS_REGION'] = 'us-east-1';
        $_ENV['AWS_ACCESS_KEY_ID'] = 'test-access-key';
        $_ENV['AWS_SECRET_ACCESS_KEY'] = 'test-secret-key';
        $_ENV['AWS_BUCKET_NAME'] = 'test-bucket';

        // Mock Yii logging
        if (!class_exists('Yii')) {
            $yiiMock = m::mock('alias:Yii');
            $yiiMock->shouldReceive('log')->byDefault();
        }

        // Create S3 client mock
        $this->s3ClientMock = m::mock(S3Client::class);

        // Create S3Uploader instance and inject mock client
        $this->s3Uploader = new S3Uploader();
        $this->s3Uploader->s3Bucket = 'test-bucket';

        // Use reflection to set the private _s3Client property
        $reflection = new ReflectionClass($this->s3Uploader);
        $property = $reflection->getProperty('_s3Client');
        $property->setAccessible(true);
        $property->setValue($this->s3Uploader, $this->s3ClientMock);
    }

    protected function tearDown(): void
    {
        m::close();
        parent::tearDown();
    }

    public function testUploadFileSuccess()
    {
        $s3Key = 'uploads/test-file.txt';
        $expectedUrl = 'https://test-bucket.s3.amazonaws.com/uploads/test-file.txt';

        // Create a real temporary file for testing
        $tempFile = tempnam(sys_get_temp_dir(), 'test_upload');
        file_put_contents($tempFile, 'test content');

        $this->s3ClientMock->shouldReceive('putObject')
            ->once()
            ->with([
                'Bucket' => 'test-bucket',
                'Key' => $s3Key,
                'SourceFile' => $tempFile,
                'Metadata' => [],
            ])
            ->andReturn(['ObjectURL' => $expectedUrl]);

        $result = $this->s3Uploader->uploadFile($tempFile, $s3Key);

        // Clean up the temporary file
        unlink($tempFile);

        $this->assertEquals($expectedUrl, $result);
    }

    public function testUploadFileFailsWhenFileNotExists()
    {
        $filePath = '/tmp/nonexistent-file.txt';
        $s3Key = 'uploads/test-file.txt';

        // Create a temporary file to ensure file_exists works properly in test
        $tempFile = tempnam(sys_get_temp_dir(), 'test');
        unlink($tempFile); // Remove it so file_exists returns false

        $result = $this->s3Uploader->uploadFile($tempFile, $s3Key);

        $this->assertFalse($result);
    }

    public function testDeleteFileSuccess()
    {
        $s3Key = 'uploads/test-file.txt';

        $this->s3ClientMock->shouldReceive('deleteObject')
            ->once()
            ->with([
                'Bucket' => 'test-bucket',
                'Key' => $s3Key,
            ])
            ->andReturn(['DeleteMarker' => true]);

        $result = $this->s3Uploader->deleteFile($s3Key);

        $this->assertTrue($result);
    }

    public function testDeleteFileHandlesException()
    {
        $s3Key = 'uploads/test-file.txt';

        $commandMock = m::mock('Aws\CommandInterface');

        $this->s3ClientMock->shouldReceive('deleteObject')
            ->once()
            ->andThrow(new S3Exception('Delete failed', $commandMock));

        $result = $this->s3Uploader->deleteFile($s3Key);

        $this->assertFalse($result);
    }

    public function testFileExistsReturnsTrue()
    {
        $s3Key = 'uploads/test-file.txt';

        $this->s3ClientMock->shouldReceive('doesObjectExist')
            ->once()
            ->with('test-bucket', $s3Key)
            ->andReturn(true);

        $result = $this->s3Uploader->fileExists($s3Key);

        $this->assertTrue($result);
    }

    public function testFileExistsReturnsFalse()
    {
        $s3Key = 'uploads/nonexistent-file.txt';

        $this->s3ClientMock->shouldReceive('doesObjectExist')
            ->once()
            ->with('test-bucket', $s3Key)
            ->andReturn(false);

        $result = $this->s3Uploader->fileExists($s3Key);

        $this->assertFalse($result);
    }

    public function testGetS3KeyFromUrlWithValidUrl()
    {
        $s3Url = 'https://test-bucket.s3.amazonaws.com/uploads/documents/file.pdf';
        $expectedKey = 'uploads/documents/file.pdf';

        $result = $this->s3Uploader->getS3KeyFromUrl($s3Url);

        $this->assertEquals($expectedKey, $result);
    }

    public function testGetS3KeyFromUrlWithInvalidUrl()
    {
        $invalidUrl = 'not-a-valid-url';

        $result = $this->s3Uploader->getS3KeyFromUrl($invalidUrl);

        $this->assertFalse($result);
    }

    // Test init() method
    public function testInit()
    {
        $s3Uploader = new S3Uploader();
        $s3Uploader->init();

        // Verify that properties were set correctly
        $this->assertEquals($_ENV['AWS_BUCKET_NAME'], $s3Uploader->s3Bucket);
    }

    // Test getS3Client() method
    public function testGetS3Client()
    {
        $s3Uploader = new S3Uploader();
        $client = $s3Uploader->getS3Client();

        $this->assertInstanceOf(S3Client::class, $client);
    }

    // Test static getter methods
    public function testGetRegion()
    {
        $this->assertEquals($_ENV['AWS_REGION'], S3Uploader::getRegion());
    }

    public function testGetKey()
    {
        $this->assertEquals($_ENV['AWS_ACCESS_KEY_ID'], S3Uploader::getKey());
    }

    public function testGetSecret()
    {
        $this->assertEquals($_ENV['AWS_SECRET_ACCESS_KEY'], S3Uploader::getSecret());
    }

    // Test uploadFile when _s3Client is null
    public function testUploadFileWhenS3ClientNotInitialized()
    {
        $s3Uploader = new S3Uploader();

        // Use reflection to set _s3Client to null
        $reflection = new ReflectionClass($s3Uploader);
        $property = $reflection->getProperty('_s3Client');
        $property->setAccessible(true);
        $property->setValue($s3Uploader, null);

        $result = $s3Uploader->uploadFile('/tmp/test.txt', 'test-key');

        $this->assertFalse($result);
    }

    // Test uploadFile catch exception
    public function testUploadFileHandlesException()
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_upload');
        file_put_contents($tempFile, 'test content');
        $s3Key = 'uploads/test-file.txt';

        $commandMock = m::mock('Aws\CommandInterface');

        $this->s3ClientMock->shouldReceive('putObject')
            ->once()
            ->andThrow(new S3Exception('Upload failed', $commandMock));

        $result = $this->s3Uploader->uploadFile($tempFile, $s3Key);

        unlink($tempFile);

        $this->assertFalse($result);
    }

    // Test deleteFile when _s3Client is null
    public function testDeleteFileWhenS3ClientNotInitialized()
    {
        $s3Uploader = new S3Uploader();

        // Use reflection to set _s3Client to null
        $reflection = new ReflectionClass($s3Uploader);
        $property = $reflection->getProperty('_s3Client');
        $property->setAccessible(true);
        $property->setValue($s3Uploader, null);

        $result = $s3Uploader->deleteFile('test-key');

        $this->assertFalse($result);
    }

    // Test fileExists when _s3Client is null
    public function testFileExistsWhenS3ClientNotInitialized()
    {
        $s3Uploader = new S3Uploader();

        // Use reflection to set _s3Client to null
        $reflection = new ReflectionClass($s3Uploader);
        $property = $reflection->getProperty('_s3Client');
        $property->setAccessible(true);
        $property->setValue($s3Uploader, null);

        $result = $s3Uploader->fileExists('test-key');

        $this->assertFalse($result);
    }

    // Test getPresignedUrl method - success case
    public function testGetPresignedUrlSuccess()
    {
        $s3Key = 'uploads/test-file.txt';
        $expectedUrl = 'https://test-bucket.s3.amazonaws.com/uploads/test-file.txt?presigned=true';

        $commandMock = m::mock('Aws\CommandInterface');
        $requestMock = m::mock('Psr\Http\Message\RequestInterface');
        $uriMock = m::mock('Psr\Http\Message\UriInterface');

        $uriMock->shouldReceive('__toString')->andReturn($expectedUrl);
        $requestMock->shouldReceive('getUri')->andReturn($uriMock);

        $this->s3ClientMock->shouldReceive('getCommand')
            ->once()
            ->with('GetObject', [
                'Bucket' => 'test-bucket',
                'Key' => $s3Key
            ])
            ->andReturn($commandMock);

        $this->s3ClientMock->shouldReceive('createPresignedRequest')
            ->once()
            ->with($commandMock, '+15 minutes')
            ->andReturn($requestMock);

        $result = $this->s3Uploader->getPresignedUrl($s3Key);

        $this->assertEquals($expectedUrl, $result);
    }

    // Test getPresignedUrl when _s3Client is null
    public function testGetPresignedUrlWhenS3ClientNotInitialized()
    {
        $s3Uploader = new S3Uploader();

        // Use reflection to set _s3Client to null
        $reflection = new ReflectionClass($s3Uploader);
        $property = $reflection->getProperty('_s3Client');
        $property->setAccessible(true);
        $property->setValue($s3Uploader, null);

        $result = $s3Uploader->getPresignedUrl('test-key');

        $this->assertFalse($result);
    }

    // Test getPresignedUrl exception handling
    public function testGetPresignedUrlHandlesException()
    {
        $s3Key = 'uploads/test-file.txt';

        $commandMock = m::mock('Aws\CommandInterface');
        $commandInterfaceMock = m::mock('Aws\CommandInterface');

        $this->s3ClientMock->shouldReceive('getCommand')
            ->once()
            ->andThrow(new S3Exception('Presigned URL failed', $commandInterfaceMock));

        $result = $this->s3Uploader->getPresignedUrl($s3Key);

        $this->assertFalse($result);
    }

    // Test getS3KeyFromUrl with URL that has no path
    public function testGetS3KeyFromUrlWithNoPath()
    {
        $urlWithoutPath = 'https://test-bucket.s3.amazonaws.com';

        $result = $this->s3Uploader->getS3KeyFromUrl($urlWithoutPath);

        $this->assertFalse($result);
    }

    // Test getS3KeyFromUrl with URL that has empty path
    public function testGetS3KeyFromUrlWithEmptyPath()
    {
        $urlWithEmptyPath = 'https://test-bucket.s3.amazonaws.com/';
        $expectedKey = '';

        $result = $this->s3Uploader->getS3KeyFromUrl($urlWithEmptyPath);

        $this->assertEquals($expectedKey, $result);
    }
}
