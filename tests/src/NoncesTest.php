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
use Brain\Nonces\ArrayContext;
use Brain\Nonces\WpNonce;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package Nonces
 * @license http://opensource.org/licenses/MIT MIT
 */
class NoncesTest extends TestCase
{

    /**
     * We mock here WP functions used to get current URL and append a query var to it.
     */
    private function mockUrlFunctions()
    {
        Functions::expect('home_url')->withNoArgs()->andReturn('http://example.com/subdir');

        Functions::expect('home_url')
            ->with(\Mockery::type('string'))
            ->andReturnUsing(function ($path) {
                return 'http://example.com/subdir' . '/' . ltrim($path, '/');
            });

        Functions::expect('add_query_arg')->with([])->andReturn('/subdir/foo');

        Functions::expect('add_query_arg')
            ->with(\Mockery::type('string'), \Mockery::type('string'), \Mockery::type('string'))
            ->andReturnUsing(function ($key, $value, $url) {
                $glue = strpos($url, '?') ? '&' : '?';

                return "{$url}{$glue}{$key}={$value}";
            });

        Functions::when('esc_url_raw')->alias(function ($url) {
            return filter_var($url, FILTER_SANITIZE_URL);
        });
    }

    /**
     * We mock wp_create_nonce and wp_verify_nonce to be alias of md5 / hash_equals
     */
    protected function setUp()
    {
        Functions::when('wp_create_nonce')->alias('md5');

        Functions::expect('wp_hash')->andReturnUsing(function ($value, $scheme) {
            return $scheme === 'nonce' ? md5($value) : '';
        });

        Functions::expect('wp_verify_nonce')->andReturnUsing(function ($nonce, $action) {
            return hash_equals(md5($action), $nonce);
        });

        parent::setUp();
    }

    /**
     * Reset $_GET
     */
    protected function tearDown()
    {
        $_GET = [];
        parent::tearDown();
    }

    /**
     * Test that nonces validate against a properly formed context passed to validation method.
     */
    public function testGivenContext()
    {
        Functions::when('get_current_blog_id')->justReturn(1);

        $nonce_a = new WpNonce('action-a-');
        $nonce_b = new WpNonce('action-b-');
        $context = new ArrayContext([
            'action-a-' => md5(md5('action-a-1')),
            'action-b-' => md5(md5('action-a-1'))
        ]);

        self::assertSame('action-a-', $nonce_a->action());
        self::assertSame('action-b-', $nonce_b->action());

        self::assertSame(md5(md5('action-a-1')), $nonce_a->__toString());
        self::assertSame(md5(md5('action-b-1')), $nonce_b->__toString());

        self::assertTrue($nonce_a->validate($context));
        self::assertFalse($nonce_b->validate($context));
    }

    /**
     * Test that nonces validate against context present in the $_GET (via URL query) when no
     * context is passed to validation method.
     */
    public function testGlobalsContextInUrl()
    {
        Functions::when('get_current_blog_id')->justReturn(1);
        $this->mockUrlFunctions();

        $nonce = new WpNonce('some-action');

        $url = \Brain\Nonces\nonceUrl($nonce);

        // this is pretty much what PHP does
        parse_str(parse_url($url, PHP_URL_QUERY), $_GET);

        self::assertTrue($nonce->validate());
    }

    /**
     * Test that nonces validate against context present in the $_GET (via form field query) when no
     * context is passed to validation method.
     */
    public function testGlobalsContextInField()
    {
        Functions::when('get_current_blog_id')->justReturn(1);
        Functions::when('esc_attr')->alias(function ($url) {
            return filter_var($url, FILTER_SANITIZE_STRING);
        });

        $nonce = new WpNonce('some-action');

        $field = \Brain\Nonces\formField($nonce);

        $name = preg_match('~name="([^"]+)"~', $field, $name_matches);
        $value = preg_match('~value="([^"]+)"~', $field, $value_matches);
        $_GET = $name && $value ? [$name_matches[1] => $value_matches[1]] : [];

        self::assertTrue($nonce->validate());
    }

    /**
     * Test that nonces don't validate when blog id is different during nonce creation / validation.
     */
    public function testFailingWhenDifferentBlogId()
    {
        Functions::expect('get_current_blog_id')->andReturn(1, 2, 3, 4, 5, 6);

        $this->mockUrlFunctions();

        $nonce = new WpNonce('some-action');

        $url = \Brain\Nonces\nonceUrl($nonce);

        // this is pretty much what PHP does
        parse_str(parse_url($url, PHP_URL_QUERY), $_GET);

        self::assertFalse($nonce->validate());
    }


}