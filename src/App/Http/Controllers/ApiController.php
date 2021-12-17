<?php

namespace PeakTowerTech\ModelsManager\App\Http\Controllers;

use Illuminate\Http\Request;
use PeakTowerTech\ModelsManager\App\Services\ModelsManager\ModelsManagerFacade;

class ApiController
{

    private function made($parentModelClass,$repository)
    {
        foreach (config('modelsManager.modelsConfigs')[$parentModelClass] ?? [] as $relationType => $models) {
            foreach ($models as $modelClass => $config){
                $repository[$modelClass] = [
                    'count' => isset($repository[$modelClass]) ? count($repository[$modelClass]) : "0",
                    'config' => $config
                ];
                $repository=$this->made($modelClass,$repository);
            }
        }
        return $repository;
    }


    public function getModelAndRelations($modelClass, $modelId)
    {
        $modelClass = urldecode($modelClass);
        $repository = ModelsManagerFacade::GetModelWithRelations(new $modelClass, $modelId);
        $jsonResponse=[
            'models'=>$this->made($modelClass,$repository),
        ];
        return response()->json($jsonResponse);
    }

    public function duplicate(Request $request){
        $mainModel=$request->mainModelClass::findOrFail($request->mainModelId);
        $parentMode=$request->parentModelClass::findOrFail($request->parentTargetModelId);
        return ModelsManagerFacade::Duplicate($mainModel,$parentMode);
    }

}
