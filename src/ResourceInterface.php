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
	 * @return \DateTime
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
	 *
	 * @return int size in bytes
	 */
	public function getLength();

}

