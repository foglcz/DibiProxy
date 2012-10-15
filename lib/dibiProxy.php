<?php
/**
 * This file is part of the foglcz/DibiProxy github project.
 *
 * @author Pavel Ptacek
 * @class LGPL
 */

/**
 * The proxy class for dibi - use when you are using dibi:: classes with one connection.
 * This class needs to be called once, only for the connect method.
 *
 * @author Pavel Ptacek
 */
class dibiProxy extends dibi {
    /**
     * Use this function to connect, if you want to use dibi::setProxyOverride
     *
     * @param array $config
     * @param int $name
     * @return DibiConnection|void
     * @throws DibiProxyException
     */
    public static function connect($config = array(), $name = 0) {
        if($name !== 0) {
            throw new DibiProxyException('Named connection are not supported in this version of dibi, due to proxying issues.');
        }

        $connection = new DibiProxyConnection($config, $name);
        parent::setConnection($connection);
    }
}