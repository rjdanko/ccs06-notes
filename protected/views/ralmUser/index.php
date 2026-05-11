<?php
/* @var $this RalmUserController */
/* @var $dataProvider CActiveDataProvider */

$this->breadcrumbs=array(
	'Ralm Users',
);

$this->menu=array(
	array('label'=>'Create RalmUser', 'url'=>array('create')),
	array('label'=>'Manage RalmUser', 'url'=>array('admin')),
);
?>

<h1>Ralm Users</h1>

<?php $this->widget('zii.widgets.CListView', array(
	'dataProvider'=>$dataProvider,
	'itemView'=>'_view',
)); ?>
