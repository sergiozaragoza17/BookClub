<?php

namespace App\Tests\Service;

use App\Service\S3Uploader;
use Aws\S3\S3Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class S3UploaderTest extends TestCase
{
    public function testUpload()
    {
        $filePath = __DIR__ . '/testfile.txt';
        file_put_contents($filePath, 'dummy content');
        $uploadedFile = new UploadedFile($filePath, 'testfile.txt', null, null, true);

        $s3ClientMock = $this->getMockBuilder(\Aws\S3\S3Client::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getObjectUrl'])
            ->addMethods(['putObject'])
            ->getMock();

        $s3ClientMock->expects($this->once())
            ->method('putObject')
            ->with($this->callback(function ($params) use ($uploadedFile) {
                return $params['SourceFile'] === $uploadedFile->getPathname()
                    && strpos($params['Key'], 'profiles/') === 0;
            }))
            ->willReturn(['ObjectURL' => 'https://bucket.s3.amazonaws.com/profiles/testfile.txt']);

        $s3ClientMock->method('getObjectUrl')
            ->willReturn('https://bucket.s3.amazonaws.com/profiles/testfile.txt');

        $uploader = new \App\Service\S3Uploader('key', 'secret', 'region', 'bucket');
        $ref = new \ReflectionClass($uploader);
        $prop = $ref->getProperty('s3Client');
        $prop->setAccessible(true);
        $prop->setValue($uploader, $s3ClientMock);

        $url = $uploader->upload($uploadedFile, 'profiles/');

        $this->assertEquals('https://bucket.s3.amazonaws.com/profiles/testfile.txt', $url);

        unlink($filePath);
    }

}
