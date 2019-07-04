<?php

namespace LBHurtado\SimpleOMR;

use Illuminate\Support\Facades\Facade;

/**
 * @see \LBHurtado\SimpleOMR\Skeleton\SkeletonClass
 */
class SimpleOMRFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'simpleomr';
    }
}
