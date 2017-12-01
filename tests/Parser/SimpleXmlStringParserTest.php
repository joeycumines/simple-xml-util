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

namespace Tests\JoeyCumines\SimpleXmlUtil\Parser;

use JoeyCumines\SimpleXmlUtil\Exception\SimpleXmlStringParserException;
use JoeyCumines\SimpleXmlUtil\Parser\SimpleXmlStringParser;
use PHPUnit\Framework\TestCase;

class SimpleXmlStringParserTest extends TestCase
{
    const BAD_XML = '<a bad-param=">ONE</a>';

    const BAD_XML_ERROR = <<<'EOT'
<a bad-param=">ONE</a>
-------------------^
Fatal Error (38): Unescaped '<' not allowed in attributes values
  Line: 1
  Column: 19
--------------------------------------------

<a bad-param=">ONE</a>
-------------------^
Fatal Error (65): attributes construct error
  Line: 1
  Column: 19
--------------------------------------------

<a bad-param=">ONE</a>
-------------------^
Fatal Error (73): Couldn't find end of Start Tag a line 1
  Line: 1
  Column: 19
--------------------------------------------

<a bad-param=">ONE</a>
-------------------^
Fatal Error (5): Extra content at the end of the document
  Line: 1
  Column: 19
--------------------------------------------

EOT;

    /**
     * Convert all characters (/ sequences), denoting the end of one line, and the beginning of another, to PHP_EOL.
     *
     * @param string $value
     *
     * @return string
     */
    private function toPhpEol($value)
    {
        return preg_replace('/\\R/u', PHP_EOL, $value);
    }

    /**
     * @param $error
     * @param $xml
     * @param $expected
     *
     * @dataProvider getLibXmlErrorAsStringProvider
     */
    public function testGetLibXmlErrorAsString($error, $xml, $expected)
    {
        $actual = SimpleXmlStringParser::getLibXmlErrorAsString($error, $xml);
        $this->assertEquals($this->toPhpEol($expected), $actual);
    }

    public function getLibXmlErrorAsStringProvider()
    {
        $error1 = new \LibXMLError();

        $error2 = new \LibXMLError();
        $error2->level = LIBXML_ERR_WARNING;
        $error2->code = 223;
        $error2->column = 4;
        $error2->message = 'error message!';
        $error2->line = 2;
        $error2->file = '/path/to/file.extension';

        $xml1 = "NOT\n1 2 3 4 5\nNOT";

        return [
            [
                $error1,
                null,
                <<<EOT
--------------------------------------------
Unknown Error (?): 
  Line: 
  Column: 
--------------------------------------------

EOT
            ],
            [
                $error1,
                $xml1,
                <<<EOT
--------------------------------------------
Unknown Error (?): 
  Line: 
  Column: 
--------------------------------------------

EOT
            ],
            [
                $error2,
                $xml1,
                <<<EOT
1 2 3 4 5
----^
Warning (223): error message!
  Line: 2
  Column: 4
  File: /path/to/file.extension
--------------------------------------------

EOT
            ],
        ];
    }

    private function dummyErrors()
    {
        return array(
            0 =>
                SimpleXmlStringParserException::valueToLibXmlError(array(
                    'level' => 3,
                    'code' => 38,
                    'column' => 19,
                    'message' => 'Unescaped \'<\' not allowed in attributes values
',
                    'file' => '',
                    'line' => 1,
                )),
            1 =>
                SimpleXmlStringParserException::valueToLibXmlError(array(
                    'level' => 3,
                    'code' => 65,
                    'column' => 19,
                    'message' => 'attributes construct error
',
                    'file' => '',
                    'line' => 1,
                )),
            2 =>
                SimpleXmlStringParserException::valueToLibXmlError(array(
                    'level' => 3,
                    'code' => 73,
                    'column' => 19,
                    'message' => 'Couldn\'t find end of Start Tag a line 1
',
                    'file' => '',
                    'line' => 1,
                )),
            3 =>
                SimpleXmlStringParserException::valueToLibXmlError(array(
                    'level' => 3,
                    'code' => 5,
                    'column' => 19,
                    'message' => 'Extra content at the end of the document
',
                    'file' => '',
                    'line' => 1,
                )),
        );
    }

    public function testGetLibXmlErrorsAsString()
    {
        $actual = SimpleXmlStringParser::getLibXmlErrorsAsString($this->dummyErrors(), self::BAD_XML);
        $this->assertEquals($this->toPhpEol(self::BAD_XML_ERROR), $actual);
    }

    public function testGetLibXmlErrorsAsStringEmpty()
    {
        $this->assertEquals('No Libxml Internal Errors!' . PHP_EOL, SimpleXmlStringParser::getLibXmlErrorsAsString([]));
    }

    /**
     * @param array ...$args List of all args to parse sans-xml.
     *
     * @dataProvider parseProvider
     */
    public function testParse(...$args)
    {
        $input = '<a><b></b><c></c></a>';

        $className = count($args) >= 1 ? $args[0] : \SimpleXMLElement::class;

        /** @var \SimpleXMLElement $output */
        $output = (new SimpleXmlStringParser(...$args))->parseXmlString($input);

        $this->assertTrue($output instanceof \SimpleXMLElement);
        $this->assertEquals('a', $output->getName());
        $this->assertTrue(isset($output->b) && $output->b instanceof \SimpleXMLElement);
        $this->assertTrue(isset($output->b) && $output->b instanceof \SimpleXMLElement);
        $this->assertEquals($className, get_class($output));
        $this->assertEquals($className, get_class($output->b));
        $this->assertEquals($className, get_class($output->c));
    }

    public function parseProvider()
    {
        return [
            [],
            [DummySimpleXMLElementSubclass::class],
        ];
    }

    private function convertErrorsToComparable(array $errors)
    {
        $result = [];
        foreach ($errors as $error) {
            $this->assertTrue($error instanceof \LibXMLError);
            $result[] = (array)$error;
        }
        return $result;
    }

    /**
     * @param $useInternalErrors
     *
     * @dataProvider parseErrorProvider
     */
    public function testParseError($useInternalErrors)
    {
        libxml_use_internal_errors($useInternalErrors);

        if (true === $useInternalErrors) {
            $this->assertTrue(!simplexml_load_string(self::BAD_XML) instanceof \SimpleXMLElement);
            $this->assertEquals(count($this->dummyErrors()), count(libxml_get_errors()));
        }

        try {
            (new SimpleXmlStringParser())->parseXmlString(self::BAD_XML);
            $this->fail('expected an exception');
        } catch (SimpleXmlStringParserException $e) {
            $expected = 'unable to parse xml string like `<a bad-param=">ONE</a>`, internal error(s):' . PHP_EOL . PHP_EOL . self::BAD_XML_ERROR;
            $expected = trim((string)$expected);
            $this->assertEquals($this->toPhpEol($expected), $e->getMessage());

            $errorsExpected = $this->convertErrorsToComparable($this->dummyErrors());
            $errorsActual = $this->convertErrorsToComparable($e->getErrors());

            $this->assertTrue($errorsActual === $errorsExpected);
        }

        $this->assertCount(0, libxml_get_errors());
    }

    public function parseErrorProvider()
    {
        return [
            [true],
            [false],
        ];
    }

    public function testParseErrorConcat()
    {
        try {
            (new SimpleXmlStringParser())->parseXmlString('<>-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-123456789abcdefghijklmnop');
            $this->fail('expected an exception');
        } catch (SimpleXmlStringParserException $e) {
            $expectedStart = 'unable to parse xml string like `<>-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-...efghijklmnop`, internal error(s):';
            $this->assertTrue(strpos($e->getMessage(), $expectedStart) === 0);
        }
    }

    /**
     * Ensure that the multi-byte summary of the xml input works correctly, which is skipped if mbstring is not
     * available. The other tests should pass even without them, as it falls back to the non-mb variants.
     */
    public function testParseErrorConcatMultiByte()
    {
        if (false === extension_loaded('mbstring')) {
            $this->markTestSkipped(
                'the mbstring extension is not available'
            );
        }

        $input = '<>-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+漢字漢字漢字漢字漢字漢字漢字漢字漢字漢字漢字漢字漢字漢字漢字漢字漢字漢字漢字ghijklmnop';
        $subStr = substr($input, 0, 65) . '...' . substr($input, strlen($input) - 12);
        $this->assertEquals(80, strlen($subStr));
        $mbSubStr = mb_substr($input, 0, 65) . '...' . mb_substr($input, mb_strlen($input) - 12);
        $this->assertEquals(80, mb_strlen($mbSubStr));

        $this->assertNotEquals($subStr, $mbSubStr);
        $this->assertEquals('<>-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+-+漢...漢字ghijklmnop', $mbSubStr);

        try {
            (new SimpleXmlStringParser())->parseXmlString($input);
            $this->fail('expected an exception');
        } catch (SimpleXmlStringParserException $e) {
            $expectedStart = 'unable to parse xml string like `' . $mbSubStr . '`, internal error(s):';
            $this->assertTrue(mb_strpos($e->getMessage(), $expectedStart) === 0);
        }
    }

    /**
     * Ensure the internal flags have their state reset to their original value.
     *
     * @param $useInternalErrorsOld
     * @param $disableEntityLoaderOld
     * @param $disableEntityLoaderNew
     *
     * @dataProvider parseInternalStateProvider
     */
    public function testParseInternalStateOnError(
        $useInternalErrorsOld,
        $disableEntityLoaderOld,
        $disableEntityLoaderNew
    ) {
        libxml_use_internal_errors($useInternalErrorsOld);
        libxml_disable_entity_loader($disableEntityLoaderOld);

        $parser = (new SimpleXmlStringParser())
            ->setDisableEntityLoader($disableEntityLoaderNew);

        try {
            $parser->parseXmlString(self::BAD_XML);
            $this->fail('expected an exception');
        } catch (SimpleXmlStringParserException $e) {
        }

        $this->assertEquals($useInternalErrorsOld, libxml_use_internal_errors($useInternalErrorsOld));
        $this->assertEquals($disableEntityLoaderOld, libxml_disable_entity_loader($disableEntityLoaderOld));
    }

    /**
     * Ensure the internal flags have their state reset to their original value.
     *
     * @param $useInternalErrorsOld
     * @param $disableEntityLoaderOld
     * @param $disableEntityLoaderNew
     *
     * @dataProvider parseInternalStateProvider
     */
    public function testParseInternalState(
        $useInternalErrorsOld,
        $disableEntityLoaderOld,
        $disableEntityLoaderNew
    ) {
        libxml_use_internal_errors($useInternalErrorsOld);
        libxml_disable_entity_loader($disableEntityLoaderOld);

        $parser = (new SimpleXmlStringParser())
            ->setDisableEntityLoader($disableEntityLoaderNew);

        $this->assertTrue($parser->parseXmlString('<a></a>') instanceof \SimpleXMLElement);

        $this->assertEquals($useInternalErrorsOld, libxml_use_internal_errors($useInternalErrorsOld));
        $this->assertEquals($disableEntityLoaderOld, libxml_disable_entity_loader($disableEntityLoaderOld));
    }

    public function parseInternalStateProvider()
    {
        return [
            [
                true,
                true,
                true,
            ],
            [
                false,
                true,
                true,
            ],
            [
                true,
                false,
                true,
            ],
            [
                true,
                true,
                false,
            ],
            [
                false,
                false,
                false,
            ],
            [
                true,
                false,
                false,
            ],
            [
                false,
                true,
                false,
            ],
            [
                false,
                false,
                true,
            ],
            [
                true,
                true,
                null,
            ],
            [
                false,
                true,
                null,
            ],
            [
                true,
                false,
                null,
            ],
            [
                false,
                false,
                null,
            ],
        ];
    }

    public function testConstructorDefaults()
    {
        $parser = new SimpleXmlStringParser();
        $this->assertNull($parser->getDisableEntityLoader());
        $this->assertEquals('', $parser->getNs());
        $this->assertEquals(\SimpleXMLElement::class, $parser->getClassName());
        $this->assertFalse($parser->isPrefix());
        $this->assertEquals(0, $parser->getOptions());
    }

    public function testConstructor()
    {
        $parser = new SimpleXmlStringParser(
            DummySimpleXMLElementSubclass::class,
            123,
            'ns',
            true,
            false
        );
        $this->assertEquals(DummySimpleXMLElementSubclass::class, $parser->getClassName());
        $this->assertEquals(123, $parser->getOptions());
        $this->assertEquals('ns', $parser->getNs());
        $this->assertTrue($parser->isPrefix());
        $this->assertFalse($parser->getDisableEntityLoader());
        $this->assertNotNull($parser->getDisableEntityLoader());
    }

    /**
     * @param $property
     * @param $value
     * @param $success
     *
     * @dataProvider getterSetterProvider
     */
    public function testGetterSetter($property, $value, $success)
    {
        $getter = 'get' . $property;
        $setter = 'set' . $property;
        if (false === method_exists(SimpleXmlStringParser::class, $getter)) {
            $getter = 'is' . $property;
        }

        $parser = new SimpleXmlStringParser();

        $previous = call_user_func([$parser, $getter]);

        try {
            $this->assertTrue($parser === call_user_func([$parser, $setter], $value));
            $this->assertTrue($success);
            $this->assertEquals($value, call_user_func([$parser, $getter]));
        } catch (\InvalidArgumentException $e) {
            $this->assertFalse($success);
            $this->assertEquals($previous, call_user_func([$parser, $getter]));
        }
    }

    public function getterSetterProvider()
    {
        return [
            ['className', DummySimpleXMLElementSubclass::class, true],
            ['className', -1213, false],
            ['className', null, false],
            ['className', \SimpleXMLElement::class, true],
            ['options', 0, true],
            ['options', 128, true],
            ['options', LIBXML_BIGLINES, true],
            ['options', null, false],
            ['options', 'NO', false],
            ['ns', 0, false],
            ['ns', 123.231, false],
            ['ns', '', true],
            ['ns', 'prefix', true],
            ['prefix', true, true],
            ['prefix', false, true],
            ['prefix', 0, false],
            ['prefix', null, false],
            ['disableEntityLoader', null, true],
            ['disableEntityLoader', false, true],
            ['disableEntityLoader', true, true],
            ['disableEntityLoader', 123, false],
        ];
    }
}
