<?php

namespace TS\Web\Resource;


/**
 * Local resources represent a file in the filesystem. 
 * They provide a path to the file via the getPath() method.
 * 
 * @author Timo Stamm <ts@timostamm.de>
 * @license MIT https://opensource.org/licenses/MIT
 */
interface FileResourceInterface extends ResourceInterface
{

	/**
	 *
	 * @return string full path to local file
	 */
	function getPath();
	
	
	/**
	 * 
	 * @param string $mode mode used to fopen()
	 * @param resource $context
	 */
	function open($mode, $context = null);
	
}
