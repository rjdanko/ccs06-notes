<?php
/* @var $this RalmUserController */
/* @var $model RalmUser */

$this->breadcrumbs=array(
	'Ralm Users'=>array('index'),
	'Create',
);

$this->menu=array(
	array('label'=>'List RalmUser', 'url'=>array('index')),
	array('label'=>'Manage RalmUser', 'url'=>array('admin')),
);
?>

<h1>Create RalmUser</h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>