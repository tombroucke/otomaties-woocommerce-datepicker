<?php

namespace Otomaties\WooCommerce\Datepicker;

use Otomaties\WooCommerce\Datepicker\Facades\Options;

class Api
{
    public function registerRoutes() {
        register_rest_route('otomaties-woocommerce-datepicker/v1', '/datepicker/(?P<datepicker_id>\d+)/enabled-dates', [
            'methods' => 'GET',
            'callback' => [$this, 'getDatepickerEnabledDates'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function getDatepickerEnabledDates(\WP_REST_Request $request) {
        $datepickerId = $request->get_param('datepicker_id');
        $month = $request->get_param('month');
        $year = $request->get_param('year');
        $datepicker = new Datepicker($datepickerId, Options::instance());
        wp_send_json($datepicker->enabledDatesFor($month, $year));
        exit();
    }
}
