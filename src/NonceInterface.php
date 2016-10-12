<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Brain Nonces package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Brain\Nonces;

/**
 * Interface for all nonce implementations.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package Brain\Nonces
 * @license http://opensource.org/licenses/MIT MIT
 */
interface NonceInterface
{
    /**
     * Returns the nonce action as string.
     *
     * @return string
     */
    public function action();

    /**
     * Returns the nonce value as string.
     *
     * @return string
     */
    public function __toString();

    /**
     * Validates the nonce against an optionally given context.
     *
     * What to do in case of missing context is left to implementations.
     *
     * Custom implementation of context interface can provide different values to be used for
     * validation.
     *
     * @param NonceContextInterface $context
     * @return bool
     */
    public function validate(NonceContextInterface $context = null);
}
