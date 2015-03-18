<?php

namespace xj\uploadify;

use Yii;
use yii\base\Action;
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
class UploadAction extends Action {

    /**
     * The length of the CSRF token mask.
     */
    const CSRF_MASK_LENGTH = 8;

    /**
     * save path
     * @var string 
     */
    public $uploadBasePath = '@webroot/upload';

    /**
     * web url
     * @var string 
     */
    public $uploadBaseUrl = '@web/upload';

    /**
     * Csrf Verify Enable
     * @var bool
     */
    public $csrf = true;

    /**
     *  $this->output['fileUrl'] = $this->uploadBaseUrl . '/' . $this->filename;
     * @var bool
     */
    public $autoOutput = true;

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
     * @var string | Closure
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
    public $uploadFileInstance;

    /**
     * saved format filename
     * image/yyyymmdd/xxx.jpg
     * @var string 
     */
    public $filename;

    /**
     * saved format filename full path
     * /var/www/htdocs/image/yyyymmdd/xxx.jpg
     * @var string
     */
    public $fullFilename;

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

    /**
     * output
     * @var []
     */
    public $output = ['error' => false];

    public function init() {
        //csrf
        $this->initCsrf();

        //upload instance
        $this->uploadFileInstance = UploadedFile::getInstanceByName('Filedata');

        //upload base path
        if (empty($this->uploadBasePath)) {
            throw new Exception('uploadBasePath not exist');
        }
        $this->uploadBasePath = Yii::getAlias($this->uploadBasePath);
        //upload web url
        if (!empty($this->uploadBaseUrl)) {
            $this->uploadBaseUrl = Yii::getAlias($this->uploadBaseUrl);
        }
        return parent::init();
    }

    public function run() {
        try {
            if ($this->uploadFileInstance === null) {
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
            //auto output
            if (true === $this->autoOutput) {
                $this->processOutput();
            }
            if ($this->afterSave !== null) {
                call_user_func($this->afterSave, $this);
            }
        } catch (Exception $e) {
            $this->output['error'] = true;
            $this->output['msg'] = $e->getMessage();
        }
        Yii::$app->response->format = 'json';
        return $this->output;
    }

    private function save() {
        $filename = $this->getSaveFileNameWithNotExist();
        $basePath = $this->uploadBasePath;
        $fullFilename = $basePath . '/' . $filename;
        $dirPath = dirname($fullFilename);
        if (false === is_dir($dirPath)) {
            if (false === mkdir($dirPath, 0755, true)) {
                throw new Exception('mkdir fail: ' . $dirPath);
            }
        }
        $result = $this->uploadFileInstance->saveAs($fullFilename);
        if (!$result) {
            throw new Exception('save file fail');
        }

        $this->filename = $filename;
        $this->fullFilename = $fullFilename;
    }

    /**
     * output fileUrl
     */
    private function processOutput() {
        $this->output['fileUrl'] = $this->uploadBaseUrl . '/' . $this->filename;
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
        if (is_callable($this->format) || is_array($this->format)) {
            return call_user_func($this->format, $this);
        }
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

        $srcName = mb_substr($this->uploadFileInstance->name, 0, mb_strpos($this->uploadFileInstance->name, '.'));
        $srcName = preg_replace("/[\|\?\"\<\>\/\*\\\\]+/", '', $srcName);
        $format = str_replace("{filename}", $srcName, $format);

        //替换随机字符串
        $randNum = rand(1, 10000000000) . rand(1, 10000000000);
        $matches = [];
        if (preg_match("/\{rand\:([\d]*)\}/i", $format, $matches)) {
            $randNumLength = substr($randNum, 0, $matches[1]);
            $format = preg_replace("/\{rand\:[\d]*\}/i", $randNumLength, $format);
        }

        $ext = $this->uploadFileInstance->getExtension();
        return $format . '.' . $ext;
    }

    /**
     * validate upload file
     * @throws Exception
     */
    private function validate() {
        $file = $this->uploadFileInstance;
        $error = [];
        $validator = new FileValidator($this->validateOptions);
        if (!$validator->validate($file, $error)) {
            throw new Exception($error);
        }
    }

    private function initCsrf() {
        //verify csrf in session
        if (false === $this->csrf) {
            Yii::$app->request->enableCsrfValidation = false;
            return;
        }
        Yii::$app->request->enableCsrfValidation = false;
        Yii::$app->request->enableCsrfCookie = false;

        $session = Yii::$app->session;
        $request = Yii::$app->request;
//        $csrfTokenName = $request->csrfParam;
        $session->open();
        $sessionName = $session->getName();
        $postSessionId = $request->post($sessionName);
        $currentSessionId = $session->getId();
        if ($currentSessionId != $postSessionId) {
            $session->destroy();
        }
        $session->setId($postSessionId);
        $session->open();
        $request->enableCsrfValidation = true;
        $request->validateCsrfToken();
    }

}
