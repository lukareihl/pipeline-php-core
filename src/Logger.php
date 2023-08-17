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
 * Logging for a Pipeline.
 */
class Logger
{
    /**
     * @var array<string, mixed>
     */
    public $settings;

    /**
     * @var int
     */
    private $minLevel;

    /**
     * @var array<int, string>
     */
    private $levels = ['trace', 'debug', 'information', 'warning', 'error', 'critical'];

    /**
     * Create a logger.
     *
     * @param null|string $level ("trace", "debug", "information", "warning", "error", "critical")
     * @param array<string, mixed> $settings customs settings for a logger
     */
    public function __construct($level, $settings = [])
    {
        $this->settings = $settings;
        $this->minLevel = $this->getLevel($level);
    }

    /**
     * Log a message.
     *
     * @param string $level
     * @param string $message
     * @return void
     */
    public function log($level, $message)
    {
        $levelIndex = $this->getLevel($level);

        if ($levelIndex >= $this->minLevel) {
            $log = [
                'time' => date('Y-m-d H:i:s'),
                'level' => $level,
                'message' => $message
            ];

            $this->logInternal($log);
        }
    }

    /**
     * Internal logging function overridden by specific loggers.
     *
     * @param array<string, mixed> $log
     * @return array<string, mixed>
     */
    public function logInternal($log)
    {
        return $log;
    }

    /**
     * @param null|string $levelName
     * @return int
     */
    private function getLevel($levelName)
    {
        if ($levelName === null) {
            $levelName = 'error';
        } else {
            $levelName = strtolower($levelName);
        }

        return (int) array_search($levelName, $this->levels);
    }
}
