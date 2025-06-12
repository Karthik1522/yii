<?php

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class S3Uploader extends CApplicationComponent
{
    private static $awsAccessKeyId;
    private static $awsSecretAccessKey;
    private static $region;
    
    public $s3Bucket;
    private $_s3Client;

    public function init()
    {
        parent::init();

        self::$region = $_ENV['AWS_REGION'];
        self::$awsAccessKeyId = $_ENV['AWS_ACCESS_KEY_ID'];
        self::$awsSecretAccessKey = $_ENV['AWS_SECRET_ACCESS_KEY'];
        $this->s3Bucket = $_ENV['AWS_BUCKET_NAME'];
        $this->_s3Client = $this->getS3Client();
    }

    public function getS3Client(): S3Client
    {
        return new S3Client([
            'region' => self::getRegion(),
            'version' => 'latest',
            'credentials' => [
                'key' => self::getKey(),
                'secret' => self::getSecret(),
            ],
        ]);
    }


    public static function getRegion()
    {
        return self::$region;
    }

    public static function getKey()
    {
        return self::$awsAccessKeyId;
    }

    public static function getSecret()
    {
        return self::$awsSecretAccessKey;
    }

    /**
     * Uploads a file to S3.
     * @param string $filePath The path to the local file.
     * @param string $s3Key The key (path/filename) for the object in S3.
     * @param string $acl ACL for the uploaded object (e.g., 'public-read').
     * @param array $metadata Optional metadata for the S3 object.
     * @return string|false The S3 object URL on success, false on failure.
     */
    public function uploadFile($filePath, $s3Key, $acl = 'public', $metadata = [])
    {
       
        if (!$this->_s3Client) {
            Yii::log("S3 client not initialized.", CLogger::LEVEL_ERROR, 'application.components.S3Uploader');
            return false;
        }


        if (!file_exists($filePath)) {
            Yii::log("File not found: {$filePath}", CLogger::LEVEL_ERROR, 'application.components.S3Uploader');
            return false;
        }
        
        try {
            
            $result = $this->_s3Client->putObject([
                'Bucket'     => $this->s3Bucket,
                'Key'        => $s3Key,
                'SourceFile' => $filePath,
                // 'ACL'        => $acl,
                'Metadata'   => $metadata,
            ]);

            
            return $result['ObjectURL'];
        } catch (S3Exception $e) {
            Yii::log("S3 Upload Error: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.components.S3Uploader');
            return false;
        }
    }

    /**
     * Generates a presigned URL for an S3 object.
     * @param string $s3Key The key of the object in S3.
     * @param string $expires Expiration time (e.g., '+10 minutes').
     * @return string|false The presigned URL on success, false on failure.
     */
    public function getPresignedUrl($s3Key, $expires = '+15 minutes')
    {
        if (!$this->_s3Client) {
            Yii::log("S3 client not initialized.", CLogger::LEVEL_ERROR, 'application.components.S3Uploader');
            return false;
        }

        try {
            $cmd = $this->_s3Client->getCommand('GetObject', [
                'Bucket' => $this->s3Bucket,
                'Key'    => $s3Key
            ]);

            $request = $this->_s3Client->createPresignedRequest($cmd, $expires);
            return (string) $request->getUri();
        } catch (S3Exception $e) {
            Yii::log("S3 Presigned URL Error: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.components.S3Uploader');
            return false;
        }
    }

    /**
     * Deletes an object from S3.
     * @param string $s3Key The key of the object in S3.
     * @return bool True on success, false on failure.
     */
    public function deleteFile($s3Key)
    {
        if (!$this->_s3Client) {
            Yii::log("S3 client not initialized.", CLogger::LEVEL_ERROR, 'application.components.S3Uploader');
            return false;
        }

        try {
            $this->_s3Client->deleteObject([
                'Bucket' => $this->s3Bucket,
                'Key'    => $s3Key,
            ]);
            return true;
        } catch (S3Exception $e) {
            Yii::log("S3 Delete Error: " . $e->getMessage(), CLogger::LEVEL_ERROR, 'application.components.S3Uploader');
            return false;
        }
    }

    /**
     * Checks if an object exists in S3.
     * @param string $s3Key The key of the object in S3.
     * @return bool True if exists, false otherwise.
     */
    public function fileExists($s3Key)
    {
        if (!$this->_s3Client) {
            Yii::log("S3 client not initialized.", CLogger::LEVEL_ERROR, 'application.components.S3Uploader');
            return false;
        }
        return $this->_s3Client->doesObjectExist($this->s3Bucket, $s3Key);
    }

    /**
     * Extracts the S3 object key from a full S3 URL.
     *
     * @param string $s3Url Full S3 URL (e.g., https://your-bucket.s3.amazonaws.com/folder/filename.jpg)
     * @return string|false The object key if found, false otherwise
     */
    function getS3KeyFromUrl($s3Url)
    {
        // Validate URL
        if (!filter_var($s3Url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Parse the URL to get the path
        $parsedUrl = parse_url($s3Url);

        if (!isset($parsedUrl['path'])) {
            return false;
        }

        // Remove leading slash from path
        return ltrim($parsedUrl['path'], '/');
    }

}