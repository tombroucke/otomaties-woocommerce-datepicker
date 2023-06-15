<?php

namespace Otomaties\WooCommerce\Datepicker\Facades;

use Illuminate\Support\Facades\Facade;

class Datepicker extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'Otomaties\WooCommerce\Datepicker\Datepicker';
    }
}
