# Yii Blog Guide Corrections for This Project

This file only lists corrections and missing pieces needed while following the official Yii 1.1 blog guide.

Official guide:

```text
https://www.yiiframework.com/doc/blog/1.1/en
```

The website guide is useful, but some code is incomplete, points to the demo app instead of showing full code, or assumes generated methods have a different signature. Use these corrections when your project breaks while following the guide.

---

## 1. `Comment.pendingCommentCount` Is Missing

Guide page:

```text
https://www.yiiframework.com/doc/blog/1.1/en/portlet.menu
```

The guide's `userMenu.php` uses:

```php
Comment::model()->pendingCommentCount
```

But this property does not exist unless you add a getter to the `Comment` model.

File:

```text
protected/models/Comment.php
```

Add inside the `Comment` class:

```php
public function getPendingCommentCount()
{
    return $this->count('status='.self::STATUS_PENDING);
}
```

Why:

Yii maps this:

```php
$model->pendingCommentCount
```

to this:

```php
$model->getPendingCommentCount()
```

---

## 2. `commentCount` Relation Belongs in `Post`, Not `Tag`

Guide page:

```text
https://www.yiiframework.com/doc/blog/1.1/en/post.model
```

If you see:

```text
Relation "commentCount" is not defined in active record class "Post".
```

then the `commentCount` relation is missing from `Post`.

File:

```text
protected/models/Post.php
```

Use this `relations()` method:

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

Do not put these relations in `Tag.php`.

---

## 3. `Tag::updateFrequency()` and Related Methods Are Not Fully Shown

Guide page:

```text
https://www.yiiframework.com/doc/blog/1.1/en/post.create
```

The guide says to refer to the demo app for `Tag::updateFrequency()`. Add the missing methods directly.

File:

```text
protected/models/Tag.php
```

Add inside the `Tag` class:

```php
public static function string2array($tags)
{
    return preg_split('/\s*,\s*/',trim($tags),-1,PREG_SPLIT_NO_EMPTY);
}

public static function array2string($tags)
{
    return implode(', ',$tags);
}

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

Why:

`Post::afterSave()` calls:

```php
Tag::model()->updateFrequency($this->_oldTags, $this->tags);
```

So the `Tag` model must actually define `updateFrequency()`.

---

## 4. `Tag::findTagWeights()` Is Needed by the TagCloud Portlet

Guide page:

```text
https://www.yiiframework.com/doc/blog/1.1/en/portlet.tags
```

The `TagCloud` portlet calls:

```php
Tag::model()->findTagWeights($this->maxTags)
```

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

## 5. Missing Config Params for Comments and Portlets

Guide pages:

```text
https://www.yiiframework.com/doc/blog/1.1/en/comment.create
https://www.yiiframework.com/doc/blog/1.1/en/portlet.tags
https://www.yiiframework.com/doc/blog/1.1/en/portlet.comments
```

Your code uses these params:

```php
Yii::app()->params['commentNeedApproval']
Yii::app()->params['tagCloudCount']
Yii::app()->params['recentCommentCount']
```

File:

```text
protected/config/main.php
```

Inside `params`, add:

```php
'params'=>array(
    'adminEmail'=>'webmaster@example.com',
    'commentNeedApproval'=>true,
    'tagCloudCount'=>20,
    'recentCommentCount'=>10,
),
```

If your `params` array already exists, only add the three missing lines:

```php
'commentNeedApproval'=>true,
'tagCloudCount'=>20,
'recentCommentCount'=>10,
```

---

## 6. `Comment::getUrl()`, `getAuthorLink()`, and `findRecentComments()` Are Needed

Guide page:

```text
https://www.yiiframework.com/doc/blog/1.1/en/portlet.comments
```

The Recent Comments view uses:

```php
$comment->authorLink
$comment->getUrl()
```

The RecentComments component uses:

```php
Comment::model()->findRecentComments($this->maxComments)
```

File:

```text
protected/models/Comment.php
```

Add inside the `Comment` class:

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

public function findRecentComments($limit=10)
{
    return $this->with('post')->findAll(array(
        'condition'=>'t.status='.self::STATUS_APPROVED,
        'order'=>'t.create_time DESC',
        'limit'=>$limit,
    ));
}
```

---

## 7. `CommentController::actionApprove()` Needs `$id`

Guide page:

```text
https://www.yiiframework.com/doc/blog/1.1/en/comment.admin
```

The guide shows `actionApprove()` calling:

```php
$comment=$this->loadModel();
```

But the generated Yii 1.1 controller usually has:

```php
public function loadModel($id)
```

So the approve action must accept and pass `$id`.

File:

```text
protected/controllers/CommentController.php
```

Use:

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

## 8. Post Form Should Not Ask for Auto-filled Fields

Guide page:

```text
https://www.yiiframework.com/doc/blog/1.1/en/post.create
```

The post form should only collect:

```text
title
content
tags
status
```

Do not show inputs for:

```text
create_time
update_time
author_id
```

Those values are set in `Post::beforeSave()`.

File:

```text
protected/views/post/_form.php
```

Use this tags field:

```php
<?php echo $form->textField($model,'tags',array('size'=>60,'maxlength'=>128)); ?>
```

Use this status dropdown:

```php
<?php echo $form->dropDownList($model,'status',Lookup::items('PostStatus')); ?>
```

Remove generated fields for:

```php
$form->textField($model,'create_time')
$form->textField($model,'update_time')
$form->textField($model,'author_id')
```

---

## 9. Blog Homepage Should Use `PostController`

Guide page:

```text
https://www.yiiframework.com/doc/blog/1.1/en/final.deploy
```

The guide changes the homepage to the post list page.

File:

```text
protected/config/main.php
```

Near the top of the returned array, add:

```php
'defaultController'=>'post',
```

Example:

```php
return array(
    'basePath'=>dirname(__FILE__).DIRECTORY_SEPARATOR.'..',
    'name'=>'My Web Application',
    'defaultController'=>'post',
    ...
);
```

---

## 10. Pretty URL Rules

Guide page:

```text
https://www.yiiframework.com/doc/blog/1.1/en/final.url
```

File:

```text
protected/config/main.php
```

Inside `components`, use:

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

This supports:

```text
/index.php/posts/yii
/index.php/post/2/A+Test+Post
/index.php/post/update?id=1
```

---

## 11. Sidebar Only Appears on `column2`

Guide page:

```text
https://www.yiiframework.com/doc/blog/1.1/en/portlet.menu
```

If the sidebar does not appear, check which layout the page uses.

Files:

```text
protected/controllers/PostController.php
protected/controllers/CommentController.php
```

Add inside each controller:

```php
public $layout='//layouts/column2';
```

File:

```text
protected/components/Controller.php
```

Default layout can remain:

```php
public $layout='//layouts/column1';
```

Why:

The guide uses `column2` for blog pages and `column1` for normal pages like login, contact, and about.

---

## 12. UserMenu Only Appears When Logged In

Guide page:

```text
https://www.yiiframework.com/doc/blog/1.1/en/portlet.menu
```

File:

```text
protected/views/layouts/column2.php
```

This is correct:

```php
<?php if(!Yii::app()->user->isGuest) $this->widget('UserMenu'); ?>
```

If you are logged out, the UserMenu will not show. That is expected.

Logged out sidebar:

```text
Tags
Recent Comments
```

Logged in sidebar:

```text
UserMenu
Tags
Recent Comments
```

---

## 13. Quick Test After Applying Corrections

Open:

```text
http://localhost/www.sariblog.com/index.php
http://localhost/www.sariblog.com/index.php/post/index
http://localhost/www.sariblog.com/index.php/site/login
http://localhost/www.sariblog.com/index.php/post/create
http://localhost/www.sariblog.com/index.php/comment/index
```

Check logged-out sidebar:

```text
Tags
Recent Comments
```

Log in, then check sidebar:

```text
Create New Post
Manage Posts
Approve Comments
Logout
Tags
Recent Comments
```

---

## Common Error Map

### Error

```text
Property "Comment.pendingCommentCount" is not defined.
```

Fix:

```text
Add getPendingCommentCount() to protected/models/Comment.php
```

### Error

```text
Relation "commentCount" is not defined in active record class "Post".
```

Fix:

```text
Add commentCount relation to protected/models/Post.php
```

### Error

```text
TagCloud fails or findTagWeights is missing.
```

Fix:

```text
Add findTagWeights() to protected/models/Tag.php
```

### Error

```text
Undefined index: tagCloudCount
```

Fix:

```text
Add tagCloudCount to protected/config/main.php params
```

### Error

```text
Undefined index: recentCommentCount
```

Fix:

```text
Add recentCommentCount to protected/config/main.php params
```

### Error

```text
Undefined index: commentNeedApproval
```

Fix:

```text
Add commentNeedApproval to protected/config/main.php params
```
