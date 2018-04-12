<?php
/**
 * @see       https://github.com/zendframework/zend-authentication for the canonical source repository
 * @copyright Copyright (c) 2012-2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-authentication/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Authentication\TestAsset;

use Zend\Authentication\Adapter\AdapterInterface;
use Zend\Authentication\Result as AuthenticationResult;

class SuccessAdapter implements AdapterInterface
{
    public function authenticate()
    {
        return new AuthenticationResult(AuthenticationResult::SUCCESS, 'someIdentity');
    }
}
