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
            data = JSON.parse(data);
            if (data.error) {
                console.log(data.msg);
            } else {
                console.log(data.fileUrl);
            }
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
            'autoOutput' => true, //output fileUrl
            'format' => 'image/{yyyy}{mm}{dd}/{time}{rand:6}', //save format
            'validateOptions' => [
                'extensions' => ['jpg', 'png'],
                'maxSize' => 1 * 1024 * 1024, //file size
            ],
            'beforeValidate' => function($actionObject) {},
            'afterValidate' => function($actionObject) {},
            'beforeSave' => function($actionObject) {},
            'afterSave' => function($actionObject) {
                /* @var $actionObject xj\uploadify\UploadAction */

                $filename = $actionObject->saveFilename;
                /* @var $filename string 'image/yyyymmddtimerand.jpg' */

                $fullFilename = $actionObject->saveFullFilename;
                /* @var $fullFilename string '/var/www/htdocs/image/yyyymmddtimerand.jpg' */
            },
        ],
    ];
}
```
