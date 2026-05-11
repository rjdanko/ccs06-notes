<?php
/* @var $this RalmUserController */
/* @var $model RalmUser */

$this->breadcrumbs=array(
	'Ralm Users'=>array('index'),
	$model->id,
);

$this->menu=array(
	array('label'=>'List RalmUser', 'url'=>array('index')),
	array('label'=>'Create RalmUser', 'url'=>array('create')),
	array('label'=>'Update RalmUser', 'url'=>array('update', 'id'=>$model->id)),
	array('label'=>'Delete RalmUser', 'url'=>'#', 'linkOptions'=>array('submit'=>array('delete','id'=>$model->id),'confirm'=>'Are you sure you want to delete this item?')),
	array('label'=>'Manage RalmUser', 'url'=>array('admin')),
);
?>

<h1>View RalmUser #<?php echo $model->id; ?></h1>

<?php $this->widget('zii.widgets.CDetailView', array(
	'data'=>$model,
	'attributes'=>array(
		'id',
		'username',
		'password',
		'firstname',
		'middlename',
		'lastname',
		'gender',
		'dob',
		'status',
	),
)); ?>
