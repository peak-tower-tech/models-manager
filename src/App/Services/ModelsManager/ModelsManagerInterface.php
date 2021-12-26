<?php

namespace PeakTowerTech\ModelsManager\App\Services\ModelsManager;

interface ModelsManagerInterface
{
    public function Duplicate($mainModel,$parentModel=null,$allowedRelations=[]);
}
