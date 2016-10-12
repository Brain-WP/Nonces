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
 * Simple immutable nonce context implementation based on an arbitrary array storage, that can only
 * be set via constructor.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package Nonces
 * @license http://opensource.org/licenses/MIT MIT
 */
final class ArrayContext implements NonceContextInterface
{

    private $storage = [];

    /**
     * @param array $storage
     */
    public function __construct(array $storage)
    {
        $this->storage = $storage;
    }

    /**
     * @inheritdoc
     */
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->storage);
    }

    /**
     * @inheritdoc
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->storage[$offset] : null;
    }

    /**
     * Disabled.
     *
     * @param mixed $offset
     * @param mixed $value
     *
     * @throws \BadMethodCallException
     */
    public function offsetSet($offset, $value)
    {
        throw new \BadMethodCallException(
            sprintf("Can't call %s, %s is read only.", __METHOD__, __CLASS__)
        );
    }

    /**
     * Disabled.
     *
     * @param mixed $offset
     *
     * @throws \BadMethodCallException
     */
    public function offsetUnset($offset)
    {
        throw new \BadMethodCallException(
            sprintf("Can't call %s, %s is read only.", __METHOD__, __CLASS__)
        );
    }
}
