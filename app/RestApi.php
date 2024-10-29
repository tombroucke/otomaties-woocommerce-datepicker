<?php

namespace Otomaties\WooCommerce\Datepicker;

use Otomaties\WooCommerce\Datepicker\Facades\Options;

class RestApi
{
    public function addRoutes()
    {
        register_rest_route('otomaties-woocommerce-datepicker/v1', '/timeslots', [
            'methods' => 'GET',
            'callback' => [$this, 'getTimeslots'],
            'permission_callback' => '__return_true',
        ]);
    }

    public function getTimeslots($request)
    {
        $date = $request->get_param('date');
        $datepickerId = $request->get_param('datepicker_id');

        if (! $date || ! $datepickerId) {
            return new \WP_Error('missing_params', __('Missing required parameters', 'otomaties-woocommerce-datepicker'), ['status' => 400]);
        }

        try {
            $dateTime = \DateTime::createFromFormat('Y-m-d', $date);
        } catch (\Exception $e) {
            return new \WP_Error('invalid_date', __('Invalid date format', 'otomaties-woocommerce-datepicker'), ['status' => 400]);
        }

        $datepicker = new Datepicker($datepickerId, Options::instance());
        $timeslots = $datepicker->timeslots($dateTime);

        return rest_ensure_response($timeslots->flatten()->toArray());
    }
}
