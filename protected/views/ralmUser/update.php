<?php
/* @var $this RalmUserController */
/* @var $model RalmUser */

$this->breadcrumbs=array(
	'Ralm Users'=>array('index'),
	$model->id=>array('view','id'=>$model->id),
	'Update',
);

$this->menu=array(
	array('label'=>'List RalmUser', 'url'=>array('index')),
	array('label'=>'Create RalmUser', 'url'=>array('create')),
	array('label'=>'View RalmUser', 'url'=>array('view', 'id'=>$model->id)),
	array('label'=>'Manage RalmUser', 'url'=>array('admin')),
);
?>

<h1>Update RalmUser <?php echo $model->id; ?></h1>

<?php $this->renderPartial('_form', array('model'=>$model)); ?>