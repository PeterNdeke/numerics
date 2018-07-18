<?php

/*
 * This file is for npay laravel integartion for developers.
 *
 * (c)peterNdeke <ndekepeter2015@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Numerics\Npay\Facades;

use Illuminate\Support\Facades\Facade;

class Npay extends Facade
{
    /**
     * Get the registered name of the component
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'npay';
    }
}
