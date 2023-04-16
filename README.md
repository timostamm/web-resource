# PHP Web Resources

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
