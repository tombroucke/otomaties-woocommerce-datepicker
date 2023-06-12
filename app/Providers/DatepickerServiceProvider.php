<?php

namespace Otomaties\WooCommerce\Datepicker\Providers;

use DateTime;
use function Roots\bundle;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;
use Otomaties\WooCommerce\Datepicker\Options;
use Otomaties\WooCommerce\Datepicker\Datepicker;
use Otomaties\WooCommerce\Datepicker\Facades\Options as OptionsFacade;
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
        $this->app->singleton('Otomaties\Woocommerce\Datepicker\Datepicker', function () {
            $options = $this->app->make('Otomaties\Woocommerce\Datepicker\Options');
            $datepickerId = $options->findDatepickerByShippingMethod($this->getChosenShippingMethod());
            return $datepickerId ? new Datepicker($datepickerId, $options) : null;
        });
        
        $this->app->singleton('Otomaties\Woocommerce\Datepicker\Options', function () {
            return new Options();
        });
        
        $manifest = new Manifest(
            dirname(plugin_dir_path(__FILE__), 2) . '/public',
            plugins_url('public', dirname(__DIR__)),
            json_decode(file_get_contents(dirname(plugin_dir_path(__FILE__), 2) . '/public/manifest.json'), true),
            json_decode(file_get_contents(dirname(plugin_dir_path(__FILE__), 2) . '/public/entrypoints.json'), true)
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
        load_plugin_textdomain('otomaties-woocommerce-datepicker', false, dirname(plugin_basename(__FILE__), 3) . '/resources/languages/');
        
        $this->loadViewsFrom(
            dirname(__DIR__, 2) . '/resources/views',
            'Otomaties\Woocommerce\Datepicker',
        );
        
        add_action('wp_enqueue_scripts', function () {
            if (is_checkout()) {
                bundle('otomaties-woocommerce-datepicker', 'otomaties-woocommerce-datepicker')->enqueue();
            }
        });
        
        add_action('acf/init', function () {
            OptionsFacade::addOptionsPage()->addOptionsFields();
        });
        
        add_action('woocommerce_after_checkout_validation', function ($fields, $errors) {
            if (!isset($_POST['otomaties-woocommerce-datepicker'])) {
                return;
            }
            
            $timeZone = new \DateTimeZone(wp_timezone_string());
            $dateTime = DateTime::createFromFormat('Y-m-d', $_POST['otomaties-woocommerce-datepicker'], $timeZone);
            $invalidReason = app()->make('Otomaties\Woocommerce\Datepicker\Datepicker')->isDateInvalid($dateTime);
            if ($invalidReason) {
                $errors->add('validation', $invalidReason);
            }
        }, 10, 2);
        
        add_action('rest_api_init', function () {
            register_rest_route('otomaties-woocommerce-datepicker/v1', '/datepicker/(?P<datepicker_id>\d+)/enabled-dates', [
                'methods' => 'GET',
                'callback' => function (\WP_REST_Request $request) {
                    $datepickerId = $request->get_param('datepicker_id');
                    $month = $request->get_param('month');
                    $year = $request->get_param('year');
                    $datepicker = new Datepicker($datepickerId, $this->app->make('Otomaties\Woocommerce\Datepicker\Options'));
                    wp_send_json($datepicker->enabledDatesFor($month, $year));
                    exit();
                },
                'permission_callback' => '__return_true',
            ]);
        });
        
        add_action('woocommerce_after_shipping_rate', function ($method, $index) {
            if ($method->get_method_id() !== $this->getChosenShippingMethod()) {
                return;
            }
            $datepickerId = OptionsFacade::findDatepickerByShippingMethod($method->get_method_id());
            if ($datepickerId) {
                $datepicker = new Datepicker($datepickerId, $this->app->make('Otomaties\Woocommerce\Datepicker\Options'));
                $datepicker->render();
            }
        }, 10, 2);
    }
    
    private function getChosenShippingMethod()
    {
        $chosenDatepickerMethod = collect(WC()->session->get("chosen_shipping_methods"))->first();
        return Str::before($chosenDatepickerMethod, ':');
    }
}
