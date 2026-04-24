<?php

namespace TS\Web\Resource;


/**
 * @author Timo Stamm <ts@timostamm.de>
 * @license MIT https://opensource.org/licenses/MIT
 */
interface TemporaryResourceInterface extends ResourceInterface
{

	function dispose();
	
}