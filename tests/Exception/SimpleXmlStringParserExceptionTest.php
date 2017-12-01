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

    public function testConstruct()
    {
        $libXmlError = new \LibXMLError();
        $errors = [
            $libXmlError,
            new \stdClass(),
            'message!',
        ];
        $message = 'e_m';
        $code = 512;
        $previous = new \Exception();

        $e = new SimpleXmlStringParserException($errors, $message, $code, $previous);

        $this->assertCount(2, $e->getErrors());
        $this->assertTrue([0, 1] === array_keys($e->getErrors()));
        $errors = $e->getErrors();
        $this->assertTrue($libXmlError === $errors[0]);
        $error2 = (array)($errors[1]);
        $this->assertCount(1, $error2);
        $this->assertTrue(array_key_exists('message', $error2));
        $this->assertEquals('message!', $error2['message']);
        $this->assertEquals('e_m', $e->getMessage());
        $this->assertEquals(512, $e->getCode());
        $this->assertTrue($e->getPrevious() === $previous);
    }
}
