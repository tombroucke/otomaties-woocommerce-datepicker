<?php

use Roots\Acorn\Application;

/**
 * Plugin Name:       Otomaties WooCommerce Datepicker
 * Description:       Add a datepicker on the checkout page
 * Version:           1.6.1
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
    if (! class_exists(\Roots\Acorn\Application::class)) {
        wp_die(
            __('You need to install Acorn to use this site.', 'domain'),
            '',
            [
                'link_url' => 'https://roots.io/acorn/docs/installation/',
                'link_text' => __('Acorn Docs: Installation', 'domain'),
            ]
        );
    }

    if (function_exists('app')) {
        app()->register(Otomaties\WooCommerce\Datepicker\Providers\DatepickerServiceProvider::class);
    } else {
        Application::configure()
            ->withProviders([
                Otomaties\WooCommerce\Datepicker\Providers\DatepickerServiceProvider::class,
            ])
            ->boot();
    }
}, 20);
