<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 * @package   Zend_Authentication
 */

namespace ZendTest\Authentication\Adapter;

use ZendTest\Authentication\TestAsset\Wrapper;

class HttpTest extends \PHPUnit_Framework_TestCase
{
    // @codingStandardsIgnoreStart
    /**
     * @var Wrapper
     */
    protected $_wrapper;
    // @codingStandardsIgnoreEnd

    public function setUp()
    {
        $config = [
            'accept_schemes' => 'basic',
            'realm'          => 'testing',
        ];

        $this->_wrapper = new Wrapper($config);
    }

    public function tearDown()
    {
        unset($this->_wrapper);
    }

    /**
     * @expectedException PHPUnit_Framework_Error_Deprecated
     */
    public function testProtectedMethodChallengeClientTriggersErrorDeprecated()
    {
        $this->_wrapper->_challengeClient();
    }
}
