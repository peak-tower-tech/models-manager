<?php
namespace PeakTowerTech\ModelsManager\App\Services\ModelsManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Duplicate($mainModel,$parentModel=null,$allowedRelations=[])
 * @method static GetModelWithRelations($mainModel,$mainId)
 *
 * @see \PeakTowerTech\ModelsManager\App\Services\ModelsManager\ModelsManager
 */
class ModelsManagerFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    public static function getFacadeAccessor(): string
    {
        return 'ModelsManager';
    }
}

