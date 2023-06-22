<?php

namespace Otomaties\WooCommerce\Datepicker;

class Admin
{
    public function addDateToOrderDetails($order) {
        $datepickerLabel = $order->get_meta('otom_wc_datepicker_label');
        $datepickerDate = $order->get_meta('otom_wc_datepicker_date');
        
        if ($datepickerDate === '') {
            return;
        }
        $datepickerLabel = $datepickerLabel === '' ? __('Datepicker', 'otomaties-woocommerce-datepicker') : $datepickerLabel;
        
        $datepickerDateTime = \DateTime::createFromFormat('Y-m-d', $datepickerDate);
        printf(
            '<p><strong>%s:</strong> %s</p>',
            $datepickerLabel,
            date_i18n(get_option('date_format'), $datepickerDateTime->getTimestamp())
        );
    }
}
