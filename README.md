yii2-uploadify-widget
=====================

composer.json
-----
```json
"require": {
    "xj/yii2-uploadify-widget": "*"
},
```

example:
-----
```php
//外部TAG
echo Html::fileInput('test', NULL, ['id' => 'test']);
echo Uploadify::widget([
    'url' => yii\helpers\Url::to(['s-upload']),
    'id' => 'test',
    'csrf' => true,
    'renderTag' => false,
    'jsOptions' => [
        'width' => 120,
        'height' => 40,
        'onUploadError' => "function(file, errorCode, errorMsg, errorString) {
            console.log('The file ' + file.name + ' could not be uploaded: ' + errorString + errorCode + errorMsg);
        }",
        'onUploadSuccess' => "function(file, data, response) {
            data = JSON.parse(data);
            if (data.error) {
                console.log(data.msg);
            } else {
                console.log(data.fileUrl);
            }
        }"
    ]
]);

//直接渲染
echo Html::activeLabel($model, 'file');
echo Uploadify::widget([
    'url' => yii\helpers\Url::to(['s-upload']),
    'attribute' => 'file',
    'model' => $model,
    'csrf' => true,
    'jsOptions' => [
        'width' => 120,
        'height' => 40,
        'onUploadError' => "function(file, errorCode, errorMsg, errorString) {
        console.log('The file ' + file.name + ' could not be uploaded: ' + errorString + errorCode + errorMsg);
    }",
        'onUploadSuccess' => "function(file, data, response) {
        console.log('The file ' + file.name + ' was successfully uploaded with a response of ' + response + ':' + data);
    }"
    ]
]);
```

Action:
----
```php
public function actions() {
    return [
        's-upload' => [
            'class' => \xj\uploadify\UploadAction::className(),
            'uploadBasePath' => '@webroot/upload', //file system path
            'uploadBaseUrl' => '@web/upload', //web path
            'csrf' => true,
//            'format' => 'image/{yyyy}{mm}{dd}/{time}{rand:6}', // OR Closure
            'format' => function(\xj\uploadify\UploadAction $action) {
                $fileext = $action->uploadFileInstance->getExtension();
                $filehash = sha1(uniqid() . time());
                $p1 = substr($filehash, 0, 2);
                $p2 = substr($filehash, 2, 2);
                return "{$p1}/{$p2}/{$filehash}.{$fileext}";
            },
            'validateOptions' => [
                'extensions' => ['jpg', 'png'],
                'maxSize' => 1 * 1024 * 1024, //file size
            ],
            'beforeValidate' => function($action) {
                /* @var $action xj\uploadify\UploadAction */
            },
            'afterValidate' => function($action) {
                /* @var $action xj\uploadify\UploadAction */
            },
            'beforeSave' => function($action) {
                /* @var $action xj\uploadify\UploadAction */
            },
            'afterSave' => function($action) {
                /* @var $action xj\uploadify\UploadAction */
                //$action->filename;  //   image/yyyymmdd/xxx.jpg
                //$action->$fullFilename //  /var/www/htdocs/image/yyyymmddtimerand.jpg
            },
        ],
    ];
}
```
