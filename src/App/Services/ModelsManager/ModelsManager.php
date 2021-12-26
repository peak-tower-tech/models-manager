<?php

namespace PeakTowerTech\ModelsManager\App\Services\ModelsManager;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PeakTowerTech\ModelsManager\App\Exceptions\DuplicatedModelNotFoundException;

class ModelsManager implements ModelsManagerInterface
{

    private Collection $duplicates;
    private array $repository;
    private string $primaryKeyName;
    private array $models;

    public function __construct()
    {
        $this->duplicates = collect();
        $this->repository = [];
        $this->primaryKeyName = config('modelsManager.defaultPrimaryKeyName');
        $this->models = config('modelsManager.modelsConfigs');
    }

    private function getModelConfig($model)
    {
        $class = get_class($model);
        $config = isset($this->models[$class]) ? $this->models[$class] : null;
        if (is_null($config) || !(bool)count($config)) return false;
        return $config;
    }

    private function getDuplicateCallBackFunc($relationConfig): Closure
    {
        if (isset($relationConfig['duplicateCallbackFunc'])) return $relationConfig['duplicateCallbackFunc'];

        return function ($mainModel, $parentModel, $duplicates) use ($relationConfig) {
            $duplicate_key_values = $mainModel->toArray();
            unset($duplicate_key_values[$this->primaryKeyName]);
            $duplicate_key_values[$relationConfig['foreignKey']] = $parentModel[$relationConfig['localKey']];
            return $duplicate_key_values;
        };
    }


    private function duplicateRelationsModels($relation, $mainModel, $duplicatedModel, $config, $allowedRelations)
    {

        foreach ($config[$relation] ?? [] as $relationClass => $relationConfig) {
            if (!in_array($relationClass, $allowedRelations)) continue;

            $relationConfig['foreignKey'] = $relationConfig['foreignKey'] ?? $mainModel->getForeignKey();
            $relationConfig['localKey'] = $relationConfig['localKey'] ?? $mainModel->getKeyName();

            $queryBuilder = $mainModel->$relation($relationClass, $relationConfig['foreignKey'], $relationConfig['localKey']);
            if (isset($relationConfig['queryBuilderCallbackFunc'])) {
                $queryBuilder == $relationConfig['queryBuilderCallbackFunc']($queryBuilder);
            }

            if (!(bool)$queryBuilder->count()) continue;

            $duplicateCallbackFunc = $this->getDuplicateCallBackFunc($relationConfig);

            $relationModels = $queryBuilder->get();

            if ($this->getModelConfig($relationModels->first())) {
                foreach ($relationModels as $relationModel) {
                    $this->makeDuplicateWithRelations(
                        $relationModel,
                        $duplicatedModel,
                        $duplicateCallbackFunc,
                        $allowedRelations
                    );
                }
            } else {
                foreach ($relationModels as $relationModel) {
                    $this->makeDuplicate($relationModel, $duplicatedModel, $duplicateCallbackFunc);
                }
            }

        }

    }

    private function createAndCollectStats($mainModel, $newModelKeysValues)
    {
        $class = get_class($mainModel);
        $duplicate = [
            'class' => $class,
            'mainId' => $mainModel->{$this->primaryKeyName},
        ];


        $newModel = (bool)count($newModelKeysValues) ? $class::create($newModelKeysValues) : null;

        if ($newModel) $duplicate['duplicateId'] = $newModel->{$this->primaryKeyName};

        $this->duplicates->push($duplicate);

        return $newModel;

    }


    private function makeDuplicate($mainModel, $parentModel = null, $duplicateCallbackFunc = null)
    {
        $duplicates = $this->duplicates;
        if (is_null($duplicateCallbackFunc)) {
            $duplicateCallbackFunc = function ($mainModel, $parentModel = null, $duplicates) {
                $duplicate_key_values = $mainModel->toArray();
                unset($duplicate_key_values[$this->primaryKeyName]);
                return $duplicate_key_values;
            };
        }

        $newModelKeysValues = $duplicateCallbackFunc($mainModel, $parentModel, $duplicates);
        if ($newModelKeysValues) return $this->createAndCollectStats($mainModel, $newModelKeysValues);
    }

    private function makeDuplicateWithRelations($mainModel, $parentModel = null, $callbackFunction = null, $allowedRelations = [])
    {
        $duplicatedModel = $this->makeDuplicate($mainModel, $parentModel, $callbackFunction);
        $config = $this->getModelConfig($duplicatedModel);
        if ($config) {
            $this->duplicateRelationsModels('hasMany', $mainModel, $duplicatedModel, $config, $allowedRelations);
            $this->duplicateRelationsModels('hasOne', $mainModel, $duplicatedModel, $config, $allowedRelations);
        }
    }

    public function Duplicate($mainModel, $parentModel = null, $allowedRelations = [])
    {
        DB::beginTransaction();
        try {
            $config = $this->getModelConfig($mainModel);
            if ($config) {
                $this->duplicateRelationsModels('hasMany', $mainModel, $parentModel, $config, $allowedRelations);
                $this->duplicateRelationsModels('hasOne', $mainModel, $parentModel, $config, $allowedRelations);
            }
            DB::commit();
        } catch (DuplicatedModelNotFoundException $duplicatedModelNotFoundException) {
            DB::rollback();
            dd(
                $duplicatedModelNotFoundException,
                $this->duplicates->groupBy('class')->toJson(),
//                $this->duplicates->groupBy('class'),
            );
        } catch (\Throwable $throwable) {
            DB::rollback();
            dd(
                $throwable instanceof DuplicatedModelNotFoundException,
                $throwable,
//                $this->duplicates->groupBy('class'),
            );
        }
        return $this->duplicates->groupBy('class');

    }

    private function getRelationsModels($relation, $mainModel, $config)
    {

        foreach ($config[$relation] ?? [] as $relationClass => $relationConfig) {

            $relationConfig['foreignKey'] = $relationConfig['foreignKey'] ?? $mainModel->getForeignKey();
            $relationConfig['localKey'] = $relationConfig['localKey'] ?? $mainModel->getKeyName();

            $queryBuilder = $mainModel->$relation($relationClass, $relationConfig['foreignKey'], $relationConfig['localKey']);
            if (isset($relationConfig['queryBuilderCallbackFunc'])) {
                $queryBuilder == $relationConfig['queryBuilderCallbackFunc']($queryBuilder);
            }

            if (!(bool)$queryBuilder->count()) continue;

            $relationModels = $queryBuilder->get();
            if ($this->getModelConfig($relationModels->first())) {
                foreach ($relationModels as $relationModel) {
                    $this->getModelRelations($relationModel, $relationModel->id);
                }
            } else {

                foreach ($relationModels as $relationModel) {
                    $this->findModel($relationModel, $relationModel->id);

                }
            }

        }

    }

    private function findModel($mainModel, $mainId)
    {
        $repository = $this->repository;

        $class = get_class($mainModel);
        $model = $class::findOrFail($mainId);


        $this->repository[$class][] = $model;

        return $model;
    }


    private function getModelRelations($mainModel, $mainId)
    {
        $mainModel = $this->findModel($mainModel, $mainId);
        $config = $this->getModelConfig($mainModel);
        if ($config) {
            $this->getRelationsModels('hasMany', $mainModel, $config);
            $this->getRelationsModels('hasOne', $mainModel, $config);
        }
    }

    public function GetModelWithRelations($mainModel, $mainId)
    {
        $this->getModelRelations($mainModel, $mainId);

        return $this->repository;

    }
}
