<?php

namespace TS\Web\Resource;


/**
 * Local resources represent a file in the filesystem. 
 * They provide a path to the file via the getPath() method.
 * 
 * @author Timo Stamm <ts@timostamm.de>
 * @license AGPLv3.0 https://www.gnu.org/licenses/agpl-3.0.txt
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
