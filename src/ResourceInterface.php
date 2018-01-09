<?php

namespace TS\Web\Resource;


/**
 *
 * @author Timo Stamm <ts@timostamm.de>
 * @license AGPLv3.0 https://www.gnu.org/licenses/agpl-3.0.txt
 */
interface ResourceInterface
{

	/**
	 *
	 * @return string
	 */
	public function getFilename();

	/**
	 *
	 * @return string mime type
	 */
	public function getMimetype();

	/**
	 *
	 * @return \DateTimeInterface
	 */
	public function getLastModified();

	/**
	 *
	 * @return string|NULL
	 */
	public function getHash();

	/**
	 *
	 * @return resource
	 */
	public function getStream($context = null);

	/**
	 * Size in bytes.
	 *
	 * @return int 
	 */
	public function getLength();

	/**
	 * Optional attributes. Should be serializable. 
	 * 
	 * @return array 
	 */
	public function getAttributes(): array;

}

