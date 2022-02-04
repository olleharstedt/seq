<?php

$fn = fn () => 123;
var_dump(&$fn);
$ref = new ReflectionFunction($fn);
echo $ref->getStartLine();
echo $ref->getEndLine();
echo basename($ref->getFilename());

define('PHPUNIT_DEBUG', 1);

class CommandResult
{
    public $uuid;
    public $result;
}

class Command
{
}

function app(callable|Command|CommandResult $fn): mixed
{
    static $results = [];
    if (defined('PHPUNIT_DEBUG')) {
        if ($uuid instanceof ClosureResult) {
            $results[$uuid->uuid] = $uuid->result;
            return $results[$uuid];
        }
    }
    return $fn();
}

function moo()
{
    for ($i = 0; $i < 10; $i++) {
        $result = app(fn () => print('calling database'));
        que(fn () => print('using result ' . $result));
    }
}

moo();
