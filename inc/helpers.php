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

if (function_exists(__NAMESPACE__ . '\\formField')) {
    return;
}

/**
 * Returns HTML markup for a form hidden field that contains given nonce action and value.
 *
 * @param NonceInterface $nonce
 * @return string
 */
function formField(NonceInterface $nonce)
{
    return sprintf(
        '<input type="hidden" name="%s" value="%s" />',
        esc_attr($nonce->action()),
        esc_attr((string)$nonce)
    );
}

/**
 * Adds nonces action and value to a given URL.
 *
 * If URL is not provided, current URL is used.
 *
 * @param NonceInterface $nonce
 * @param string|null $url
 * @return string
 */
function nonceUrl(NonceInterface $nonce, $url = null)
{
    if (!$url || !is_string($url)) {
        $home_path = trim(parse_url(home_url(), PHP_URL_PATH), '/');
        $current_url_path = trim(add_query_arg([]), '/');
        if ($home_path && strpos($current_url_path, $home_path) === 0) {
            $current_url_path = substr($current_url_path, strlen($home_path));
        }
        $url = home_url(urldecode($current_url_path));
    }

    return esc_url_raw(add_query_arg($nonce->action(), (string)$nonce, $url));
}
