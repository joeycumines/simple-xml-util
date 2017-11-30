<?php
/*
   Copyright 2017 Joseph Cumines

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
 */

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
