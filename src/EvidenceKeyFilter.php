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
 * An evidence key filter is added to a FlowElement
 * It tells the Pipeline which evidence it is interested in
 * This can be used to determine whether a request can be cached
 * Or to filter out evidence not needed by any element in a Pipeline
 * This base class is always extended for a specific filter type.
 *
 * @property null|callable $filterEvidenceKey
 */
class EvidenceKeyFilter
{
    /**
     * @var null|callable
     */
    private $_filterEvidenceKey;

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        if ($name === 'filterEvidenceKey') {
            $this->_filterEvidenceKey = $value;
        }
    }

    /**
     * Filter evidence from an object.
     *
     * @param array<string, mixed> $evidenceList evidence dictionary contents
     * @return array<string, mixed> Filtered evidence dictionary contents
     */
    public function filterEvidence($evidenceList)
    {
        $filtered = [];

        foreach ($evidenceList as $key => $value) {
            if ($this->filterEvidenceKey($key)) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    /**
     * Should a property key be in the filtered evidence.
     *
     * @param string $key
     * @return bool
     */
    public function filterEvidenceKey($key)
    {
        if (is_callable($this->_filterEvidenceKey)) {
            return call_user_func($this->_filterEvidenceKey, $key);
        }

        return true;
    }
}