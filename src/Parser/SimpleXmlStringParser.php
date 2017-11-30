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

namespace JoeyCumines\SimpleXmlUtil\Parser;

use JoeyCumines\SimpleXmlUtil\Exception\SimpleXmlStringParserException;
use JoeyCumines\SimpleXmlUtil\Interfaces\SimpleXmlStringParserInterface;

class SimpleXmlStringParser implements SimpleXmlStringParserInterface
{
    /** @var string Arg 2 for simplexml_load_string. */
    private $className;

    /** @var int Arg 3 for simplexml_load_string. */
    private $options;

    /** @var string Arg 4 for simplexml_load_string. */
    private $ns;

    /** @var bool Arg 5 for simplexml_load_string. */
    private $prefix;

    /** @var bool|null If set to a boolean it will call libxml_disable_entity_loader with it (resets after). */
    private $disableEntityLoader;

    /**
     * SimpleXmlStringParser constructor.
     *
     * @param string $className
     * @param int $options
     * @param string $ns
     * @param bool $prefix
     * @param bool|null $disableEntityLoader
     *
     * @throws \InvalidArgumentException If any input failed validation.
     */
    public function __construct(
        $className = \SimpleXMLElement::class,
        $options = 0,
        $ns = '',
        $prefix = false,
        $disableEntityLoader = null
    ) {
        $this->setClassName($className)
            ->setOptions($options)
            ->setNs($ns)
            ->setPrefix($prefix)
            ->setDisableEntityLoader($disableEntityLoader);
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @param string $className
     *
     * @return $this
     */
    public function setClassName($className)
    {
        if (false === is_string($className)) {
            throw new \InvalidArgumentException('$className must be a string');
        }

        if (
            \SimpleXMLElement::class !== $className &&
            false === is_subclass_of($className, \SimpleXMLElement::class, true)
        ) {
            throw new \InvalidArgumentException('$className must be SimpleXMLElement or the fully qualified class name of a subclass');
        }

        $this->className = $className;

        return $this;
    }

    /**
     * @return int
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param int $options
     *
     * @return $this
     */
    public function setOptions($options)
    {
        if (false === is_int($options)) {
            throw new \InvalidArgumentException('$options must be an int');
        }

        $this->options = $options;

        return $this;
    }

    /**
     * @return string
     */
    public function getNs()
    {
        return $this->ns;
    }

    /**
     * @param string $ns
     *
     * @return $this
     */
    public function setNs($ns)
    {
        if (false === is_string($ns)) {
            throw new \InvalidArgumentException('$ns must be a string');
        }

        $this->ns = $ns;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPrefix()
    {
        return $this->prefix;
    }

    /**
     * @param bool $prefix
     *
     * @return $this
     */
    public function setPrefix($prefix)
    {
        if (false === is_bool($prefix)) {
            throw new \InvalidArgumentException('$prefix must be a bool');
        }

        $this->prefix = $prefix;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getDisableEntityLoader()
    {
        return $this->disableEntityLoader;
    }

    /**
     * @param bool|null $disableEntityLoader
     *
     * @return $this
     */
    public function setDisableEntityLoader($disableEntityLoader)
    {
        if (null !== $disableEntityLoader && false === is_bool($disableEntityLoader)) {
            throw new \InvalidArgumentException('$disableEntityLoader must be a bool or null');
        }

        $this->disableEntityLoader = $disableEntityLoader;

        return $this;
    }

    /**
     * Parse an xml string into a \SimpleXMLElement structure, throwing an exception on failure.
     *
     * The error message of any SimpleXmlStringParserException will contain an informative console-printable
     * representation of all libxml errors.
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
    public function parseXmlString($data)
    {
        if (false === is_string($data)) {
            throw new \InvalidArgumentException('$data must be a string');
        }

        // set to use internal errors and clear any existing, noting the previous flag value for later
        $useErrors = libxml_use_internal_errors(true);

        libxml_clear_errors();

        $disableEntityLoader = null;

        if (true === is_bool($this->getDisableEntityLoader())) {
            $disableEntityLoader = libxml_disable_entity_loader($this->getDisableEntityLoader());
        }

        $element = simplexml_load_string(
            $data,
            $this->getClassName(),
            $this->getOptions(),
            $this->getNs(),
            $this->isPrefix()
        );

        $errors = libxml_get_errors();

        if (true === is_bool($disableEntityLoader)) {
            libxml_disable_entity_loader($disableEntityLoader);
        }

        // clear any errors (we generated) then reset the flag to use internal errors to it's previous value
        libxml_clear_errors();
        libxml_use_internal_errors($useErrors);

        if (false === is_array($errors)) {
            $errors = [];
        }

        if (!$element instanceof \SimpleXMLElement || count($errors)) {
            // summarize / truncate the data for the message, to a max of 80 characters
            $summary = $data;
            if (mb_strlen($data) > 80) {
                // 0-64...(-11)-(-0)
                $summary = mb_substr($summary, 0, 65) . '...' . mb_substr($summary, mb_strlen($data) - 12);
            }

            throw new SimpleXmlStringParserException(
                $errors,
                trim(
                    sprintf(
                        'unable to parse xml string like `%s`, internal error(s):%s%s%s',
                        $summary,
                        PHP_EOL,
                        PHP_EOL,
                        static::getLibXmlErrorsAsString($errors, $data)
                    )
                )
            );
        }

        return $element;
    }

    /**
     * Get all libxml errors as a simple built string, based on the example provided by php.net.
     *
     * NOTES:
     * - if provided, $xml will be used to show the line the error occurred (only works for fixed-width col)
     * - there will be one trailing newline
     * - errors that are not instances of \LibXMLError will be ignored
     *
     * @param \LibXMLError[] $errors
     * @param string|null $xml
     *
     * @return string
     */
    public static function getLibXmlErrorsAsString(array $errors, $xml = null)
    {
        $result = '';

        if (true === is_string($xml)) {
            $xml = preg_split('/\\R/u', $xml);
        }

        foreach ($errors as $error) {
            if (!$error instanceof \LibXMLError) {
                continue;
            }

            if ('' !== $result) {
                $result .= PHP_EOL;
            }

            $result .= static::getLibXmlErrorAsString($error, $xml);
        }

        if ('' === $result) {
            $result = 'No Libxml Internal Errors!' . PHP_EOL;
        }

        return $result;
    }

    /**
     * Get all libxml errors as a simple built string, based on the example provided by php.net.
     *
     * NOTES:
     * - if provided, $xml will be used to show the line the error occurred (only works for fixed-width col)
     * - there will be one trailing newline
     *
     * @param \LibXMLError $error
     * @param string|array|null $xml
     *
     * @return string
     */
    public static function getLibXmlErrorAsString(\LibXMLError $error, $xml = null)
    {
        $result = '';

        if (true === is_string($xml)) {
            $xml = preg_split('/\\R/u', $xml);
        }

        if (
            true === is_array($xml) &&
            true === isset($error->line) &&
            true === is_int($error->line) &&
            0 < $error->line &&
            count($xml) >= $error->line
        ) {
            // add the actual line as part of the error
            $result .= array_values($xml)[$error->line - 1] . PHP_EOL;

            // add a little indicator for the column, defaulting to position 0 if no info available
            $column = 0;
            if (
                true === isset($error->column) &&
                true === is_int($error->column) &&
                0 <= $error->column
            ) {
                $column = $error->column;
            }
            $result .= str_repeat('-', $column) . '^' . PHP_EOL;
        } else {
            $result .= '--------------------------------------------' . PHP_EOL;
        }

        $code = '';

        if (true === isset($error->code) && (true === is_int($error->code) || true === is_string($error->code))) {
            $code = trim((string)($error->code));
        }

        if ('' === $code) {
            $code = '?';
        }

        $level = true === isset($error->level) ? $error->level : null;

        switch ($level) {
            case LIBXML_ERR_WARNING:
                $result .= 'Warning (' . $code . '): ';
                break;

            case LIBXML_ERR_ERROR:
                $result .= 'Error (' . $code . '): ';
                break;

            case LIBXML_ERR_FATAL:
                $result .= 'Fatal Error (' . $code . '): ';
                break;

            default:
                $result .= 'Unknown Error (' . $code . '): ';
                break;
        }

        $result .= (true === isset($error->message) ? trim((string)($error->message)) : '') .
            PHP_EOL . '  Line: ' . (true === isset($error->line) ? $error->line : '') .
            PHP_EOL . '  Column: ' . (true === isset($error->column) ? $error->column : '');

        if (true === isset($error->file) && true === is_string($error->file) && '' !== $error->file) {
            $result .= PHP_EOL . '  File: ' . $error->file;
        }

        $result .= PHP_EOL . '--------------------------------------------' . PHP_EOL;
        return $result;
    }
}
