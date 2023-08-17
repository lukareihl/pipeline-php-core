<?php
/* *********************************************************************
 * This Original Work is copyright of 51 Degrees Mobile Experts Limited.
 * Copyright 2023 51 Degrees Mobile Experts Limited, Davidson House,
 * Forbury Square, Reading, Berkshire, United Kingdom RG1 3EU.
 *
 * This Original Work is licensed under the European Union Public Licence
 * (EUPL) v.1.2 and is subject to its terms as set out below.
 *
 * If a copy of the EUPL was not distributed with this file, You can obtain
 * one at https://opensource.org/licenses/EUPL-1.2.
 *
 * The 'Compatible Licences' set out in the Appendix to the EUPL (as may be
 * amended by the European Commission) shall be deemed incompatible for
 * the purposes of the Work and the provisions of the compatibility
 * clause in Article 5 of the EUPL shall not apply.
 *
 * If using the Work as, or as part of, a network application, by
 * including the attribution notice(s) required under Article 5 of the EUPL
 * in the end user terms of the application under an appropriate heading,
 * such notice(s) shall fulfill the requirements of that article.
 * ********************************************************************* */

namespace fiftyone\pipeline\core;

/**
 * FlowData is created by a specific Pipeline
 * It collects evidence set by the user
 * It passes evidence to FlowElements in the Pipeline
 * These elements can return ElementData or populate an errors object.
 */
class FlowData
{
    /**
     * @var null|Pipeline
     */
    public $pipeline;

    /**
     * @var bool
     */
    public $stopped = false;

    /**
     * @var Evidence
     */
    public $evidence;

    /**
     * @var array<string, ElementData|ElementDataDictionary>
     */
    public $data;

    /**
     * @var bool
     */
    public $processed = false;

    /**
     * @var array<string, \Throwable>
     */
    public $errors = [];

    /**
     * @var JsonBundlerElement
     */
    public $jsonbundler;

    /**
     * @param null|Pipeline $pipeline
     */
    public function __construct($pipeline)
    {
        $this->pipeline = $pipeline;

        $this->evidence = new Evidence($this);
    }

    /**
     * Magic getter to allow $FlowData->FlowElementKey getting.
     *
     * @param string $flowElementKey
     * @return ElementData
     */
    public function __get($flowElementKey)
    {
        return $this->get($flowElementKey);
    }

    /**
     * Runs the process function on every attached FlowElement allowing data to
     * be changed based on evidence. Can only be run once per FlowData instance.
     *
     * @return FlowData
     */
    public function process()
    {
        if ($this->processed === false) {
            foreach ($this->pipeline->flowElements as $flowElement) {
                if ($this->stopped === false) {
                    // All errors are caught and stored in an errors array keyed by the
                    // FlowElement that set the error

                    try {
                        $flowElement->process($this);
                    } catch (\Throwable $e) {
                        $this->setError($flowElement->dataKey, $e);
                    }
                }
            }

            // Set processed flag to true. FlowData can only be processed once
            $this->processed = true;
        } else {
            $this->setError('global', new \Exception(Messages::FLOW_DATA_PROCESSED));
        }

        if (count($this->errors) > 0 && $this->pipeline->suppressProcessExceptions === false) {
            $exception = reset($this->errors);

            throw $exception;
        }

        return $this;
    }

    /**
     * Retrieve data by FlowElement object.
     *
     * @param FlowElement $flowElement
     * @return ElementData
     */
    public function getFromElement($flowElement)
    {
        return $this->get($flowElement->dataKey);
    }

    /**
     * Retrieve data by FlowElement key.
     *
     * @param string $flowElementKey
     * @return ElementData
     * @throws \Exception
     */
    public function get($flowElementKey)
    {
        if (isset($this->data[$flowElementKey])) {
            return $this->data[$flowElementKey];
        }

        if ($this->data === null) {
            $message = sprintf(Messages::NO_ELEMENT_DATA_NULL, $flowElementKey);
        } else {
            $message = sprintf(Messages::NO_ELEMENT_DATA, $flowElementKey, join(',', array_keys($this->data)));
        }

        throw new \Exception($message);
    }

    /**
     * Set data (used by FlowElement).
     *
     * @param ElementData|ElementDataDictionary $data
     * @return void
     */
    public function setElementData($data)
    {
        $this->data[$data->flowElement->dataKey] = $data;
    }

    /**
     * Set error (should be keyed by FlowElement dataKey).
     *
     * @param string $key
     * @param \Throwable $error
     * @return void
     */
    public function setError($key, $error)
    {
        $this->errors[$key] = $error;

        $logMessage = 'Error occurred during processing';

        if (!empty($key)) {
            $logMessage = $logMessage . ' of ' . $key . ". \n" . $error->getMessage();
        }

        $this->pipeline->log('error', $logMessage);
    }

    /**
     * Get an array evidence stored in the FlowData, filtered by
     * its FlowElements' EvidenceKeyFilters.
     *
     * @return array<string, mixed>
     */
    public function getEvidenceDataKey()
    {
        $requestedEvidence = [];
        $evidence = $this->evidence->getAll();

        foreach ($this->pipeline->flowElements as $flowElement) {
            $requestedEvidence = array_merge($requestedEvidence, $flowElement->filterEvidence($this));
        }

        return $requestedEvidence;
    }

    /**
     * Stop processing any subsequent FlowElements.
     *
     * @return void
     */
    public function stop()
    {
        $this->stopped = true;
    }

    /**
     * Get data from FlowElement based on property metadata.
     *
     * @param string $metaKey
     * @param string $metaValue
     * @return array<string, mixed>
     * @throws \Exception
     */
    public function getWhere($metaKey, $metaValue)
    {
        $metaKey = strtolower($metaKey);
        $metaValue = strtolower($metaValue);

        $keys = [];

        if (isset($this->pipeline->propertyDatabase[$metaKey][$metaValue])) {
            foreach ($this->pipeline->propertyDatabase[$metaKey][$metaValue] as $key => $value) {
                $keys[$key] = $value['flowElement'];
            }
        }

        $output = [];

        foreach ($keys as $key => $flowElement) {
            // First check if FlowElement has any data set
            if (isset($this->data[$flowElement])) {
                $data = $this->get($flowElement);

                try {
                    $output[$key] = $data->get($key);
                } catch (\Throwable $e) {
                    continue;
                }
            }
        }

        return $output;
    }
}
