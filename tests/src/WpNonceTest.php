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

use Brain\Monkey\Functions;
use Brain\Monkey\WP\Filters;
use Brain\Nonces\NonceContextInterface;
use Brain\Nonces\WpNonce;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package Nonces
 * @license http://opensource.org/licenses/MIT MIT
 */
class WpNonceTest extends TestCase
{

    /**
     * Mock `et_current_blog_id()` to always return 1.
     */
    protected function setUp()
    {
        Functions::when('get_current_blog_id')->justReturn(1);
        parent::setUp();
    }

    /**
     * Tests that nonce validation fails when given context passed does not contain nonce action
     * as key.
     */
    public function testValidateFailIfNoActionInContext()
    {
        $nonce = new WpNonce('foo');

        $context = \Mockery::mock(NonceContextInterface::class);
        $context->shouldReceive('offsetExists')->once()->with('foo')->andReturn(false);

        self::assertFalse($nonce->validate($context));
    }

    /**
     * Tests that nonce validation pass when given context passed contain nonce action key and
     * related value is what nonce expects.
     *
     */
    public function testValidate()
    {
        // `get_current_blog_id()` is mocked to return `1`
        Functions::expect('wp_hash')->with('foo1', 'nonce')->andReturn(md5('foo1'));

        Functions::expect('wp_verify_nonce')->with('nonce-value', md5('foo1'))->andReturn(true);

        $nonce = new WpNonce('foo');

        $context = \Mockery::mock(NonceContextInterface::class);
        $context->shouldReceive('offsetExists')->once()->with('foo')->andReturn(true);
        $context->shouldReceive('offsetGet')->once()->with('foo')->andReturn('nonce-value');

        self::assertTrue($nonce->validate($context));
    }

    /**
     * Tests that __toString() works as expected calling `wp_create_nonce()` and `wp_hash()` with
     * expected arguments.
     */
    public function testToString()
    {
        $hash = md5('foo1');

        // `get_current_blog_id()` is mocked to return `1`
        Functions::expect('wp_hash')->once()->with('foo1', 'nonce')->andReturn($hash);
        Functions::expect('wp_create_nonce')->once()->with(md5('foo1'))->andReturn(md5($hash));

        $nonce = new WpNonce('foo');

        self::assertSame(md5($hash), (string)$nonce);
    }

    /**
     * Tests that when both calling `wp_create_nonce()` and `wp_verify_nonce()` the nonce life
     * is filtered.
     */
    public function testLifeIsFiltered()
    {
        Functions::when('wp_hash')->alias('md5');
        Functions::when('wp_create_nonce')->alias('md5');
        Functions::when('wp_verify_nonce')->justReturn(true);

        // twice because the same filter must be added on create and on verify
        Filters::expectAdded('nonce_life')
            ->twice()
            ->with(\Mockery::type('Closure'))
            ->whenHappen(function (\Closure $closure) {
                self::assertSame(100, $closure());
            });

        $context = \Mockery::mock(NonceContextInterface::class);
        $context->shouldReceive('offsetExists')->once()->with('foo')->andReturn(true);
        $context->shouldReceive('offsetGet')->once()->with('foo')->andReturn('nonce-value');

        $nonce = new WpNonce('foo', 100);

        $nonce->validate($context);
        $nonce->__toString();
    }

}