<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Nonces package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Nonces;

/**
 * A nonce context that populates its internal storage with values coming from `$_GET` and `$_POSt`
 * super globals.
 *
 * Used as default in `WpNonce` whe no context is given, it allows to simplify the validation of
 * nonce available a s URL query variable or form variables, which are the most common usage of
 * nonces in WordPress context.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package Nonces
 * @license http://opensource.org/licenses/MIT MIT
 */
final class RequestGlobalsContext implements NonceContextInterface
{

    /**
     * @var ArrayContext context
     */
    private $context;

    /**
     * We don't use `$_REQUEST` because, by default, in PHP it gives precedence to `$_GET` over
     * `$_POST` in POST requests, and being dependant on `request_order` / `variables_order` ini
     * configurations it is not consistent across systems.
     */
    public function __construct()
    {
        $http_method = empty($_SERVER['REQUEST_METHOD']) ? null : $_SERVER['REQUEST_METHOD'];
        $is_post = is_string($http_method) && strtoupper($http_method) === 'POST';
        $request = $is_post ? array_merge($_GET, $_POST) : $_REQUEST;

        $this->context = new ArrayContext($request);
    }

    /**
     * Delegates to encapsulated context.
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->context->offsetExists($offset);
    }

    /**
     * Delegates to encapsulated context.
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->context->offsetGet($offset);
    }

    /**
     * Delegates to encapsulated context.
     *
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->context->offsetSet($offset, $value);
    }

    /**
     * DDelegates to encapsulated context.
     *
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        $this->context->offsetUnset($offset);
    }
}
