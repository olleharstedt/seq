<?php

interface CommandNodeInterface
{
    public function run();
    public function before(CommandNodeInterface $c);
    public function then(CommandNodeInterface $c);
}

class CommandNode implements CommandNodeInterface
{
    public $left;
    public $right;
    public $fn;
    public function __construct(callable $fn)
    {
        $this->fn = $fn;
    }
    public function before(CommandNodeInterface $c)
    {
        $this->left = $c;
    }
    public function then(CommandNodeInterface $c)
    {
        $this->right = $c;
    }
    public function run()
    {
        if ($this->left) {
            $this->left->run();
        }
        $fn = $this->fn;
        $result = $fn();
        if ($result && $this->right) {
            $this->right->run();
        }
    }
}

define('RUN', -1);

// do, seq, enqueue, que
// TODO: Events? Must not be delayed.
function seq(...$fns)
{
    /** @var ?CommandNode */
    static $node;

    if (empty($fns)) {
        return $node;
    }

    $tmp = $node;
    foreach ($fns as $fn) {
        if ($fn === RUN) {
            $tmp->run();
            $tmp = $node = null;
        } elseif ($tmp) {
            $new = new CommandNode($fn);
            $tmp->then($new);
            $tmp = $new;
        } else {
            $tmp = new CommandNode($fn);
            $node = $tmp;
        }
    }

    return $node;
}

// app() to apply a command immediately, when result is needed
function app(callable $fn, string $uuid = null)
{
    static $results = [];
    if (defined('PHPUNIT_DEBUG')) {
    }
    // If unit test is def, return pre-baked stuff instead
    return $fn();
}

function createSurveyDirectory(string $dir, bool $createSurveyDir)
{
    // Create the survey directory where the uploaded files can be saved
    if ($createSurveyDir) {
        seq(
            fn () => !file_exists($dir),
            fn () => mkdir($dir, 0777, true) ? true : throw new Exception('Could not create dir'),
            fn () => file_put_contents($dir . '/index.html', '<html><head></head><body></body></html>')
        );
    }
    return true;
}

/**
 * Example on how to test
 */
function testCreateSurveyDirectory()
{
    createSurveyDirectory("dummydir", true);
    seq(RUN);
}

testCreateSurveyDirectory();
