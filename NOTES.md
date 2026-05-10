# Yii 1.1 Blog Guide Notes for This Project

This file is a simplified build guide for `C:\xampp\htdocs\www.sariblog.com`.

It follows the official Yii 1.1 blog tutorial structure, but includes the missing or corrected pieces needed for this project to work cleanly.

Official guide index:

```text
https://www.yiiframework.com/doc/blog/1.1/en
```

Important idea:

When the guide uses code like `$model->url`, `$model->commentCount`, `$comment->authorLink`, or `Comment::model()->pendingCommentCount`, Yii expects either a relation or a getter method to exist.

For example:

```php
$comment->authorLink
```

means Yii will look for:

```php
public function getAuthorLink()
```

---

## Phase 1: Getting Started

### 1. Entry Script

File:

```text
index.php
```

Typical local XAMPP entry script:

```php
<?php

$yii=dirname(__FILE__).'/../yii-1.1.32.e7728e/framework/yii.php';
$config=dirname(__FILE__).'/protected/config/main.php';

defined('YII_DEBUG') or define('YII_DEBUG',true);
defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);

require_once($yii);
Yii::createWebApplication($config)->run();
```

### 2. Application Config Basics

File:

```text
protected/config/main.php
```

Make sure models and components are imported:

```php
'import'=>array(
    'application.models.*',
    'application.components.*',
),
```

This lets Yii autoload files such as:

```text
protected/models/Post.php
protected/models/Comment.php
protected/components/UserMenu.php
```

---

## Phase 2: Initial Prototyping

### 1. Database Connection

File:

```text
protected/config/database.php
```

For this local MySQL project:

```php
<?php

return array(
    'connectionString' => 'mysql:host=localhost;dbname=www.sariblog.com',
    'emulatePrepare' => true,
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8',
);
```

Then load it in:

```text
protected/config/main.php
```

inside `components`:

```php
'db'=>require(dirname(__FILE__).'/database.php'),
```

### 2. Gii Module

File:

```text
protected/config/main.php
```

Use Gii only for development:

```php
'modules'=>array(
    'gii'=>array(
        'class'=>'system.gii.GiiModule',
        'password'=>'sari',
        'ipFilters'=>array('127.0.0.1','::1'),
    ),
),
```

Local Gii URL:

```text
http://localhost/www.sariblog.com/index.php?r=gii
```

### 3. Authentication

File:

```text
protected/components/UserIdentity.php
```

Use database users instead of the default demo hardcoded users:

```php
<?php

class UserIdentity extends CUserIdentity
{
    private $_id;

    public function authenticate()
    {
        $username=strtolower($this->username);
        $user=User::model()->find('LOWER(username)=?',array($username));

        if($user===null)
            $this->errorCode=self::ERROR_USERNAME_INVALID;
        else if(!$user->validatePassword($this->password))
            $this->errorCode=self::ERROR_PASSWORD_INVALID;
        else
        {
            $this->_id=$user->id;
            $this->username=$user->username;
            $this->errorCode=self::ERROR_NONE;
        }

        return $this->errorCode==self::ERROR_NONE;
    }

    public function getId()
    {
        return $this->_id;
    }
}
```

File:

```text
protected/models/User.php
```

Add password helpers:

```php
public function validatePassword($password)
{
    return CPasswordHelper::verifyPassword($password,$this->password);
}

public function hashPassword($password)
{
    return CPasswordHelper::hashPassword($password);
}
```

Note:

If your database has old plain-text passwords such as `demo`, `CPasswordHelper::verifyPassword()` will not validate them. The stored password must be a Yii password hash.

---

## Phase 3: Post Management

### 1. Post Status Constants

File:

```text
protected/models/Post.php
```

Inside the `Post` class:

```php
const STATUS_DRAFT=1;
const STATUS_PUBLISHED=2;
const STATUS_ARCHIVED=3;
```

### 2. Post Validation Rules

File:

```text
protected/models/Post.php
```

Use these rules:

```php
public function rules()
{
    return array(
        array('title, content, status', 'required'),
        array('title', 'length', 'max'=>128),
        array('status', 'in', 'range'=>array(1,2,3)),
        array('tags', 'match', 'pattern'=>'/^[\w\s,]+$/',
            'message'=>'Tags can only contain word characters.'),
        array('tags', 'normalizeTags'),

        array('title, status', 'safe', 'on'=>'search'),
    );
}
```

Add the tag normalizer:

```php
public function normalizeTags($attribute,$params)
{
    $this->tags=Tag::array2string(array_unique(Tag::string2array($this->tags)));
}
```

### 3. Post Relations

File:

```text
protected/models/Post.php
```

Important correction:

The `comments` and `commentCount` relations belong in `Post`, not in `Tag`.

```php
public function relations()
{
    return array(
        'author' => array(self::BELONGS_TO, 'User', 'author_id'),
        'comments' => array(self::HAS_MANY, 'Comment', 'post_id',
            'condition'=>'comments.status='.Comment::STATUS_APPROVED,
            'order'=>'comments.create_time DESC'),
        'commentCount' => array(self::STAT, 'Comment', 'post_id',
            'condition'=>'status='.Comment::STATUS_APPROVED),
    );
}
```

### 4. Post URL Getter

File:

```text
protected/models/Post.php
```

Add:

```php
public function getUrl()
{
    return Yii::app()->createUrl('post/view', array(
        'id'=>$this->id,
        'title'=>$this->title,
    ));
}
```

Then in views you can use:

```php
$data->url
```

or:

```php
$model->url
```

### 5. Auto-fill Create Time, Update Time, and Author

File:

```text
protected/models/Post.php
```

Add:

```php
protected function beforeSave()
{
    if(parent::beforeSave())
    {
        if($this->isNewRecord)
        {
            $this->create_time=$this->update_time=time();
            $this->author_id=Yii::app()->user->id;
        }
        else
            $this->update_time=time();

        return true;
    }
    else
        return false;
}
```

### 6. Track Tag Changes

File:

```text
protected/models/Post.php
```

Add:

```php
protected function afterSave()
{
    parent::afterSave();
    Tag::model()->updateFrequency($this->_oldTags, $this->tags);
}

private $_oldTags;

protected function afterFind()
{
    parent::afterFind();
    $this->_oldTags=$this->tags;
}

protected function afterDelete()
{
    parent::afterDelete();
    Comment::model()->deleteAll('post_id='.$this->id);
    Tag::model()->updateFrequency($this->tags, '');
}
```

### 7. Add Comment to Post

File:

```text
protected/models/Post.php
```

Add:

```php
public function addComment($comment)
{
    if(Yii::app()->params['commentNeedApproval'])
        $comment->status=Comment::STATUS_PENDING;
    else
        $comment->status=Comment::STATUS_APPROVED;

    $comment->post_id=$this->id;
    return $comment->save();
}
```

### 8. Post Form

File:

```text
protected/views/post/_form.php
```

The form should collect only:

```text
title
content
tags
status
```

Do not collect:

```text
create_time
update_time
author_id
```

Those are set by `beforeSave()`.

Use this status dropdown:

```php
<?php echo $form->dropDownList($model,'status',Lookup::items('PostStatus')); ?>
```

Use this tags field:

```php
<?php echo $form->textField($model,'tags',array('size'=>60,'maxlength'=>128)); ?>
```

---

## Phase 4: Lookup Model

### 1. Lookup Helpers

File:

```text
protected/models/Lookup.php
```

Add these inside the class:

```php
private static $_items=array();

public static function items($type)
{
    if(!isset(self::$_items[$type]))
        self::loadItems($type);

    return self::$_items[$type];
}

public static function item($type,$code)
{
    if(!isset(self::$_items[$type]))
        self::loadItems($type);

    return isset(self::$_items[$type][$code]) ? self::$_items[$type][$code] : false;
}

private static function loadItems($type)
{
    self::$_items[$type]=array();
    $models=self::model()->findAll(array(
        'condition'=>'type=:type',
        'params'=>array(':type'=>$type),
        'order'=>'position',
    ));

    foreach($models as $model)
        self::$_items[$type][$model->code]=$model->name;
}
```

This supports:

```php
Lookup::items('PostStatus')
Lookup::item('PostStatus',$data->status)
Lookup::items('CommentStatus')
Lookup::item('CommentStatus',$data->status)
```

---

## Phase 5: Tag Model

### 1. Tag Conversion Helpers

File:

```text
protected/models/Tag.php
```

Add:

```php
public static function string2array($tags)
{
    return preg_split('/\s*,\s*/',trim($tags),-1,PREG_SPLIT_NO_EMPTY);
}

public static function array2string($tags)
{
    return implode(', ',$tags);
}
```

### 2. Tag Frequency Helpers

File:

```text
protected/models/Tag.php
```

The official guide points to the demo for some of these. Add them directly:

```php
public function updateFrequency($oldTags, $newTags)
{
    $oldTags=self::string2array($oldTags);
    $newTags=self::string2array($newTags);
    $this->addTags(array_values(array_diff($newTags,$oldTags)));
    $this->removeTags(array_values(array_diff($oldTags,$newTags)));
}

public function addTags($tags)
{
    if(empty($tags))
        return;

    $criteria=new CDbCriteria;
    $criteria->addInCondition('name',$tags);
    $this->updateCounters(array('frequency'=>1),$criteria);

    foreach($tags as $name)
    {
        if(!$this->exists('name=:name',array(':name'=>$name)))
        {
            $tag=new Tag;
            $tag->name=$name;
            $tag->frequency=1;
            $tag->save();
        }
    }
}

public function removeTags($tags)
{
    if(empty($tags))
        return;

    $criteria=new CDbCriteria;
    $criteria->addInCondition('name',$tags);
    $this->updateCounters(array('frequency'=>-1),$criteria);
    $this->deleteAll('frequency<=0');
}
```

### 3. Tag Cloud Weights

File:

```text
protected/models/Tag.php
```

Add:

```php
public function findTagWeights($limit=20)
{
    $models=$this->findAll(array(
        'order'=>'frequency DESC',
        'limit'=>$limit,
    ));

    $total=0;
    foreach($models as $model)
        $total+=$model->frequency;

    $tags=array();
    if($total>0)
    {
        foreach($models as $model)
            $tags[$model->name]=8+(int)(16*$model->frequency/($total+10));

        ksort($tags);
    }

    return $tags;
}
```

---

## Phase 6: Comment Management

### 1. Comment Status Constants

File:

```text
protected/models/Comment.php
```

Inside the class:

```php
const STATUS_PENDING=1;
const STATUS_APPROVED=2;
```

### 2. Comment Rules

File:

```text
protected/models/Comment.php
```

Use:

```php
public function rules()
{
    return array(
        array('content, author, email', 'required'),
        array('author, email, url', 'length', 'max'=>128),
        array('email','email'),
        array('url','url'),
    );
}
```

### 3. Comment Labels

File:

```text
protected/models/Comment.php
```

Use:

```php
public function attributeLabels()
{
    return array(
        'id' => 'Id',
        'content' => 'Comment',
        'status' => 'Status',
        'create_time' => 'Create Time',
        'author' => 'Name',
        'email' => 'Email',
        'url' => 'Website',
        'post_id' => 'Post',
    );
}
```

### 4. Comment Save Time

File:

```text
protected/models/Comment.php
```

Add:

```php
protected function beforeSave()
{
    if(parent::beforeSave())
    {
        if($this->isNewRecord)
            $this->create_time=time();

        return true;
    }
    else
        return false;
}
```

### 5. Comment Approval

File:

```text
protected/models/Comment.php
```

Add:

```php
public function approve()
{
    $this->status=Comment::STATUS_APPROVED;
    $this->update(array('status'));
}
```

### 6. Pending Comment Count

File:

```text
protected/models/Comment.php
```

Important correction:

The UserMenu view uses:

```php
Comment::model()->pendingCommentCount
```

So the model must have:

```php
public function getPendingCommentCount()
{
    return $this->count('status='.self::STATUS_PENDING);
}
```

### 7. Comment URL and Author Link

File:

```text
protected/models/Comment.php
```

These support the Recent Comments portlet:

```php
public function getUrl()
{
    return Yii::app()->createUrl('post/view', array(
        'id'=>$this->post_id,
        'title'=>$this->post->title,
    )).'#c'.$this->id;
}

public function getAuthorLink()
{
    if(!empty($this->url))
        return CHtml::link(CHtml::encode($this->author), $this->url);
    else
        return CHtml::encode($this->author);
}
```

### 8. Recent Comments Query

File:

```text
protected/models/Comment.php
```

Add:

```php
public function findRecentComments($limit=10)
{
    return $this->with('post')->findAll(array(
        'condition'=>'t.status='.self::STATUS_APPROVED,
        'order'=>'t.create_time DESC',
        'limit'=>$limit,
    ));
}
```

### 9. Comment Controller: Index

File:

```text
protected/controllers/CommentController.php
```

Use:

```php
public function actionIndex()
{
    $dataProvider=new CActiveDataProvider('Comment', array(
        'criteria'=>array(
            'with'=>'post',
            'order'=>'t.status, t.create_time DESC',
        ),
    ));

    $this->render('index',array(
        'dataProvider'=>$dataProvider,
    ));
}
```

### 10. Comment Controller: Approve

File:

```text
protected/controllers/CommentController.php
```

Important correction:

Use `$id`. The guide version can be confusing because `loadModel()` needs an ID in the generated controller.

```php
public function actionApprove($id)
{
    if(Yii::app()->request->isPostRequest)
    {
        $comment=$this->loadModel($id);
        $comment->approve();
        $this->redirect(array('index'));
    }
    else
        throw new CHttpException(400,'Invalid request...');
}
```

---

## Phase 7: Post Display and Comments

### 1. PostController View Action

File:

```text
protected/controllers/PostController.php
```

Use:

```php
public function actionView()
{
    $post=$this->loadModel();
    $comment=$this->newComment($post);

    $this->render('view',array(
        'model'=>$post,
        'comment'=>$comment,
    ));
}
```

### 2. New Comment Handler

File:

```text
protected/controllers/PostController.php
```

Use:

```php
protected function newComment($post)
{
    $comment=new Comment;

    if(isset($_POST['ajax']) && $_POST['ajax']==='comment-form')
    {
        echo CActiveForm::validate($comment);
        Yii::app()->end();
    }

    if(isset($_POST['Comment']))
    {
        $comment->attributes=$_POST['Comment'];
        if($post->addComment($comment))
        {
            if($comment->status==Comment::STATUS_PENDING)
                Yii::app()->user->setFlash('commentSubmitted','Thank you for your comment. Your comment will be posted once it is approved.');

            $this->refresh();
        }
    }

    return $comment;
}
```

### 3. Post View Comments Section

File:

```text
protected/views/post/view.php
```

Use this pattern:

```php
<div id="comments">
    <?php if($model->commentCount>=1): ?>
        <h3>
            <?php echo $model->commentCount>1 ? $model->commentCount . ' comments' : 'One comment'; ?>
        </h3>

        <?php $this->renderPartial('_comments',array(
            'post'=>$model,
            'comments'=>$model->comments,
        )); ?>
    <?php endif; ?>

    <h3>Leave a Comment</h3>

    <?php if(Yii::app()->user->hasFlash('commentSubmitted')): ?>
        <div class="flash-success">
            <?php echo Yii::app()->user->getFlash('commentSubmitted'); ?>
        </div>
    <?php else: ?>
        <?php $this->renderPartial('/comment/_form',array(
            'model'=>$comment,
        )); ?>
    <?php endif; ?>
</div>
```

### 4. Comments Partial

File:

```text
protected/views/post/_comments.php
```

Simple version:

```php
<?php foreach($comments as $comment): ?>
<div class="comment" id="c<?php echo $comment->id; ?>">
    <div class="author">
        <?php echo $comment->authorLink; ?> says:
    </div>

    <div class="time">
        <?php echo date('F j, Y \a\t h:i a',$comment->create_time); ?>
    </div>

    <div class="content">
        <?php echo nl2br(CHtml::encode($comment->content)); ?>
    </div>
</div>
<?php endforeach; ?>
```

---

## Phase 8: Layouts and Sidebars

### 1. Base Controller Layout

File:

```text
protected/components/Controller.php
```

Default layout:

```php
public $layout='//layouts/column1';
```

### 2. Post and Comment Controllers Use Two Columns

Files:

```text
protected/controllers/PostController.php
protected/controllers/CommentController.php
```

Add inside each controller:

```php
public $layout='//layouts/column2';
```

### 3. Column 2 Layout

File:

```text
protected/views/layouts/column2.php
```

Use:

```php
<?php $this->beginContent('//layouts/main'); ?>
<div class="span-19">
    <div id="content">
        <?php echo $content; ?>
    </div>
</div>
<div class="span-5 last">
    <div id="sidebar">
        <?php if(!Yii::app()->user->isGuest) $this->widget('UserMenu'); ?>

        <?php $this->widget('TagCloud', array(
            'maxTags'=>Yii::app()->params['tagCloudCount'],
        )); ?>

        <?php $this->widget('RecentComments', array(
            'maxComments'=>Yii::app()->params['recentCommentCount'],
        )); ?>
    </div>
</div>
<?php $this->endContent(); ?>
```

Expected behavior:

Logged out:

```text
Tags
Recent Comments
```

Logged in:

```text
UserMenu
Tags
Recent Comments
```

---

## Phase 9: Portlets

### 1. UserMenu Component

File:

```text
protected/components/UserMenu.php
```

Use:

```php
<?php

Yii::import('zii.widgets.CPortlet');

class UserMenu extends CPortlet
{
    public function init()
    {
        $this->title=CHtml::encode(Yii::app()->user->name);
        parent::init();
    }

    protected function renderContent()
    {
        $this->render('userMenu');
    }
}
```

### 2. UserMenu View

File:

```text
protected/components/views/userMenu.php
```

Use:

```php
<ul>
    <li><?php echo CHtml::link('Create New Post',array('post/create')); ?></li>
    <li><?php echo CHtml::link('Manage Posts',array('post/admin')); ?></li>
    <li><?php echo CHtml::link('Approve Comments',array('comment/index'))
        . ' (' . Comment::model()->pendingCommentCount . ')'; ?></li>
    <li><?php echo CHtml::link('Logout',array('site/logout')); ?></li>
</ul>
```

If this fails with:

```text
Property "Comment.pendingCommentCount" is not defined.
```

then `Comment::getPendingCommentCount()` is missing.

### 3. TagCloud Component

File:

```text
protected/components/TagCloud.php
```

Use:

```php
<?php

Yii::import('zii.widgets.CPortlet');

class TagCloud extends CPortlet
{
    public $title='Tags';
    public $maxTags=20;

    protected function renderContent()
    {
        $tags=Tag::model()->findTagWeights($this->maxTags);

        foreach($tags as $tag=>$weight)
        {
            $link=CHtml::link(CHtml::encode($tag), array('post/index','tag'=>$tag));
            echo CHtml::tag('span', array(
                'class'=>'tag',
                'style'=>"font-size:{$weight}pt",
            ), $link)."\n";
        }
    }
}
```

### 4. RecentComments Component

File:

```text
protected/components/RecentComments.php
```

Use:

```php
<?php

Yii::import('zii.widgets.CPortlet');

class RecentComments extends CPortlet
{
    public $title='Recent Comments';
    public $maxComments=10;

    public function getRecentComments()
    {
        return Comment::model()->findRecentComments($this->maxComments);
    }

    protected function renderContent()
    {
        $this->render('recentComments');
    }
}
```

### 5. RecentComments View

File:

```text
protected/components/views/recentComments.php
```

Use:

```php
<ul>
    <?php foreach($this->getRecentComments() as $comment): ?>
    <li><?php echo $comment->authorLink; ?> on
        <?php echo CHtml::link(CHtml::encode($comment->post->title), $comment->getUrl()); ?>
    </li>
    <?php endforeach; ?>
</ul>
```

If this fails, check that `Comment` has:

```php
getAuthorLink()
getUrl()
findRecentComments()
```

---

## Phase 10: Final Work

### 1. Pretty URLs

File:

```text
protected/config/main.php
```

Inside `components`:

```php
'urlManager'=>array(
    'urlFormat'=>'path',
    'rules'=>array(
        'post/<id:\d+>/<title:.*?>'=>'post/view',
        'posts/<tag:.*?>'=>'post/index',
        '<controller:\w+>/<action:\w+>'=>'<controller>/<action>',
    ),
),
```

This supports URLs like:

```text
/index.php/posts/yii
/index.php/post/2/A+Test+Post
/index.php/post/update?id=1
```

### 2. Blog as Homepage

File:

```text
protected/config/main.php
```

Near the top:

```php
'defaultController'=>'post',
```

### 3. Required Params

File:

```text
protected/config/main.php
```

Inside `params`:

```php
'params'=>array(
    'adminEmail'=>'webmaster@example.com',
    'commentNeedApproval'=>true,
    'tagCloudCount'=>20,
    'recentCommentCount'=>10,
),
```

Without these params, the code can fail when rendering comments or sidebar portlets.

---

## Quick Test Checklist

Open these URLs:

```text
http://localhost/www.sariblog.com/index.php
http://localhost/www.sariblog.com/index.php/post/index
http://localhost/www.sariblog.com/index.php/site/login
http://localhost/www.sariblog.com/index.php/post/create
http://localhost/www.sariblog.com/index.php/comment/index
```

When logged out, `post/index` should show:

```text
Tags
Recent Comments
```

After login, `post/index` should show:

```text
Create New Post
Manage Posts
Approve Comments
Logout
Tags
Recent Comments
```

---

## Common Errors and Fixes

### Error: `Property "Comment.pendingCommentCount" is not defined`

Fix:

```text
Add getPendingCommentCount() to protected/models/Comment.php
```

### Error: `Relation "commentCount" is not defined in active record class "Post"`

Fix:

```text
Add commentCount relation to protected/models/Post.php
```

### Error: tag cloud fails

Fix:

```text
Add findTagWeights() to protected/models/Tag.php
```

Also make sure this config exists:

```php
'tagCloudCount'=>20,
```

### Error: recent comments fails

Fix:

```text
Add findRecentComments(), getAuthorLink(), and getUrl() to protected/models/Comment.php
```

Also make sure this config exists:

```php
'recentCommentCount'=>10,
```

### Error: creating comments fails because param is missing

Fix:

```php
'commentNeedApproval'=>true,
```

### Error: approve comment action fails

Fix:

Use:

```php
public function actionApprove($id)
```

and:

```php
$comment=$this->loadModel($id);
```

---

## Official Yii Guide Pages Used

```text
https://www.yiiframework.com/doc/blog/1.1/en
https://www.yiiframework.com/doc/blog/1.1/en/start.overview
https://www.yiiframework.com/doc/blog/1.1/en/post.model
https://www.yiiframework.com/doc/blog/1.1/en/post.create
https://www.yiiframework.com/doc/blog/1.1/en/post.display
https://www.yiiframework.com/doc/blog/1.1/en/post.admin
https://www.yiiframework.com/doc/blog/1.1/en/comment.model
https://www.yiiframework.com/doc/blog/1.1/en/comment.create
https://www.yiiframework.com/doc/blog/1.1/en/comment.admin
https://www.yiiframework.com/doc/blog/1.1/en/portlet.menu
https://www.yiiframework.com/doc/blog/1.1/en/portlet.tags
https://www.yiiframework.com/doc/blog/1.1/en/portlet.comments
https://www.yiiframework.com/doc/blog/1.1/en/final.url
```
