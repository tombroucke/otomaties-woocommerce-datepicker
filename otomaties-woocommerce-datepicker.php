<?php

use Roots\Acorn\Assets\Manifest;

/**
 * Plugin Name:       Otomaties WooCommerce Datepicker
 * Description:       Add a datepicker on the checkout page
 * Version:           0.1.0
 * Author:            Tom Broucke
 * Author URI:        https://tombroucke.be/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       otomaties-woocommerce-datepicker
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

add_filter('after_setup_theme', function () {
    if (file_exists($composer = __DIR__ . '/vendor/autoload.php')) {
        require_once $composer;
    }

    if (function_exists('Roots\bootloader')) {
        \Roots\bootloader()->boot(function (Roots\Acorn\Application $app) {
            $app->register(
                Otomaties\WooCommerce\Datepicker\Providers\DatepickerServiceProvider::class
            );
            
            $manifest = new Manifest(
                plugin_dir_path(__FILE__) . 'public',
                plugin_dir_url(__FILE__) . 'public',
                json_decode(file_get_contents(plugin_dir_path(__FILE__) . 'public/manifest.json'), true),
                json_decode(file_get_contents(plugin_dir_path(__FILE__) . 'public/entrypoints.json'), true)
            );
            $app->make('assets')->register('otomaties-woocommerce-datepicker', $manifest);
        });
    }
}, 20);
