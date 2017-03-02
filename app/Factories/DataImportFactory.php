<?php
/**
 * Copyright Dora Boateng(TM) 2015, all rights reserved.
 */
namespace App\Factories;

use Exception;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpFoundation\File\UploadedFile as File;

/**
 * @todo This class can be improved for efficiency, especially keeping in mind the BackupFactory.
 */
class DataImportFactory
{
    /**
     * Model associated with data.
     *
     * @var string
     */
    protected $dataModel;

    /**
     * Meta data.
     *
     * @var array
     */
    protected $dataMeta;

    /**
     * Data array.
     *
     * @var array
     */
    protected $dataArray;

    /**
     * @var array
     */
    protected $messages = [];

    /**
     * @param array $meta
     */
    public function setDataMeta(array $meta)
    {
        $this->dataMeta = $meta;
    }

    /**
     * @param string $model
     */
    public function setDataModel($model)
    {
        $this->dataModel = $model;
    }

    /**
     * Saves data array and tries to remove duplicates.
     *
     * @param array $data
     */
    public function setDataArray(array $data)
    {
        $this->dataArray = array_map('unserialize', array_unique(array_map('serialize', $data)));
    }

    /**
     * Imports a data set into the database.
     *
     * @param array $data
     * @return DataImportFactory
     */
    public function importDataSet(array $data = null)
    {
        if (is_array($data) && count($data)) {
            $this->setDataArray($data);
        }

        // Performance check.
        if (count($this->dataArray) < 1) {
            throw new Exception('Empty data set.');
        }

        // Since we're in the general DataImportFactory, we will create a new factory that
        // is specific to this data set.
        switch ($this->dataModel) {
            case 'App\\Models\\Alphabet':
            case 'App\\Models\\Culture':
            case 'App\\Models\\Language':
            case 'App\\Models\\LanguageFamily':
            case 'App\\Models\\Reference':
            case 'App\\Models\\Tag':
            case 'App\\Models\\User':
                $name = substr($this->dataModel, strrpos($this->dataModel, '\\') + 1);
                $factory = $this->make("{$name}ImportFactory");
                break;

            case 'App\\Models\\Definition':
            case 'App\\Models\\Definition\\Word':
            case 'App\\Models\\Definition\\Expression':
            case 'App\\Models\\Definition\\Story':
                $factory = $this->make('DefinitionImportFactory');
                break;

            default:
                $this->setMessage("\"{$this->dataModel}\" is not a valid data model.");
                return $this;
        }

        $factory->setDataModel($this->dataModel);
        $factory->setDataMeta($this->dataMeta);
        $factory->setDataArray($this->dataArray);

        // TODO: check for infinite loops?

        return $factory->importDataSet();
    }

    /**
     * @param string $msg
     */
    public function setMessage($msg)
    {
        array_push($this->messages, $msg);
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Creates a new instance of a DataImportFactory.
     *
     * @param string $factory
     */
    public function make($factory)
    {
        $className = 'App\\Factories\\DataImport\\'.$factory;

        return new $className;
    }
}
