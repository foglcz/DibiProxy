<?php
/**
 * This file is part of the foglcz/DibiProxy github project.
 *
 * @author Pavel Ptacek
 * @class LGPL
 */

/**
 * The dibi proxy connection class. Use when you want to use setProxyOverride() method
 *
 * @author Pavel Ptacek
 */
class DibiProxyConnection extends DibiConnection {
    /**
     * @param $ident
     * @throws DibiProxyException
     */
    public function setProxyOverride($ident) {
        try {
            $this->driver->setProxyOverride($ident);
        }
        catch(LogicException $e) {
            if(strpos($e->getMessage(), '::setProxyOverride()') !== false) {
                throw new DibiProxyException('The current connection does not use DibiProxyTrait. Use one of the Proxy* drivers.');
            }

            throw $e;
        }
    }
}