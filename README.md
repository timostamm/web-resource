# PHP Web Resources

[![build](https://github.com/timostamm/web-resource/workflows/CI/badge.svg)](https://github.com/timostamm/web-resource/actions?query=workflow:"CI")
![Packagist PHP Version](https://img.shields.io/packagist/dependency-v/timostamm/web-resource/php)
[![GitHub tag](https://img.shields.io/github/tag/timostamm/web-resource?include_prereleases=&sort=semver&color=blue)](https://github.com/timostamm/web-resource/releases/)
[![License](https://img.shields.io/badge/License-MIT-blue)](#license)

A collection of classes that represent resources, suited for delivery to a HTTP client. 

The benefit of these classes is a common interface that bundles the data stream with a bunch of metadata like mimetype, modification date, etc. 

This abstraction makes caching, lazy processing, media type conversion and other pipelined operations much more convenient than handling files. 



#### Example

```PHP
// In a Symfony Controller:
$res = Resource::fromFile('dir/my-file.txt');
$res->getMimetype(); // guessed mimetype
return new ResourceResponse($res); 

// Fetch a resource from an URL:
$res = Resource::fromUrl('http://foo.bar/my-file.txt');
$res->getMimetype(); // mimetype from server
$res->getLastModified(); // modification date from server

// Overriding a mimetype and the file name:
$res = Resource::fromFile('dir/my-file.txt', [
	'mimetype' => 'applicatio/octet-stream', 
	'filename' => 'text.txt'
]);
return new ResourceResponse($res); // will also set the appropriate content-disposition header

```
