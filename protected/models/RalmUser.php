<?php

/**
 * This is the model class for table "fin_ralm_user".
 *
 * The followings are the available columns in table 'fin_ralm_user':
 * @property integer $id
 * @property string $username
 * @property string $password
 * @property string $firstname
 * @property string $middlename
 * @property string $lastname
 * @property integer $gender
 * @property string $dob
 * @property integer $status
 */
class RalmUser extends CActiveRecord
{
	private function isSha1Hash($value)
	{
		return is_string($value) && preg_match('/^[a-f0-9]{40}$/i', $value);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'fin_ralm_user';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('username, password, firstname, lastname, gender, dob', 'required'),
			array('gender, status', 'numerical', 'integerOnly'=>true),
			array('username, password, firstname, middlename, lastname', 'length', 'max'=>255),
			array('password', 'length', 'min'=>8),
			array('username', 'unique'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, username, password, firstname, middlename, lastname, gender, dob, status', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * Hash passwords before persisting them.
	 * @return boolean
	 */
	public function beforeSave()
	{
		if(parent::beforeSave())
		{
			if(!empty($this->password) && !$this->isSha1Hash($this->password))
			{
				$this->password = sha1($this->password);
			}

			if($this->status === null || $this->status === '')
			{
				$this->status = 2;
			}

			return true;
		}

		return false;
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'username' => 'Username',
			'password' => 'Password',
			'firstname' => 'Firstname',
			'middlename' => 'Middlename',
			'lastname' => 'Lastname',
			'gender' => 'Gender',
			'dob' => 'Dob',
			'status' => 'Status',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('username',$this->username,true);
		$criteria->compare('password',$this->password,true);
		$criteria->compare('firstname',$this->firstname,true);
		$criteria->compare('middlename',$this->middlename,true);
		$criteria->compare('lastname',$this->lastname,true);
		$criteria->compare('gender',$this->gender);
		$criteria->compare('dob',$this->dob,true);
		$criteria->compare('status',$this->status);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return RalmUser the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
