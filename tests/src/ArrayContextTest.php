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

use Brain\Nonces\ArrayContext;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package Nonces
 * @license http://opensource.org/licenses/MIT MIT
 */
class ArrayContextTest extends TestCase
{

    public function testOffsetGet()
    {
        $context = new ArrayContext(['foo' => 'bar']);

        self::assertSame('bar', $context['foo']);
        self::assertNull($context['bar']);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testOffsetSetImmutability()
    {
        $context = new ArrayContext(['foo' => 'bar']);
        $context['bar'] = 'baz';
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testOffsetUnsetImmutability()
    {
        $context = new ArrayContext(['foo' => 'bar']);
        unset($context['foo']);
    }

}