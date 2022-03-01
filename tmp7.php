<?php

$nr    = Foo::countByAttribue(['foo' => 1]);
$model = Model::find(['id' => 2]);
if (empty($model)) {
    $model     = new Model();
    $model->nr = $nr;
    $model->save();
} elseif ($model->value < 1) {
    $model->value = $nr;
    $model->save();
}

$st
    ->env('nr', countByAttribue(Foo::class, ['id' => 1]))
    ->env('model', findById(Model::class, 2)
    ->if(fn ($model) => empty($model))
    ->do(fn ($nr) => $model = new Model(); $model->nr; $model->save())
    ->elseif(fn ($model) => $model->value < 1)
    ->do(fn ($model, $nr) => $model->nr = $nr; $model->save())

// read-read-if-write-else-write
