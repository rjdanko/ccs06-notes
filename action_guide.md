User table SQL Statement: 
CREATE TABLE `fin_RALM_user` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `username`   VARCHAR(255) NOT NULL UNIQUE,
  `password`   VARCHAR(255) NOT NULL,
  `firstname`  VARCHAR(255) NOT NULL,
  `middlename` VARCHAR(255)          DEFAULT NULL,
  `lastname`   VARCHAR(255) NOT NULL,
  `gender`     INT(1)       NOT NULL,
  `dob`        DATE         NOT NULL,
  `status`     INT(1)       NOT NULL DEFAULT 2,

  PRIMARY KEY (`id`),

  CONSTRAINT `chk_password_length`
    CHECK (CHAR_LENGTH(`password`) >= 8),

  CONSTRAINT `chk_status_range`
    CHECK (`status` IN (1, 2, 3))
);


Code to change textbox in _form.php:
	<div class="row">
		<?php echo $form->labelEx($model,'gender'); ?>
		<?php echo $form->radioButtonList($model,'gender', array(
			1 => 'Male',
			2 => 'Female',
		), array(
			'separator' => ' ',
			'labelOptions' => array('style' => 'display:inline; font-weight:normal;'),
		)); ?>
		<?php echo $form->error($model,'gender'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'dob'); ?>
		<?php $this->widget('zii.widgets.jui.CJuiDatePicker', array(
			'name' => CHtml::activeName($model, 'dob'),
			'value' => $model->dob,
			'options' => array(
				'dateFormat' => 'yy-mm-dd',
				'changeMonth' => true,
				'changeYear' => true,
			),
			'htmlOptions' => array(
				'size' => 20,
				'maxlength' => 10,
			),
		)); ?>
		<?php echo $form->error($model,'dob'); ?>
	</div>

	<div class="row">
		<?php echo $form->labelEx($model,'status'); ?>
		<?php echo $form->dropDownList($model,'status', array(
			1 => 'Active',
			2 => 'Inactive',
			3 => 'Deleted',
		), array('prompt' => 'Select status')); ?>
		<?php echo $form->error($model,'status'); ?>
	</div>

