<?php

namespace TS\Web\Resource;

use PHPUnit\Framework\TestCase;
use ZipArchive;

class StreamCopyToStreamTest extends TestCase
{
    public function testCopyText()
    {
        // Create source stream
        $data = "plain text";
        $in = fopen('php://temp', 'r+');
        fwrite($in, $data);
        rewind($in);

        // Create destination stream
        $out = fopen('php://temp', 'r+');

        // Copy data from the source stream to the destination stream and check the result
        $bytesCopied = stream_copy_to_stream($in, $out);
        rewind($out);
        $this->assertEquals(strlen($data), $bytesCopied);
        $this->assertEquals($data, stream_get_contents($out));

        // Close streams
        fclose($in);
        fclose($out);
    }

    public function testCopyTextFileResource()
    {
        // Create source stream
        $filename = __DIR__ . '/Data/plaintext.txt';
        $resource = Resource::fromFile($filename);
        $in = $resource->getStream();

        // Create destination stream
        $out = fopen('php://temp', 'r+');

        // Copy data from the source stream to the destination stream and check the result
        $bytesCopied = stream_copy_to_stream($in, $out);
        rewind($out);
        $this->assertEquals(strlen(file_get_contents($filename)), $bytesCopied);
        $this->assertEquals(file_get_contents($filename), stream_get_contents($out));

        // Close streams
        fclose($in);
        fclose($out);
    }

    public function testCopyZipFileResource()
    {
        // Create source stream
        $filename = __DIR__ . '/Data/plaintext.txt.zip';
        $resource = Resource::fromFile($filename);
        $in = $resource->getStream();

        // Create destination stream
        $out = fopen('php://temp', 'r+');

        // Copy data from the source stream to the destination stream and check the result
        $bytesCopied = stream_copy_to_stream($in, $out);
        rewind($out);
        $this->assertEquals(strlen(file_get_contents($filename)), $bytesCopied);
        $this->assertEquals(file_get_contents($filename), stream_get_contents($out));

        // Close streams
        fclose($in);
        fclose($out);
    }

    public function testCopyZipArchive()
    {
        // Create source stream
        $filename = __DIR__ . '/Data/plaintext.txt.zip';
        $path = 'plaintext.txt';
        $zipArchive = new ZipArchive();
        $zipArchive->open($filename);
        $data = $zipArchive->getFromName($path);
        $in = $zipArchive->getStream($path);

        // Create destination stream
        $out = fopen('php://temp', 'r+');

        // Copy data from the source stream to the destination stream and check the result
        $bytesCopied = stream_copy_to_stream($in, $out);
        rewind($out);
        $this->assertEquals(strlen($data), $bytesCopied);
        $this->assertEquals($data, stream_get_contents($out));

        // Close streams
        fclose($in);
        fclose($out);
    }

    public function testCopyZipArchiveStream()
    {
        // Create source stream
        $filename = __DIR__ . '/Data/plaintext.txt.zip';
        $path = 'plaintext.txt';
        $zipArchive = new ZipArchive();
        $zipArchive->open($filename);
        $data = $zipArchive->getFromName($path);
        $zipArchive = new ZipArchive();
        $zipArchive->open($filename);
        $resource = new Resource([
            'stream' => function () use ($zipArchive, $path) {
                return $zipArchive->getStream($path);
            },
            'length' => filesize($filename),
            'hash' => sha1_file($filename),
            'mimetype' => 'application/zip',
            'filename' => pathinfo($filename, PATHINFO_BASENAME),
        ]);
        $in = $resource->getStream();

        // Create destination stream
        $out = fopen('php://temp', 'r+');

        // Copy data from the source stream to the destination stream and check the result
        $bytesCopied = stream_copy_to_stream($in, $out);
        rewind($out);
        $this->assertEquals(strlen($data), $bytesCopied);
        $this->assertEquals($data, stream_get_contents($out));

        // Close streams
        fclose($in);
        fclose($out);
    }
}
