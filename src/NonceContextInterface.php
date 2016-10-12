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
 * Nonce context interface. At the moment, we don't need any specific method, ArrayAccess is enough.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package Nonces
 * @license http://opensource.org/licenses/MIT MIT
 */
interface NonceContextInterface extends \ArrayAccess
{
}
