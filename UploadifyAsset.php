<?php

namespace xj\uploadify;

use yii\web\AssetBundle;

class UploadifyAsset extends AssetBundle {

    public $sourcePath;
    public $basePath = '@webroot/assets';
    public $publishOptions = ['forceCopy' => YII_DEBUG];
    public $css = ['uploadify.css'];
    public $js = [];
    public $depends = ['yii\web\JqueryAsset'];

    private function getJs() {
        return [
            YII_DEBUG ? 'jquery.uploadify.js' : 'jquery.uploadify.min.js',
        ];
    }

    public function init() {
        $this->sourcePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'assets';
        if (empty($this->js)) {
            $this->js = $this->getJs();
        }
        return parent::init();
    }

}
