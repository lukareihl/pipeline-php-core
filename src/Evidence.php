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
 * Storage of evidence on a FlowData object.
 */
class Evidence
{
    /**
     * @var FlowData
     */
    protected $flowData;

    /**
     * @var array<string, mixed>
     */
    protected $evidence = [];

    /**
     * Evidence container constructor.
     *
     * @param FlowData $flowData
     */
    public function __construct($flowData)
    {
        $this->flowData = $flowData;
    }

    /**
     * If a flow element can use the key then add the key value pair to the
     * evidence collection.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set($key, $value)
    {
        foreach ($this->flowData->pipeline->flowElements as $flowElement) {
            if ($flowElement->filterEvidenceKey($key)) {
                $this->evidence[$key] = $value;

                break;
            }
        }
    }

    /**
     * Helper function to set multiple pieces of evidence from an array.
     *
     * @param array<string, mixed> $array
     * @return void
     */
    public function setArray($array)
    {
        if (!is_array($array)) {
            $this->flowData->setError('core', new \Exception(Messages::PASS_KEY_VALUE));
        }

        foreach ($array as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Extract evidence from a web request
     * No argument version automatically reads from current request using the
     * $_SERVER, $_COOKIE, $_GET and $_POST globals.
     *
     * @param null|array<string, mixed> $server key value pairs for the HTTP headers
     * @param null|array<string, mixed> $cookies key value pairs for the cookies
     * @param null|array<string, mixed> $query key value pairs for the form parameters
     * @return void
     */
    public function setFromWebRequest($server = null, $cookies = null, $query = null)
    {
        if ($server === null) {
            $server = $_SERVER;
        }

        if ($cookies === null) {
            $cookies = $_COOKIE;
        }

        if ($query === null) {
            // Merge the GET and POST parameters favoring the GET keys if there
            // are keys that conflict.
            $query = array_merge($_POST, $_GET);
        }

        $evidence = [];

        foreach ($server as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));

                $key = strtolower($key);

                $evidence['header.' . $key] = $value;
            }
        }

        foreach ($cookies as $key => $value) {
            $evidence['cookie.' . $key] = $value;
        }

        foreach ($query as $key => $value) {
            $evidence['query.' . $key] = $value;
        }

        if (isset($server['SERVER_ADDR'])) {
            $evidence['server.host-ip'] = $server['SERVER_ADDR'];
        }

        if (isset($server['REMOTE_ADDR'])) {
            $evidence['server.client-ip'] = $server['REMOTE_ADDR'];
        }

        // Protocol
        if (
            isset($server['HTTPS']) &&
            ($server['HTTPS'] === 'on' || $server['HTTPS'] == 1) ||
            isset($server['HTTP_X_FORWARDED_PROTO']) &&
            $server['HTTP_X_FORWARDED_PROTO'] === 'https'
        ) {
            $protocol = 'https';
        } else {
            $protocol = 'http';
        }

        // Override protocol with referer header if set
        if (isset($server['HTTP_REFERER']) && $server['HTTP_REFERER']) {
            $protocol = parse_url($server['HTTP_REFERER'], PHP_URL_SCHEME);
        }

        $evidence['header.protocol'] = $protocol;

        $this->setArray($evidence);
    }

    /**
     * Get a piece of evidence by key.
     *
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        if (isset($this->evidence[$key])) {
            return $this->evidence[$key];
        }

        return null;
    }

    /**
     * Get all evidence.
     *
     * @return array<string, mixed>
     */
    public function getAll()
    {
        return $this->evidence;
    }
}