<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Nonces package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Nonces\Tests;

use Brain\Nonces\RequestGlobalsContext;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package Nonces
 * @license http://opensource.org/licenses/MIT MIT
 */
class RequestGlobalsContextTest extends TestCase
{

    /**
     * Reset $_GET
     */
    protected function tearDown()
    {
        $_GET = [];
        parent::tearDown();
    }

    public function testOffsetGet()
    {
        $_GET = ['foo' => 'bar'];
        $context = new RequestGlobalsContext();

        self::assertSame('bar', $context['foo']);
        self::assertNull($context['bar']);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testOffsetSetImmutability()
    {
        $context = new RequestGlobalsContext();
        $context['bar'] = 'baz';
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testOffsetUnsetImmutability()
    {
        $_GET = ['foo' => 'bar'];
        $context = new RequestGlobalsContext();
        unset($context['foo']);
    }


}