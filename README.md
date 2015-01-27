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
        console.log('The file ' + file.name + ' was successfully uploaded with a response of ' + response + ':' + data);
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
            'format' => 'image/{yyyy}{mm}{dd}/{time}{rand:6}', //save format
            'validateOptions' => [
                'extensions' => ['jpg', 'png'],
                'maxSize' => 1 * 1024 * 1024, //file size
            ],
            'beforeValidate' => function($actionObject) {},
            'afterValidate' => function($actionObject) {},
            'beforeSave' => function($actionObject) {},
            'afterSave' => function($filename, $fullFilename, $actionObject) {
                //$filename; // image/yyyymmddtimerand.jpg
                //$fullFilename; // /var/www/htdocs/image/yyyymmddtimerand.jpg
                //$actionObject; // xj\uploadify\UploadAction instance
            },
        ],
    ];
}
```
