<?php

namespace xj\uploadify;

use Yii;
use yii\helpers\Json;
use yii\validators\FileValidator;
use yii\web\UploadedFile;
use yii\base\Exception;

/**
 * Uploadify Widget Action
 * @author xjflyttp <xjflyttp@gmail.com>
 * @example
    Html::fileInput('test', NULL, ['id' => 'test']);
    Uploadify::widget([
        'url' => yii\helpers\Url::to(['s-upload']),
        'id' => 'test',
        'model' => $model,
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
 */
class UploadAction extends \yii\base\Action {

    /**
     * save path
     * @var string 
     */
    public $uploadBasePath = '@frontend/web/upload';

    /**
     * web url
     * @var string 
     */
    public $uploadBaseUrl = '/web/upload';

    /**
     * Csrf Verify Enable
     * @var bool
     */
    public $csrf = true;

    /**
     *
      {filename} 会替换成原文件名,配置这项需要注意中文乱码问题
      {rand:6} 会替换成随机数,后面的数字是随机数的位数
      {time} 会替换成时间戳
      {yyyy} 会替换成四位年份
      {yy} 会替换成两位年份
      {mm} 会替换成两位月份
      {dd} 会替换成两位日期
      {hh} 会替换成两位小时
      {ii} 会替换成两位分钟
      {ss} 会替换成两位秒
      非法字符 \ : * ? " < > |
     * @var string
     */
    public $format = '{yyyy}{mm}{dd}/{time}{rand:6}';

    /**
     * file validator options
     * @var []
     * @see http://stuff.cebe.cc/yii2docs/yii-validators-filevalidator.html
     * @example
     * [
     * 'maxSize' => 1000,
     * 'extensions' => ['jpg', 'png']
     * ]
     */
    public $validateOptions = [];

    /**
     * file instance
     * @var UploadedFile
     */
    private $_uploadFileInstance;

    /**
     * saved format filename
     * image/yyyymmdd/xxx.jpg
     * @var string 
     */
    private $_filename;

    /**
     * saved format filename full path
     * /var/www/htdocs/image/yyyymmdd/xxx.jpg
     * @var string
     */
    private $_fullFilename;

    /**
     * throw yii\base\Exception will break
     * @var Closure
     * beforeValidate($UploadAction)
     */
    public $beforeValidate;

    /**
     * throw yii\base\Exception will break
     * @var Closure
     * afterValidate($UploadAction)
     */
    public $afterValidate;

    /**
     * throw yii\base\Exception will break
     * @var Closure
     * beforeSave($UploadAction)
     */
    public $beforeSave;

    /**
     * throw yii\base\Exception will break
     * @var Closure
     * afterSave($filename, $fullFilename, $UploadAction)
     */
    public $afterSave;

    public function init() {
        //csrf状态
        Yii::$app->request->enableCsrfValidation = false;

        //verify csrf in session
        if ($this->csrf && !$this->verifyCsrf()) {
            throw new \yii\web\BadRequestHttpException('csrf verify fail.');
        }

        //upload instance
        $this->_uploadFileInstance = UploadedFile::getInstanceByName('Filedata');

        //upload base path
        $this->uploadBasePath = Yii::getAlias($this->uploadBasePath);

        return parent::init();
    }

    public function run() {
        try {
            if ($this->_uploadFileInstance === null) {
                throw new Exception('upload not exist');
            }
            if ($this->beforeValidate !== null) {
                call_user_func($this->beforeValidate, $this);
            }
            $this->validate();
            if ($this->afterValidate !== null) {
                call_user_func($this->afterValidate, $this);
            }
            if ($this->beforeSave !== null) {
                call_user_func($this->beforeSave, $this);
            }
            $this->save();
            if ($this->afterSave !== null) {
                call_user_func($this->afterSave, $this->_filename, $this->_fullFilename, $this);
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function save() {
        $filename = $this->getSaveFileNameWithNotExist();
        $basePath = $this->uploadBasePath;
        $fullFilename = $basePath . DIRECTORY_SEPARATOR . $filename;
        $result = $this->_uploadFileInstance->saveAs($fullFilename);
        if (!$result) {
            throw new Exception('save file fail');
        }

        $this->_filename = $filename;
        $this->_fullFilename = $fullFilename;
    }

    /**
     * 取得没有碰撞的FileName
     */
    private function getSaveFileNameWithNotExist() {
        $retryCount = 10;
        $currentCount = 0;
        $basePath = $this->uploadBasePath;
        $filename = '';
        do {
            ++$currentCount;
            $filename = $this->getSaveFileName();
            $filepath = $basePath . DIRECTORY_SEPARATOR . $filename;
        } while ($currentCount < $retryCount && file_exists($filepath));
        if ($currentCount == $retryCount) {
            throw new Exception('file exist dump of ' . $currentCount . ' times');
        }
        return $filename;
    }

    /**
     * convert format property to string
     * @return string
     */
    private function getSaveFileName() {
        //替换日期事件
        $t = time();
        $d = explode('-', date("Y-y-m-d-H-i-s"));
        $format = $this->format;
        $format = str_replace("{yyyy}", $d[0], $format);
        $format = str_replace("{yy}", $d[1], $format);
        $format = str_replace("{mm}", $d[2], $format);
        $format = str_replace("{dd}", $d[3], $format);
        $format = str_replace("{hh}", $d[4], $format);
        $format = str_replace("{ii}", $d[5], $format);
        $format = str_replace("{ss}", $d[6], $format);
        $format = str_replace("{time}", $t, $format);

        $srcName = mb_substr($this->_uploadFileInstance->name, 0, mb_strpos($this->_uploadFileInstance->name, '.'));
        $srcName = preg_replace("/[\|\?\"\<\>\/\*\\\\]+/", '', $srcName);
        $format = str_replace("{filename}", $srcName, $format);

        //替换随机字符串
        $randNum = rand(1, 10000000000) . rand(1, 10000000000);
        $matches = [];
        if (preg_match("/\{rand\:([\d]*)\}/i", $format, $matches)) {
            $randNumLength = substr($randNum, 0, $matches[1]);
            $format = preg_replace("/\{rand\:[\d]*\}/i", $randNumLength, $format);
        }

        $ext = $this->_uploadFileInstance->getExtension();
        return $format . '.' . $ext;
    }

    /**
     * validate upload file
     * @throws Exception
     */
    private function validate() {
        $file = $this->_uploadFileInstance;
        $error = [];
        $validator = new FileValidator($this->validateOptions);
        if (!$validator->validate($file, $error)) {
            throw new Exception($error);
        }
    }

    /**
     * verify csrf token
     * @return boolean
     */
    private function verifyCsrf() {
        $session = Yii::$app->session;
        $sessionIdName = $session->getName();

        $request = Yii::$app->request;
        $csrfName = $request->csrfParam;

        $postSessionIdValue = $request->post($sessionIdName);
        $postCsrfValue = $request->post($csrfName);
        if ($postCsrfValue === null || $postSessionIdValue === null) {
            return false;
        }

        $session->setId($postSessionIdValue);
        $session->open();
        $trueCsrfValue = $session->get($csrfName);

        return $trueCsrfValue === $postCsrfValue ? true : false;
    }

}
