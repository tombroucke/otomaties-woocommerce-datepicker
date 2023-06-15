<?php

namespace Otomaties\WooCommerce\Datepicker;

use Illuminate\Support\Str;
use Otomaties\WooCommerce\Datepicker\Facades\Options;
use Otomaties\WooCommerce\Datepicker\Facades\Datepicker;

class Checkout
{
    public function validate($fields, $errors) {
        if (!isset($_POST['otomaties-woocommerce-datepicker--date'])) {
            return;
        }
        
        $timeZone = new \DateTimeZone(wp_timezone_string());
        $dateTime = \DateTime::createFromFormat('Y-m-d', $_POST['otomaties-woocommerce-datepicker--date'], $timeZone);
        $invalidReason = Datepicker::isDateInvalid($dateTime);
        if ($invalidReason) {
            $errors->add('validation', $invalidReason);
        }
    }

    public function saveDateToSession($data) {
        parse_str($data, $postData);
        $date = $postData['otomaties-woocommerce-datepicker--date'] ?? null;
        $id = $postData['otomaties-woocommerce-datepicker--id'] ?? null;
        if ($date && $id) {
            WC()->session->set('otomaties_woocommerce_datepicker_' . $id . '_date', wc_clean(wp_unslash($date)));
        }
    }

    public function updateSession() {
        $date = $_POST['otomaties-woocommerce-datepicker--date'] ?? null;
        $id = $_POST['otomaties-woocommerce-datepicker--id'] ?? null;
        if ($date && $id) {
            WC()->session->set('otomaties_woocommerce_datepicker_' . $id . '_date', wc_clean(wp_unslash($date)));
        }
    }

    public function saveDatepickerDate($orderId, $data, $order) {
        $datepickerId = $_POST['otomaties-woocommerce-datepicker--id'] ?? null;
        $datepickerDate = $_POST['otomaties-woocommerce-datepicker--date'] ?? null;
        if ($datepickerId) {
            $datepicker = new \Otomaties\WooCommerce\Datepicker\Datepicker($datepickerId, Options::instance());
            update_post_meta($orderId, 'otom_wc_datepicker_id', wc_clean(wp_unslash($datepickerId)));
            update_post_meta($orderId, 'otom_wc_datepicker_label', $datepicker->administrationLabel());
        }
        if ($datepickerDate) {
            update_post_meta($orderId, 'otom_wc_datepicker_date', wc_clean(wp_unslash($datepickerDate)));
        }

        $sessionData = collect(WC()->session->get_session_data());
        $sessionData
            ->filter(function ($value, $key) {
                return Str::startsWith($key, 'otomaties_woocommerce_datepicker');
            })
            ->keys()
            ->each(function ($key) use ($sessionData) {
                WC()->session->__unset($key);
            });
    }
}
