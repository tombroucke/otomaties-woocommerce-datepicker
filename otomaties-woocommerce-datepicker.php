<?php

/**
 * Plugin Name:       Otomaties WooCommerce Datepicker
 * Description:       Add a datepicker on the checkout page
 * Version:           1.5.5
 * Author:            Tom Broucke
 * Author URI:        https://tombroucke.be/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       otomaties-woocommerce-datepicker
 * Domain Path:       /resources/languages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    exit;
}

if (! file_exists($composer = __DIR__.'/vendor/autoload.php')) {
    return;
}

require_once $composer;

add_action('after_setup_theme', function () {
    if (! function_exists('Roots\bootloader')) {
        return;
    }

    \Roots\bootloader()->boot();
    \Roots\bootloader()
        ->getApplication()
        ->register(
            Otomaties\WooCommerce\Datepicker\Providers\DatepickerServiceProvider::class
        );
}, 20);
