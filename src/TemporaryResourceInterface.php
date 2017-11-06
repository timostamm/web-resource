<?php

namespace TS\Web\Resource;


/**
 * @author Timo Stamm <ts@timostamm.de>
 * @license AGPLv3.0 https://www.gnu.org/licenses/agpl-3.0.txt
 */
interface TemporaryResourceInterface extends ResourceInterface
{

	function dispose();
	
}