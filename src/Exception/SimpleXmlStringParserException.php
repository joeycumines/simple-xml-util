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

namespace JoeyCumines\SimpleXmlUtil\Exception;

/**
 * Class SimpleXmlStringParserException
 * @package JoeyCumines\SimpleXmlUtil\Exception
 *
 * An exception modeling a libxml failure.
 */
class SimpleXmlStringParserException extends \RuntimeException
{
    /** @var \LibXMLError[] */
    private $errors = [];

    /**
     * SimpleXmlStringParserException constructor.
     *
     * NOTES:
     * - errors will be converted to instances of LibXMLError if they are not and have common properties, or can
     *      successfully be converted into a non-empty, non-whitespace string (invalid ones ignored otherwise)
     *
     * @param \LibXMLError[]|mixed[] $errors
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     *
     * @see SimpleXmlStringParserException::valueToLibXmlError() For information about $errors conversion.
     */
    public function __construct(array $errors = [], $message = '', $code = 0, $previous = null)
    {
        parent::__construct($message, $code, $previous);

        foreach ($errors as $error) {
            $error = static::valueToLibXmlError($error);

            if (!$error instanceof \LibXMLError) {
                continue;
            }

            $this->errors[] = $error;
        }
    }

    /**
     * @return \LibXMLError[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Convert a value to a \LibXMLError (if necessary), returning null if it was not possible to obtain an error
     * with at least one set property.
     *
     * NOTES:
     * - objects (with public properties) and arrays will attempt to load all keys, and pass at least one matched
     * - as a fallback, a string conversion will be attempted, to use for a message, and will pass if non-empty and
     *      non-whitespace
     *
     * @param mixed $value
     *
     * @return \LibXMLError|null
     */
    public static function valueToLibXmlError($value)
    {
        if ($value instanceof \LibXMLError) {
            return $value;
        }

        $type = gettype($value);

        // should we try to map properties?
        if ('array' === $type || 'object' === $type) {
            $result = new \LibXMLError();
            $match = false;

            foreach ($value as $k => $v) {
                if (true === property_exists(\LibXMLError::class, $k)) {
                    $match = true;
                }

                $result->{$k} = $v;
            }

            // if there was any match we can return our $result
            if (true === $match) {
                return $result;
            }

            // we don't want to use any properties from objects that failed
            unset($result);
        }

        // fallback to an attempting string conversion to get a message
        switch ($type) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case 'object':
                // if there is no __toString it will fail
                if (false === method_exists($value, '__toString')) {
                    return null;
                }
            case 'double':
            case 'integer':
            case 'string':
                $message = (string)$value;
                break;

            default:
                // non-convertible type
                return null;
        }

        // empty message, no result
        if ('' === trim($message)) {
            return null;
        }

        // a xml error with a message (only)
        $result = new \LibXMLError();
        $result->message = $message;
        return $result;
    }
}
