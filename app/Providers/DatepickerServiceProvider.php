<?php

namespace Otomaties\WooCommerce\Datepicker\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Otomaties\WooCommerce\Datepicker\Datepicker;
use Roots\Acorn\Assets\Manifest;

class DatepickerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Otomaties\WooCommerce\Datepicker\Datepicker', function () {
            $options = $this->app->make('Otomaties\WooCommerce\Datepicker\Options');
            $chosenShippingMethod = $this->app->make('getChosenShippingMethod');
            $datepickerId = $options->findDatepickerByShippingMethod($chosenShippingMethod);

            return $datepickerId ? new Datepicker($datepickerId, $options) : null;
        });

        $singletons = [
            'Otomaties\WooCommerce\Datepicker\Options',
            'Otomaties\WooCommerce\Datepicker\Frontend',
            'Otomaties\WooCommerce\Datepicker\Admin',
            'Otomaties\WooCommerce\Datepicker\Checkout',
            'Otomaties\WooCommerce\Datepicker\RestApi',
        ];
        foreach ($singletons as $singleton) {
            $this->app->singleton($singleton, function () use ($singleton) {
                return new $singleton;
            });
        }

        $this->app->bind('getChosenShippingMethod', function () {
            $chosenDatepickerMethod = collect(WC()->session->get('chosen_shipping_methods'))->first();

            return Str::before($chosenDatepickerMethod, ':');
        });

        $manifest = new Manifest(
            dirname(plugin_dir_path(__FILE__), 2).'/public',
            plugins_url('public', dirname(__DIR__)),
            json_decode(file_get_contents(dirname(plugin_dir_path(__FILE__), 2).'/public/manifest.json'), true),
            json_decode(file_get_contents(dirname(plugin_dir_path(__FILE__), 2).'/public/entrypoints.json'), true)
        );
        $this->app->make('assets')->register('otomaties-woocommerce-datepicker', $manifest);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        load_plugin_textdomain('otomaties-woocommerce-datepicker', false, dirname(plugin_basename(__FILE__), 3).'/resources/languages/');

        $this->loadViewsFrom(
            dirname(__DIR__, 2).'/resources/views',
            'Otomaties\Woocommerce\Datepicker',
        );

        if ($this->isWooCommerceActive() && $this->isAcfActive()) {
            $this->registerActionsAndFilters();
        } elseif (is_admin()) {
            if (! $this->isWooCommerceActive()) {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error is-dismissible"><p>WooCommerce is not active. Please activate WooCommerce to use the Otomaties WooCommerce Datepicker plugin.</p></div>';
                });
            } elseif (! $this->isAcfActive()) {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error is-dismissible"><p>Advanced Custom Fields is not active. Please activate Advanced Custom Fields to use the Otomaties WooCommerce Datepicker plugin.</p></div>';
                });
            }
        }
    }

    public function registerActionsAndFilters()
    {
        $frontend = $this->app->make('Otomaties\WooCommerce\Datepicker\Frontend');
        add_action('wp_enqueue_scripts', [$frontend, 'enqueueScripts']);
        add_action('woocommerce_after_shipping_rate', [$frontend, 'renderDatepicker'], 10, 2);
        add_action('wp_footer', [$frontend, 'dispatchJqueryEvents']);
        add_filter('woocommerce_get_order_item_totals', [$frontend, 'addDateRow'], 10, 3);

        $options = $this->app->make('Otomaties\WooCommerce\Datepicker\Options');
        add_action('acf/init', [$options, 'addOptionsPage'], 10, 2);
        add_action('acf/init', [$options, 'addOptionsFields'], 10, 2);
        add_action('acf/save_post', [$options, 'cleanUpInactiveDatepickers']);

        $checkout = $this->app->make('Otomaties\WooCommerce\Datepicker\Checkout');
        add_action('woocommerce_after_checkout_validation', [$checkout, 'validate'], 10, 2);
        add_action('woocommerce_checkout_update_order_review', [$checkout, 'saveDateToSession']);
        add_action('woocommerce_before_checkout_process', [$checkout, 'updateSession']);
        add_action('woocommerce_checkout_order_processed', [$checkout, 'saveDatepickerDate'], 10, 3);

        $admin = $this->app->make('Otomaties\WooCommerce\Datepicker\Admin');
        add_action('woocommerce_admin_order_data_after_shipping_address', [$admin, 'addDateToOrderDetails']);
        add_action('woocommerce_admin_shipping_fields', [$admin, 'addDatepickerToShippingFields'], 10, 3);
        add_action('woocommerce_process_shop_order_meta', [$admin, 'saveDatepickerShippingFields']);
        add_filter('woocommerce_email_classes', [$admin, 'addDatepickerChangedEmail']);
        add_filter('wc_get_template', [$admin, 'datepickerChangeEmailTemplate'], 10, 4);

        $restApi = $this->app->make('Otomaties\WooCommerce\Datepicker\RestApi');
        add_action('rest_api_init', [$restApi, 'addRoutes']);

    }

    private function isWooCommerceActive()
    {
        return class_exists('WooCommerce');
    }

    private function isAcfActive()
    {
        return class_exists('ACF');
    }
}
