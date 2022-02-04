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

function getRelativePath($base, $path) {
	// Detect directory separator
	$separator = substr($base, 0, 1);
	$base = array_slice(explode($separator, rtrim($base,$separator)),1);
	$path = array_slice(explode($separator, rtrim($path,$separator)),1);
	return implode($separator, array_slice($path, count($base)));
}

function getFunUuid(callable $fn)
{
    $ref = new ReflectionFunction($fn);
    return $ref->getStartLine() . '_'
        . $ref->getEndLine() . '_'
        . getRelativePath(getcwd(), $ref->getFilename());
}

class ApplyConfiguration
{
    public $results = [];
    public function __construct(array $res)
    {
        $this->results = $res;
    }
}

// app() to apply a command immediately, when result is needed
function app(callable|ApplyConfiguration $fn)
{
    static $conf;
    static $i = 0;
    if (defined('PHPUNIT_DEBUG') && $conf) {
        return $conf->results[$i++];
    }
    if ($fn instanceof ApplyConfiguration) {
        $conf = $fn;
        return;
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

function prepareTableDefinition(string $collation, array $fieldMap)
{
    foreach ($fieldMap as $row) {
        $nrOfAnswers = app(fn () => Answer::model()->countByAttributes(array('qid' => $row['qid'])));
        //$nrOfAnswers = app(new Command("count", fn () => Answer::model()->countByAttributes(array('qid' => $row['qid']))));
        /*
        $oQuestionAttribute = app(fn () => QuestionAttribute::model()->find( "qid = :qid AND attribute = 'max_subquestions'", [':qid' => $row['qid']]));
        if (empty($oQuestionAttribute)) {
            que(function () use ($row, $nrOfAnswers) {
                $oQuestionAttribute = new QuestionAttribute();
                $oQuestionAttribute->qid = $row['qid'];
                $oQuestionAttribute->attribute = 'max_subquestions';
                $oQuestionAttribute->value = $nrOfAnswers;
                $oQuestionAttribute->save();
            }
            );
        } elseif (intval($oQuestionAttribute->value) < 1) {
            // Fix it if invalid : disallow 0, but need a sub question minimum for EM
            que(function () use ($oQuestionAttribute, $nrOfAnswers) {
                $oQuestionAttribute->value = $nrOfAnswers;
                $oQuestionAttribute->save();
            }
            );
        }
         */
    }
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

define('PHPUNIT_DEBUG', 1);

$conf = new ApplyConfiguration(['moo']);
app($conf);

prepareTableDefinition(
    'se',
    [
        ['qid' => 1]
    ]
);
