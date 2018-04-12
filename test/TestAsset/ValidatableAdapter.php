<?php
/**
 * @see       https://github.com/zendframework/zend-authentication for the canonical source repository
 * @copyright Copyright (c) 2018 Zend Technologies USA Inc. (https://www.zend.com)
 * @license   https://github.com/zendframework/zend-authentication/blob/master/LICENSE.md New BSD License
 */

namespace ZendTest\Authentication\TestAsset;

use Zend\Authentication\Adapter\AbstractAdapter as AuthenticationAdapter;
use Zend\Authentication\Result as AuthenticationResult;

class ValidatableAdapter extends AuthenticationAdapter
{
    /**
     * @var int Authentication result code
     */
    private $code;

    /**
     * @param int $code
     */
    public function __construct($code = AuthenticationResult::SUCCESS)
    {
        $this->code = $code;
    }

    /**
     * {@inheritDoc}
     */
    public function authenticate()
    {
        return new AuthenticationResult($this->code, 'someIdentity');
    }
}
