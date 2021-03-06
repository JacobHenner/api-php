--TEST--
JsonSchema: Draft 3, validate addressbook, card schema enforced on items
--FILE--
<?php
require __DIR__ . '/setup.php.inc';
require __DIR__ . '/schema.setup.php.inc';

$a = function ($test_name, $schema_uri) use ($test, $env) {
    $schema = array( '$ref'=> $schema_uri );

    $test->assertSchemaValidateFail(array('The number of items is less than the required minimum [schema path: #/cards/0]'),
                                    $env->validate(array( "cards"=> array( array())), $schema),
             "cards schema is enforced on items " . $test_name);
};

$a("Explicit Schema", "http://example.com/addressbook.json");
$a("Referring Schema", "http://example.com/addressbook_ref.json");
$a("Extends Schema", "http://example.com/addressbook_extends.json");
?>
===DONE===
--EXPECT--
===DONE===