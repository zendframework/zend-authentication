<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\Authentication\TestAsset;

use Zend\Authentication\Adapter\AbstractAdapter as AuthenticationAdapter;
use Zend\Authentication\Result as AuthenticationResult;

class ValidatableAdapter extends AuthenticationAdapter
{
    /**
     * @var int Authentication result code
     */
    protected $code;

    /**
     * @param int $code
     */
    public function __construct($code = AuthenticationResult::SUCCESS)
    {
        $this->code = $code;
    }

    public function authenticate()
    {
        return new AuthenticationResult($this->code, 'someIdentity');
    }
}
