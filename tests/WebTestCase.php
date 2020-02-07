<?php

namespace TS\Web\Resource;


if (php_sapi_name() == 'cli-server') {
    if (preg_match('/^\/$/', $_SERVER["REQUEST_URI"])) {
        header('Content-Type: text/html');
        print "index";
    } else if (preg_match('/^\/foo-no-content-length$/', $_SERVER["REQUEST_URI"])) {
        header('Content-Type: application/x-foo');
        print "foo";
    } else if (preg_match('/^\/foo-error$/', $_SERVER["REQUEST_URI"])) {
        header('HTTP/1.1 500 Internal Server Error');
        print "foo";
    } else {
        // return false to route static assets
        return false;
    }
    return true;
}


use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;


abstract class WebTestCase extends TestCase
{

    public static $base_url;

    private static $port = 8000;

    /** @var Process */
    private static $webserver;

    public static function setUpBeforeClass(): void
    {
        self::$webserver = new Process('exec php -S localhost:' . self::$port . ' -t ' . __DIR__ . '/Data ' . __FILE__);
        self::$base_url = 'http://localhost:' . self::$port . '/';
        self::$port++;
        self::$webserver->start();
        usleep(100 * 1000);
    }

    public static function tearDownAfterClass(): void
    {
        if (!self::$webserver->isRunning()) {
            throw new \Exception('Webserver seems to have failed. ' . self::$webserver->getErrorOutput());
        }
        self::$webserver->stop();
    }

}
