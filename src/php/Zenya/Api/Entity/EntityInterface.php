<?php

namespace Zenya\Api\Entity;

use Zenya\Api\Router;

interface EntityInterface
{
    
    /**
 	 * Import given array/object.
	 *
	 * @param array $definitions
	 * @return void
	 */
	public function _append(array $definitions=null);

    /**
 	 * Calls the underline entity.
	 *
	 * @param Router $route
	 * @return array
	 * @throws InvalidArgumentException 405
	 */
    public function _call(Router $route);

    /**
 	 * Parses the PHP docs.
	 *
	 * @return void
	 */
    function _parseDocs();

    /**
 	 * Gets the method
	 *
	 * @param string $name
	 * @return
	 */
    function getMethod($name);

    /**
     * Returns an array of method keys and action values.
     *
     * @param  array $array
     * @return array
     */
    function getActions();

}