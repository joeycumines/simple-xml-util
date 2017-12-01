# simple-xml-util

Utilities to improve maintainability of code that relies on PHP's 
simplexml_load_string.

## Features

- Interface for better dependency injection
- Automatically manage internal errors, builds readable error string
- Makes it possible to use exception handling
- Configure `simplexml_load_string`, `libxml_use_internal_errors` and 
    `libxml_disable_entity_loader`, without it feeling like you are relying on 
    globals with side effects

## Example

```php
// manual configuration example

use JoeyCumines\SimpleXmlUtil\Parser\SimpleXmlStringParser;

// ...

$xmlParser = new SimpleXmlStringParser($className, $options, $ns, $prefix, $disableEntityLoader);

// or

$xmlParser = (new SimpleXmlStringParser())
    ->setClassName($className)
    ->setOptions($options)
    ->setNs($ns)
    ->setPrefix($prefix)
    ->setDisableEntityLoader($disableEntityLoader);


// use the interface in the service - pre-configured

use JoeyCumines\SimpleXmlUtil\Parser\SimpleXmlStringParser;

// ...

class SomeService
{
    private $xmlParser;
    private $logger;

    public function __construct(
        SimpleXmlStringParser $xmlParser,
        Logger $logger
    ) {
        $this->xmlParser = $xmlParser;
        $this->logger = $logger;
    }
    
    public function doSomeXmlParsing(string $data): array
    {
        try {
            $doc = $this->xmlParser->parseXmlString($data);
        } catch (SimpleXmlStringParserException $e) {
            // the actual parser error will come through in the logs
            $this->logger->error(
                "[SomeService] o no our xml things failed:\n{$e}",
                $e
            );
            
            throw $e;
        }
        
        // at this point you can always be sure $doc is an actual object
        
        foreach ($doc as $name => $child) {
            // ...
        }
        
        // ...
    }
}
```
