<?php

namespace TS\Web\Resource;


class UrlResourceTest extends WebTestCase
{
	
	public function downloadTest() {
		$dir = ResourceUtil::createTempDir();
		$url = new UrlResource(self::$base_url . 'plaintext.txt');
		$local = $url->download($dir);
		$this->assertSame($url->getMimetype(), $local->getMimetype());
		$this->assertSame($url->getFilename(), $local->getFilename());
		$this->assertSame($url->getLastModified(), $local->getLastModified());
		$this->assertSame($url->getLength(), $local->getLength());
		$this->assertSame($url->getHash(), $local->getHash());
		unlink($local->getPath());
	}
	
	public function downloadAsTest() {
		$path = ResourceUtil::createTempDir() . 'test.txt';
		$url = new UrlResource(self::$base_url . 'plaintext.txt');
		$local = $url->downloadAs($path);
		$this->assertSame($url->getMimetype(), $local->getMimetype());
		$this->assertSame($url->getFilename(), $local->getFilename());
		$this->assertSame($url->getLastModified(), $local->getLastModified());
		$this->assertSame($url->getLength(), $local->getLength());
		$this->assertSame($url->getHash(), $local->getHash());
		unlink($local->getPath());
	}
	
	public function testPlaintext()
	{
		$r = new UrlResource(self::$base_url . 'plaintext.txt');
		
		$this->assertSame('text/plain; charset=UTF-8', $r->getMimetype());
		$this->assertSame('plaintext.txt', $r->getFilename());
		$this->assertSame(10, $r->getLength());
		$this->assertSame('9f9443b8f3d8361541f8792562b3050f721ee534', $r->getHash());
		$this->assertSame('plain text', stream_get_contents($r->getStream()));
	}

	public function testNoContentLength()
	{
		$r = new UrlResource(self::$base_url . 'foo-no-content-length');
		
		$this->assertSame('foo-no-content-length', $r->getFilename());
		$this->assertSame('application/x-foo', $r->getMimetype());
		$this->assertNull($r->getLength());
		$this->assertSame('foo', stream_get_contents($r->getStream()));
	}

	public function testNotFound()
	{
		$this->expectException(\Exception::class);
		$this->expectExceptionMessageRegExp('/^Got HTTP 404 for URL/');
		
		$r = new UrlResource(self::$base_url . 'does-not-exist');
		$r->getMimetype();
	}

	public function testError()
	{
		$this->expectException(\Exception::class);
		$this->expectExceptionMessageRegExp('/^Got HTTP 500 for URL/');
		$r = new UrlResource(self::$base_url . 'foo-error');
		$r->getMimetype();
	}

	public function testOverrideFilename()
	{
		$r = new UrlResource(self::$base_url . 'plaintext.txt', [
			'filename' => 'dummy.foo'
		]);
		$this->assertSame('dummy.foo', $r->getFilename());
		$this->assertSame('text/plain; charset=UTF-8', $r->getMimetype());
	}

	public function testOverrideMimetype()
	{
		$r = new UrlResource(self::$base_url . 'plaintext.txt', [
			'mimetype' => 'image/jpeg'
		]);
		$this->assertSame('image/jpeg', $r->getMimetype());
	}

	public function testOverrideLastModified()
	{
		$now = new \DateTime();
		$r = new UrlResource(self::$base_url . 'plaintext.txt', [
			'lastmodified' => $now
		]);
		$this->assertSame($now, $r->getLastModified());
	}

}