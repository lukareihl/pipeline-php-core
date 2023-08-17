<?php

namespace fiftyone\pipeline\core\tests\classes;

class HeaderParser
{
    /**
     * Converts response headers string to an indexed array.
     *
     * @param array<string> $headers
     * @return array<string, int|string>
     */
    public static function parse($headers)
    {
        $parsed = [];

        foreach ($headers as $header) {
            $parts = explode(':', $header, 2);

            if (isset($parts[1])) {
                $parsed[trim($parts[0])] = trim($parts[1]);
            } else {
                $parsed[] = $header;

                if (preg_match('#HTTP/[0-9\\.]+\\s+([0-9]+)#', $header, $out)) {
                    $parsed['response_code'] = (int) $out[1];
                }
            }
        }

        return $parsed;
    }
}