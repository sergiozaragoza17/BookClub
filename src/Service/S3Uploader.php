<?php

namespace App\Service;

use Aws\S3\S3Client;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class S3Uploader
{
    private $s3Client;
    private $bucketName;

    public function __construct(string $awsKey, string $awsSecret, string $awsRegion, string $bucketName)
    {
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region'  => $awsRegion,
            'credentials' => [
                'key'    => $awsKey,
                'secret' => $awsSecret,
            ],
        ]);
        $this->bucketName = $bucketName;
    }

    public function upload(UploadedFile $file, string $keyPrefix = ''): string
    {
        $key = $keyPrefix . uniqid() . '.' . $file->guessExtension();

        $this->s3Client->putObject([
            'Bucket' => $this->bucketName,
            'Key'    => $key,
            'SourceFile' => $file->getPathname(),
        ]);

        return $this->s3Client->getObjectUrl($this->bucketName, $key);
    }
}