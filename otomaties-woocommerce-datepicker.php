<?php

/**
 * Plugin Name:       Otomaties WooCommerce Datepicker
 * Description:       Add a datepicker on the checkout page
 * Version:           1.4.0
 * Author:            Tom Broucke
 * Author URI:        https://tombroucke.be/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       otomaties-woocommerce-datepicker
 * Domain Path:       /resources/languages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

add_action('after_setup_theme', function () {
    if (file_exists($composer = __DIR__ . '/vendor/autoload.php')) {
        require_once $composer;
    }

    if (function_exists('Roots\bootloader')) {
        \Roots\bootloader()->boot(function (Roots\Acorn\Application $app) {
            $app->register(
                Otomaties\WooCommerce\Datepicker\Providers\DatepickerServiceProvider::class
            );
        });
    }
}, 20);
