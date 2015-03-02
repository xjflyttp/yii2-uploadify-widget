<?php

namespace xj\uploadify;

use Yii;
use yii\helpers\Json;
use yii\helpers\Html;
use yii\web\View;
use yii\helpers\ArrayHelper;
use yii\web\JsExpression;
use yii\widgets\InputWidget;
use xj\uploadify\UploadifyAsset;

/**
 * Uploadify Widget
 *
 */
class Uploadify extends InputWidget {

    /**
     * upload file to URL
     * @var string 
     * @example
     * http://xxxxx/upload.php
     * ['article/upload']
     * ['upload']
     */
    public $url;

    /**
     * enable csrf verify
     * @var bool 
     */
    public $csrf = true;

    /**
     * 是否渲染Tag
     * @var bool
     */
    public $renderTag = true;

    /**
     * uploadify js options
     * @var array
     * @example 
     * [
     * 'height' => 30,
     * 'width' => 120,
     * 'swf' => '/uploadify/uploadify.swf',
     * 'uploader' => '/uploadify/uploadify.php',
     * ]
     * @see http://www.uploadify.com/documentation/
     */
    public $jsOptions = [];

    /**
     * uploadify javascript event list
     * @var []
     * @see http://www.uploadify.com/documentation/
     */
    public $events = [
        'onCancel', 'onClearQueue', 'onDestroy', 'onDialogClose', 'onDialogOpen',
        'onDisable', 'onEnable', 'onFallback', 'onInit', 'onQueueComplete',
        'onSelect', 'onSelectError', 'onSWFReady', 'onUploadComplete',
        'onUploadError', 'onUploadProgress', 'onUploadStart', 'onUploadSuccess',
    ];

    /**
     * Initializes the widget.
     */
    public function init() {

        Yii::$app->request->enableCsrfValidation = $this->csrf;
        if ($this->csrf) {
            Yii::$app->request->enableCsrfCookie = false;
        }

        //init var
        if (empty($this->url)) {
            $this->url = \yii\helpers\Url::to('index');
        }
        if (empty($this->id)) {
            $this->id = $this->hasModel() ? Html::getInputId($this->model, $this->attribute) : $this->getId();
        }
        $this->options['id'] = $this->id;
        if (empty($this->name)) {
            $this->name = $this->hasModel() ? Html::getInputName($this->model, $this->attribute) : $this->id;
        }

        //register js css
        $assets = UploadifyAsset::register($this->view);

        //init options
        $this->initUploadifyOptions($assets);

        parent::init();
    }

    /**
     * Renders the widget.
     */
    public function run() {
        $this->registerScripts();
        if ($this->renderTag === true) {
            echo $this->renderTag();
        }
    }

    /**
     * init Uploadify options
     * @param [] $assets
     * @return void
     */
    private function initUploadifyOptions($assets) {
        $baseUrl = $assets->baseUrl;

        $this->jsOptions['uploader'] = $this->url;
        $this->jsOptions['swf'] = $baseUrl . '/uploadify.swf';

        //csrf options
        if ($this->csrf) {
            $this->initUploadifyCsrfOption($this->jsOptions);
        }

        /**
         * JsExpression convert
         */
        foreach ($this->jsOptions as $key => $val) {
            if (in_array($key, $this->events) && !($val instanceof JsExpression)) {
                $this->jsOptions[$key] = new JsExpression($val);
            }
        }
    }

    /**
     * uploadify csrf options
     * 
     * @param type $jsOptions
     * @return void
     */
    private function initUploadifyCsrfOption(&$jsOptions) {
        $request = Yii::$app->request;
        $csrfName = $request->csrfParam;
        $csrfValue = $request->csrfToken;

        $session = Yii::$app->session;
        $session->open();
        $sessionIdName = $session->getName();
        $sessionIdValue = $session->getId();
        $jsOptions['formData'] = [
            $sessionIdName => $sessionIdValue,
            $csrfName => $csrfValue,
        ];
    }

    /**
     * render file input tag
     * @return string
     */
    private function renderTag() {
        return Html::fileInput($this->name, null, $this->options);
    }

    /**
     * register script
     */
    private function registerScripts() {
        $jsonOptions = Json::encode($this->jsOptions);
        $script = <<<EOF
\$('#{$this->id}').uploadify({$jsonOptions});
EOF;
        $this->view->registerJs($script, View::POS_LOAD);
    }

}
