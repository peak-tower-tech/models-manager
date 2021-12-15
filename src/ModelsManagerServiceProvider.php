<?php

namespace PeakTowerTech\ModelsManager;

use Illuminate\Support\ServiceProvider;
use PeakTowerTech\ModelsManager\App\Services\ModelsManager\ModelsManager;
use PeakTowerTech\ModelsManager\App\Services\ModelsManager\ModelsManagerInterface;
class ModelsManagerServiceProvider extends ServiceProvider
{

    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/Resources/Views/','ModelsManager');
        $this->loadRoutesFrom(__DIR__.'/Routes/Web.php');
    }

    public function register()
    {
        $this->app->bind(ModelsManagerInterface::class,ModelsManager::class);

        $this->app->bind('ModelsManager',ModelsManagerInterface::class);
    }
}
