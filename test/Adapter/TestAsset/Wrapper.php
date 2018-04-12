<?php
/**
 * @see       https://github.com/zendframework/zend-authentication for the canonical source repository
 * @copyright Copyright (c) 2017 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   https://github.com/zendframework/zend-authentication/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Authentication\Adapter\TestAsset;

use Zend\Authentication\Adapter;

class Wrapper extends Adapter\Http
{
    public function __call($method, $args)
    {
        return call_user_func_array([$this, $method], $args);
    }
}
