<?php

namespace PeakTowerTech\ModelsManager\App\Http\Controllers;

class DashboardController
{
    public function index()
    {
        return view('ModelsManager::Dashboard',[
            'models'=>config('modelsManager.modelsConfigs'),
        ]);
    }

}
