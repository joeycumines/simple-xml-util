<?php

namespace Tests\JoeyCumines\SimpleXmlUtil\Exception;

use JoeyCumines\SimpleXmlUtil\Exception\SimpleXmlStringParserException;
use PHPUnit\Framework\TestCase;

class SimpleXmlStringParserExceptionTest extends TestCase
{
    /**
     * @param mixed $value
     * @param array|null $expected
     *
     * @dataProvider valueToLibXmlErrorProvider
     */
    public function testValueToLibXmlError($value, $expected)
    {
        $actual = SimpleXmlStringParserException::valueToLibXmlError($value);
        $this->assertTrue($actual instanceof \LibXMLError || null === $actual);
        if (null !== $actual) {
            $this->assertTrue(!$value instanceof \LibXMLError || $value === $actual);
            $actual = (array)$actual;
            foreach ($actual as $k => $v) {
                if (null === $v) {
                    unset($actual[$k]);
                }
            }
        }
        $this->assertTrue(
            $expected === $actual,
            "EXPECTED:\n" . print_r($expected, true) . "\nACTUAL:\n" . print_r($actual, true)
        );
    }

    public function valueToLibXmlErrorProvider()
    {
        $exception = new \Exception('dummy', 2);
        $libXmlError = new \LibXMLError();
        $libXmlError->code = 1;
        $libXmlError->message = 'message';
        return [
            [
                'a string',
                [
                    'message' => 'a string',
                ],
            ],
            [
                12,
                [
                    'message' => '12',
                ],
            ],
            [
                14314.23,
                [
                    'message' => '14314.23',
                ],
            ],
            [
                new \stdClass(),
                null,
            ],
            [
                $exception,
                [
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ],
            ],
            [
                $libXmlError,
                [
                    'code' => $libXmlError->code,
                    'message' => $libXmlError->message,
                ],
            ],
            [
                null,
                null,
            ],
            [
                '',
                null,
            ],
            [
                '    ',
                null,
            ],
            [
                [1, 2, 3],
                null,
            ],
        ];
    }
}
