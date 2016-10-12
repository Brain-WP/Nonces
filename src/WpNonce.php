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
 * WordPress-specific nonce implementation.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package Brain\Nonces
 * @license http://opensource.org/licenses/MIT MIT
 */
final class WpNonce implements NonceInterface
{

    /**
     * @var string
     */
    private $action;

    /**
     * @var int
     */
    private $life;

    /**
     * Constructor. Save properties as instance variables.
     *
     * We allow to customize nonce Time To Live, defaulting to 30 minutes (1800 seconds) that
     * is much less than the 24 hours of WordPress defaults.
     *
     * @param string $action
     * @param int $life
     */
    public function __construct($action = '', $life = 1800)
    {
        $this->action = is_string($action) ? $action : '';
        $this->life = is_numeric($life) ? (int)$life : 1800;
    }

    /**
     * @inheritdoc
     */
    public function action()
    {
        return $this->action;
    }

    /**
     * Validates the nonce against given context.
     *
     * When not provided, context defaults to `RequestGlobalsContext`, so that value is searched
     * in super globals.
     *
     * We need to filter the nonce life and remove the filter afterwards, because WP does not
     * allow to filter nonce by action (yet? @link https://core.trac.wordpress.org/ticket/35188)
     *
     * @param NonceContextInterface $context
     * @return bool
     */
    public function validate(NonceContextInterface $context = null)
    {
        $context or $context = new RequestGlobalsContext();

        $value = $context->offsetExists($this->action) ? $context[$this->action] : '';
        if (!$value || !is_string($value)) {
            return false;
        }

        $lifeFilter = $this->lifeFilter();

        add_filter('nonce_life', $lifeFilter);
        $valid = wp_verify_nonce($value, $this->hashedAction());
        remove_filter('nonce_life', $lifeFilter);

        return (bool)$valid;
    }

    /**
     * Returns the nonce string built with WordPress core function.
     *
     * We need to filter the nonce life and remove the filter afterwards, because WP does not
     * allow to filter nonce by action (yet? @link https://core.trac.wordpress.org/ticket/35188)
     *
     * @return string Nonce value.
     */
    public function __toString()
    {
        $lifeFilter = $this->lifeFilter();

        add_filter('nonce_life', $lifeFilter);
        $value = wp_create_nonce($this->hashedAction());
        remove_filter('nonce_life', $lifeFilter);

        return $value;
    }

    /**
     * Returns the callback that will be used to filter nonce life.
     *
     * @return \Closure
     */
    private function lifeFilter()
    {
        return function () {
            return $this->life;
        };
    }

    /**
     * Returns an hashed version of the action.
     *
     * Current blog id is appended to nonce action to make nonce blog specific.
     * WordPress will hash the action, so we could avoid do the hashing here.
     * However, unlike WordPress, we don't have a nonce "key" in URL or form fields, we use the
     * action for that, so nonce action publicly clearly accessible.
     * For this reason we do hash to make sure that trace back the nonce value from the action
     * is as much hard as possible.
     * This relies on a strong salt, which is required anyway for good WP security.
     *
     * @return string
     */
    private function hashedAction()
    {
        return wp_hash($this->action . get_current_blog_id(), 'nonce');
    }
}
