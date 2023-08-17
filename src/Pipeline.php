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
 * Pipeline holding a list of FlowElements for processing,
 * can create FlowData that will be passed through these,
 * collecting ElementData
 * Should be constructed through the PipelineBuilder class.
 */
class Pipeline
{
    /**
     * @var array<FlowElement>
     */
    public $flowElements;

    /**
     * @var array<FlowElement>
     */
    public $flowElementsList = [];

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var bool
     */
    public $suppressProcessExceptions;

    /**
     * @var array<string, array<string, array<string, mixed>>>
     */
    public $propertyDatabase;

    /**
     * @param array<FlowElement> $flowElements
     * @param array<string, mixed> $settings
     */
    public function __construct($flowElements, $settings)
    {
        $this->logger = $settings['logger'] ?? new Logger(null);

        // If true then pipeline will suppress exceptions
        // added to FlowData errors otherwise will throw the
        // exception occurred during the processing of first
        // element.
        $this->suppressProcessExceptions = $settings['suppressProcessExceptions'] ?? false;

        $this->log('info', 'test');

        $this->flowElements = $flowElements;

        $this->propertyDatabase = [];

        foreach ($flowElements as $flowElement) {
            $this->flowElementsList[$flowElement->dataKey] = $flowElement;

            $flowElement->pipelines[] = $this;

            $flowElement->onRegistration($this);

            $this->updatePropertyDatabaseForFlowElement($flowElement);
        }
    }

    /**
     * Create a FlowData based on what's in the Pipeline.
     *
     * @return FlowData
     */
    public function createFlowData()
    {
        return new FlowData($this);
    }

    /**
     * @param string $level
     * @param string $message
     * @return void
     */
    public function log($level, $message)
    {
        $this->logger->log($level, $message);
    }

    /**
     * Get a FlowElement by its name.
     *
     * @param string $key
     * @return FlowElement
     */
    public function getElement($key)
    {
        return $this->flowElementsList[$key];
    }

    /**
     * Update metadata store for a FlowElement based on its list of properties.
     *
     * @param FlowElement $flowElement
     * @return void
     */
    public function updatePropertyDatabaseForFlowElement($flowElement)
    {
        $dataKey = $flowElement->dataKey;

        // First unset any properties stored by the FlowElement
        foreach ($this->propertyDatabase as $propertyValues) {
            foreach ($propertyValues as $propertyList) {
                foreach ($propertyList as $key => $info) {
                    if ($info['flowElement'] === $dataKey) {
                        unset($propertyList[$key]);
                    }
                }
            }
        }

        $properties = $flowElement->getProperties();

        foreach ($properties as $key => $property) {
            foreach ($property as $metaKey => $metaValue) {
                $metaKey = strtolower($metaKey);

                if (!isset($this->propertyDatabase[$metaKey])) {
                    $this->propertyDatabase[$metaKey] = [];
                }

                if (is_string($metaValue)) {
                    $metaValue = strtolower($metaValue);
                } else {
                    continue;
                }

                if (!isset($this->propertyDatabase[$metaKey][$metaValue])) {
                    $this->propertyDatabase[$metaKey][$metaValue] = [];
                }

                $property['flowElement'] = $dataKey;

                $this->propertyDatabase[$metaKey][$metaValue][$key] = $property;
            }
        }
    }
}
