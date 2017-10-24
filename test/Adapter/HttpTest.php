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

use PHPUnit\Framework\Error\Deprecated;
use PHPUnit\Framework\TestCase;

class HttpTest extends TestCase
{
    /**
     * @var TestAsset\Wrapper
     */
    private $wrapper;

    public function setUp()
    {
        $config = [
            'accept_schemes' => 'basic',
            'realm'          => 'testing',
        ];

        $this->wrapper = new TestAsset\Wrapper($config);
    }

    public function tearDown()
    {
        unset($this->wrapper);
    }

    public function testProtectedMethodChallengeClientTriggersErrorDeprecated()
    {
        $this->expectException(Deprecated::class);
        $this->wrapper->_challengeClient();
    }
}
