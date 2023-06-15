<?php

namespace Otomaties\WooCommerce\Datepicker;

use function \Roots\bundle;
use Otomaties\WooCommerce\Datepicker\Facades\Options;

class Frontend
{
    public function enqueueScripts()
    {
        if (is_cart() || is_checkout()) {
            bundle('otomaties-woocommerce-datepicker', 'otomaties-woocommerce-datepicker')->enqueue();
        }
    }
    
    public function renderDatepicker($method, $index)
    {
        if (!is_checkout()) {
            return;
        }

        if ($method->get_method_id() !== app()->make('getChosenShippingMethod')) {
            return;
        }
        
        $datepickerId = Options::findDatepickerByShippingMethod($method->get_method_id());
        if ($datepickerId) {
            $datepicker = new Datepicker($datepickerId, Options::instance());
            $datepicker->render();
        }
    }

    public function dispatchJqueryEvents()
    {
        if (!is_cart() && !is_checkout()) {
            return;
        }
        ?>
        <script>
            const dispatchEvents = [
                'updated_checkout',
            ];
            for (const dispatchEvent of dispatchEvents) {
                jQuery( document.body ).on( dispatchEvent, function() {
                    document.body.dispatchEvent(new Event('js_' + dispatchEvent));
                });
            }
        </script>
        <?php
    }

    public function addDateRow($totalRows, $order, $tax_display)
    {
        $date = $order->get_meta('otom_wc_datepicker_date');

        if (!$date) {
            return $totalRows;
        }

        $dateTime = \DateTime::createFromFormat('Y-m-d', $date, new \DateTimeZone(wp_timezone_string()));
        $totalRows = collect($totalRows);
        $index = $totalRows->keys()->search('shipping');
        $index = $index === false ? $totalRows->count() : $index;
        $label = $order->get_meta('otom_wc_datepicker_label') != '' ? $order->get_meta('otom_wc_datepicker_label') : null;
        $totalRows->splice($index + 1, 0, [
            'otom_wc_datepicker_date' => [
                'label' => $label ?? __('Delivery/pickup date', 'otomaties-woocommerce-datepicker'),
                'value' => date_i18n(get_option('date_format'), $dateTime->getTimestamp()),
            ],
        ]);
        return $totalRows->toArray();
    }
}
