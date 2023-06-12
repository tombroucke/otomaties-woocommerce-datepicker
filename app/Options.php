<?php

namespace Otomaties\WooCommerce\Datepicker;

use StoutLogic\AcfBuilder\FieldsBuilder;

class Options
{
    public function findDatepickerByShippingMethod(string $shippingMethod)
    {
        global $wpdb;
        
        $query = $wpdb->prepare("
            SELECT *
            FROM {$wpdb->prefix}options
            WHERE option_name LIKE %s
            AND option_value LIKE %s
            ",
            '%options_otomaties_wc_datepicker_%_shipping_methods%',
            '%' . $shippingMethod . '%'
        );

        $records = collect($wpdb->get_results($query));
        
        if ($records->isEmpty()) {
            return false;
        }

        $option_name = $records->first()->option_name;
        
        preg_match('/options_otomaties_wc_datepicker_([0-9]+)_shipping_methods/', $option_name, $matches);
        return $matches[1] ?? null;
    }

    public function disabledDays(string $datepickerId)
    {
        return get_field('otomaties_wc_datepicker_' . $datepickerId . '_disabled_days', 'option');
    }

    public function disabledDates(string $datepickerId)
    {
        return $this->getDateRepeaterField('otomaties_wc_datepicker_' . $datepickerId . '_disabled_dates');
    }

    public function enabledDates(string $datepickerId)
    {
        return $this->getDateRepeaterField('otomaties_wc_datepicker_' . $datepickerId . '_enabled_dates');
    }

    public function datepickerLabel(string $datepickerId)
    {
        return get_field('otomaties_wc_datepicker_' . $datepickerId . '_datepicker_label', 'option');
    }

    public function administrationLabel(string $datepickerId)
    {
        return get_field('otomaties_wc_datepicker_' . $datepickerId . '_administration_label', 'option');
    }

    public function buffer(string $datepickerId)
    {
        return get_field('otomaties_wc_datepicker_' . $datepickerId . '_buffer', 'option');
    }

    private function getDateRepeaterField(string $fieldName) {
        $repeater = [];
        $repeaterCount = get_option('options_' . $fieldName);
        for ($i = 0; $i < $repeaterCount; $i++) {
            $from = get_option('options_' . $fieldName . '_' . $i . '_from_date');
            $to = get_option('options_' . $fieldName . '_' . $i . '_to_date');
            $repeater[] = [
                'from' => $from,
                'to' => $to,
            ];
        }
        $repeater = collect($repeater)->map(function ($date) {
            return [
                'from' => $date['from'] ? substr($date['from'], 0, 4) . substr($date['from'], 4, 2) . substr($date['from'], 6, 2) : null,
                'to' => $date['to'] ? substr($date['to'], 0, 4) . substr($date['to'], 4, 2) . substr($date['to'], 6, 2) : null,
            ];
        });
        return $repeater->toArray();
    }
    
    public function addOptionsPage()
    {
        acf_add_options_page(
            array(
                'page_title'    => __('Datepicker', 'otomaties-woocommerce-datepicker'),
                'menu_title'    => __('Datepicker', 'otomaties-woocommerce-datepicker'),
                'menu_slug'     => 'otomaties-woocommerce-datepicker-settings',
                'icon_url'      => 'dashicons-airplane',
                'capability'    => 'manage_woocommerce',
                'redirect'      => false,
            )
        );
        return $this;
    }

    public function addOptionsFields()
    {
        $datepickers = new FieldsBuilder('otomaties-woocommerce-datepicker-datepickers', [
            'title' => __('Datepickers', 'otomaties-woocommerce-datepicker'),
        ]);


        $datepickers
            ->addRepeater('otomaties_wc_datepicker_datepickers', [
                'label' => __('Datepickers', 'otomaties-woocommerce-datepicker'),
                'layout' => 'block',
                'button_label' => __('Add datepicker', 'otomaties-woocommerce-datepicker'),
            ])
                ->addText('label', [
                    'label' => __('Label', 'otomaties-woocommerce-datepicker'),
                    'instructions' => __('For admin eyes only. This will not be displayed to clients.', 'otomaties-woocommerce-datepicker'),
                ])
            ->endRepeater();

        $datepickers->setLocation('options_page', '==', 'otomaties-woocommerce-datepicker-settings');
        acf_add_local_field_group($datepickers->build());


        $datepickersDetails = new FieldsBuilder('otomaties-woocommerce-datepicker-settings', [
            'title' => __('Datepickers details', 'otomaties-woocommerce-datepicker'),
        ]);


        $wcShippingMethods = WC()->shipping->get_shipping_methods();

        foreach ($this->datepickers() as $key => $label) {
                $datepickersDetails
                    ->addTab('otomaties_wc_datepicker_' . $key, [
                        'label' => $label,
                    ])
                    ->addSelect('otomaties_wc_datepicker_' . $key . '_shipping_methods', [
                        'label' => __('Shipping method', 'otomaties-woocommerce-datepicker'),
                        'choices' => array_map(function ($shippingMethod) {
                            return $shippingMethod->get_method_title();
                        }, $wcShippingMethods),
                        'multiple' => 1
                    ])
                    ->addText('otomaties_wc_datepicker_' . $key . '_administration_label', [
                        'label' => __('Administration abel', 'otomaties-woocommerce-datepicker'),
                        'instructions' => __('This will displayed in on the thankyou-page, e-mails and in the WooCommere backend', 'otomaties-woocommerce-datepicker'),
                        'default_value' => sprintf(__('%s date', 'otomaties-woocommerce-datepicker'), $label),
                    ])
                    ->addText('otomaties_wc_datepicker_' . $key . '_datepicker_label', [
                        'label' => __('Datepicker label', 'otomaties-woocommerce-datepicker'),
                        'instructions' => __('This will displayed right above the datepicker', 'otomaties-woocommerce-datepicker'),
                        'default_value' => sprintf(__('Choose a %s date', 'otomaties-woocommerce-datepicker'), strtolower($label)),
                    ])
                    ->addNumber('otomaties_wc_datepicker_' . $key . '_buffer', [
                        'label' => __('Buffer', 'otomaties-woocommerce-datepicker'),
                        'instructions' => __('The number of hours that should be added to the datepicker\'s min date', 'otomaties-woocommerce-datepicker'),
                        'default_value' => 0,
                    ])
                    ->addSelect('otomaties_wc_datepicker_' . $key . '_disabled_days', [
                        'label' => __('Disabled days', 'otomaties-woocommerce-datepicker'),
                        'multiple' => 1,
                        'ui' => 1,
                        'choices' => [
                            'monday' => __('Monday', 'otomaties-woocommerce-datepicker'),
                            'tuesday' => __('Tuesday', 'otomaties-woocommerce-datepicker'),
                            'wednesday' => __('Wednesday', 'otomaties-woocommerce-datepicker'),
                            'thursday' => __('Thursday', 'otomaties-woocommerce-datepicker'),
                            'friday' => __('Friday', 'otomaties-woocommerce-datepicker'),
                            'saturday' => __('Saturday', 'otomaties-woocommerce-datepicker'),
                            'sunday' => __('Sunday', 'otomaties-woocommerce-datepicker'),
                        ],
                    ])
                    ->addRepeater('otomaties_wc_datepicker_' . $key . '_disabled_dates', [
                        'label' => __('Disabled dates', 'otomaties-woocommerce-datepicker'),
                        'layout' => 'table',
                        'button_label' => __('Add date', 'otomaties-woocommerce-datepicker'),
                    ])
                        ->addDatepicker('from_date', [
                            'label' => __('Date from', 'otomaties-woocommerce-datepicker'),
                            'return_format' => 'Ymd',
                            'required' => true
                        ])
                        ->addDatepicker('to_date', [
                            'label' => __('Date to', 'otomaties-woocommerce-datepicker'),
                            'return_format' => 'Ymd',
                            'instructions' => __('Leave empty if you want to disable only one date', 'otomaties-woocommerce-datepicker'),
                            'required' => false
                        ])
                    ->endRepeater()
                    ->addRepeater('otomaties_wc_datepicker_' . $key . '_enabled_dates', [
                        'label' => __('Enabled dates', 'otomaties-woocommerce-datepicker'),
                        'layout' => 'table',
                        'button_label' => __('Add date', 'otomaties-woocommerce-datepicker'),
                    ])
                        ->addDatepicker('from_date', [
                            'label' => __('Date from', 'otomaties-woocommerce-datepicker'),
                            'return_format' => 'Ymd',
                            'required' => true
                        ])
                        ->addDatepicker('to_date', [
                            'label' => __('Date to', 'otomaties-woocommerce-datepicker'),
                            'return_format' => 'Ymd',
                            'instructions' => __('Leave empty if you want to disable only one date', 'otomaties-woocommerce-datepicker'),
                            'required' => false
                        ])
                    ->endRepeater();
        }

        $datepickersDetails->setLocation('options_page', '==', 'otomaties-woocommerce-datepicker-settings');
        acf_add_local_field_group($datepickersDetails->build());
    }
    
    private function datepickers() {
        global $wpdb;
        $datepickers = $wpdb->get_results("SELECT option_id, option_value FROM $wpdb->options WHERE option_name LIKE 'options_otomaties_wc_datepicker_datepickers_%_label'");
        return collect($datepickers)
            ->pluck('option_value', 'option_id')
            ->toArray();
    }
}
