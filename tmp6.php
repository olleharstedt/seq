<?php

namespace Tmp6;

use InvalidArgumentException;

interface Node
{
}

class Echo_ implements Node
{
    public $str;
    public function __construct(string $str)
    {
        $this->str = $str;
    }
}

function echo_($str)
{
    return new Echo_($str);
}

class If_ implements Node
{
    public $if;
    public $then;

    public function __construct(Node|Callable $if)
    {
        $this->if = $if;
    }

    public function setThen(Node $if)
    {
        $this->then = $if;
    }
}

class FileExists implements Node
{
    public $file;
    public function __construct($file)
    {
        $this->file = $file;
    }
}

function fileExists(string $file)
{
    return new FileExists($file);
}

class FileGetContents implements Node
{
    public $file;
    public function __construct($file)
    {
        $this->file = $file;
    }
}

function fileGetContents(string $file)
{
    return new FileGetContents($file);
}

class Set implements Node
{
    public $var;
    public $val;
    public function __construct(mixed &$var, mixed $val)
    {
        $this->var = &$var;
        $this->val = $val;
    }
}

function set(&$var, $val)
{
    return new Set($var, $val);
}

interface EvaluatorInterface
{
    public function evalNode(Node $node);
}

class NodeEvaluator implements EvaluatorInterface
{
    /**
     * @return mixed
     */
    public function evalNode(Node $node)
    {
        $className = get_class_name($node::class);
        switch ($className) {
            case "If_": 
                if ($this->evalNode($node->if)) {
                    $this->evalNode($node->then);
                } elseif (!empty($node->else)) {
                    $this->evalNode($node->else);
                }
                break;
            case "Save":
                return $node->model->save();
                break;
            case "PushToStack":
                $node->stack->push($node->thing);
                break;
            case "FileExists":
                return file_exists($node->file);
            case "FileGetContents":
                return file_get_contents($node->file);
            case "Set":
                if ($node->val instanceof Node) {
                    $node->var = $this->evalNode($node->val);
                } elseif (gettype($node->val) === 'string') {
                    $node->var = $node->val;
                } else {
                    throw new InvalidArgumentException('Unknown type of val in set: ' . gettype($node->val));
                }
                break;
            default:
                throw new InvalidArgumentException('Unsupported node type: ' . $className);
        }
    }
}

class DryRunEvaluator implements EvaluatorInterface
{
    public $log = [];
    public $returnValues = [];

    /**
     * @return mixed
     */
    public function evalNode($node)
    {
        $className = get_class_name($node::class);
        switch ($className) {
            case "If_": 
                $this->log[] = "Evaluating if";
                if ($this->evalNode($node->if)) {
                    $this->log[] = "Evaluating then";
                    $this->evalNode($node->then);
                } elseif (!empty($node->else)) {
                    $this->log[] = "Evaluating else";
                    $this->evalNode($node->else);
                }
                break;
            case "Save":
                $this->log[] = "Save model";
                return array_pop($this->returnValues);
            case "PushToStack":
                $this->log[] = "Push thing to stack: " . $node->thing;
                break;
            case "FileExists":
                $this->log[] = "File exists";
                return array_pop($this->returnValues);
            case "FileGetContents":
                $this->log[] = "File get contents";
                return array_pop($this->returnValues);
            case "Set":
                if ($node->val instanceof Node) {
                    $node->var = $this->evalNode($node->val);
                } elseif (gettype($node->val) === 'string') {
                    $node->var = $node->val;
                } else {
                    throw new InvalidArgumentException('Unknown type of val in set: ' . gettype($node->val));
                }
                break;
            default:
                throw new InvalidArgumentException('Unsupported node type: ' . $className);
        }
    }
}


class St
{
    public $queue = [];
    public $ev;

    public function __construct(EvaluatorInterface $ev)
    {
        $this->ev = $ev;
    }

    public function if($if)
    {
        $this->queue[] = new If_($if);
        return $this;
    }

    public function then($if)
    {
        $i = \count($this->queue) - 1;
        if ($this->queue[$i] instanceof If_) {
            $this->queue[$i]->setThen($if);
        } else {
            throw new InvalidArgumentException('then must come after if');
        }
        return $this;
    }

    public function run($ev)
    {
        foreach ($this->queue as $node) {
            $ev->evalNode($node);
        }
    }

    public function load($model, $id)
    {
        //var_dump(get_class($model));
    }

    public function set($var, $value)
    {
        //var_dump(func_get_args());
        return $this;
    }

    public function __invoke()
    {
        foreach ($this->queue as $node) {
            $this->ev->evalNode($node);
        }
    }
}

class Model
{
    public function __construct()
    {
    }
}

class LoadModel
{
    public function __construct($id)
    {
        return new Model($id);
    }
}

class Load
{
}

class FindById
{
    public $modelName;
    public $id;
    public function __construct(int $id)
    {
    }
}

class LoadById implements Node
{
    public $modelName;
    public $id;
    public function __construct(string $modelName, int $id)
    {
    }
}

function loadById($class, $id)
{
    return new LoadById($class, $id);
}

function get_class_name($classname)
{
    if ($pos = strrpos($classname, '\\'))
        return substr($classname, $pos + 1);
    return $pos;
}

//$st = new St();
//$st
    //->if(fn ($model = new LoadModel(10)) => $model->x > 10)
    //->then(echo_("asd"));

/*
$st
    ->if(fn ($model = new Load(Model::class, 10)) => $model->x > 10)
    ->then(echo_("asd"));

$st->set('m', loadById(Model::class, 10))->if(fn ($m) => $m->x > 10)->then(echo_("asd"));

//$fn = fn(LoadModel $model = new LoadModel(10)) => $model->x > 10;
//$fn = fn (Model $model = new Load(Model::class, 10)) => $model->x > 10;
$fn = fn ($model = new FindById(10)) => $model->x > 10;
$ref = new ReflectionFunction($fn);
//var_dump($ref->getParameters()[0]->getType()->getName());
var_dump($ref->getParameters()[0]->getDefaultValue());
$defClass = $ref->getParameters()[0]->getDefaultValue();
//$def = new $defClass();
 */

//$st("if fileExists($file) then set $result = fileGetContents($file)");


function getUpperText(string $file, St $st)
{
    $result = 'DEFAULT';
    $st
        ->if(fileExists($file))
        ->then(set($result, fileGetContents($file)))
        ();
    return strtoupper($result);
}

function getUpperTextMock(string $file, IO $io)
{
    $result = 'DEFAULT';
    if ($io->fileExists($file)) {
        $result = $io->fileGetContents($file);
    }
    return strtoupper($result);
}

$ev = new DryRunEvaluator();
$ev->returnValues = array_reverse(
    [
        false,
        'Some example file content, bla bla bla'
    ]
);
$st = new St($ev);

$text = getUpperText('moo.txt', $st);
var_dump($st->ev->log);
var_dump($text);
