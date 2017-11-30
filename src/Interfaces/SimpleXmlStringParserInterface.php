<?php

namespace JoeyCumines\SimpleXmlUtil\Interfaces;

use JoeyCumines\SimpleXmlUtil\Exception\SimpleXmlStringParserException;

/**
 * Interface SimpleXmlStringParserInterface
 * @package JoeyCumines\SimpleXmlUtil\Interfaces
 *
 * An interface modeling a given configuration of the libxml parser, that can be used to provide repeated reads
 * of xml strings in a standardized way.
 */
interface SimpleXmlStringParserInterface
{
    /**
     * Parse an xml string into a \SimpleXMLElement structure, throwing an exception on failure.
     *
     * NOTES:
     * - may clear the libxml error buffer
     * - should not trigger php errors
     *
     * @param string $data
     *
     * @return \SimpleXMLElement
     *
     * @throws SimpleXmlStringParserException On parser error.
     * @throws \InvalidArgumentException If $data was not a string.
     */
    public function parseXmlString($data);
}
