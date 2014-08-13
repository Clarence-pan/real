<?php
require_once('vendors/class.phpmailer.php');

/**
 * Coypright (C) 2012 Tuniu All rights reserved
 * Author: zhangjun
 * Date: 2012-9-21
 * Description: 将日志发送到邮件的路由，使用phpmailer实现
 */
class TEmailLogRoute extends CLogRoute {

    /**
     * @var PHPMailer an instance of PHPMailer used to send email.
     */
    private $_mailer;

    /**
     * @var array list of destination email addresses.
     */
    private $_email = array();
    /**
     * @var string email sent from address
     */
    private $_from;

    /**
     * @var array list of additional headers to use when sending an email.
     */
    private $_headers = array();

    public function __construct() {
        $this->_mailer = new PHPMailer();
        $this->_mailer->IsSMTP();
        $this->_mailer->CharSet = "UTF-8";
    }

    public function init() {
        parent::init();
        Yii::setPathOfAlias('phpmailer', dirname(__FILE__));
        Yii::app()->setImport(array(
            'phpmailer.*',
            'phpmailer.vendors.*'
        ));
    }

    /**
     * Sends log messages to specified email addresses.
     * @param array $logs list of log messages
     */
    protected function processLogs($logs) {
        $this->_mailer->SetFrom($this->_from);
        $this->_mailer->MsgHTML($this->render('log', $logs));
        foreach ($this->getEmails() as $email) {
            $this->_mailer->AddAddress($email);
        }
        if (!$this->_mailer->Send()) {
            // TODO: 日志发邮件出错，无法使用Yii::log()将错误在写进日志中
            //var_dump($this->_mailer->ErrorInfo);
        }
    }

    /**
     * @return array list of destination email addresses
     */
    public function getEmails() {
        return $this->_email;
    }

    /**
     * @param mixed $value list of destination email addresses. If the value is
     * a string, it is assumed to be comma-separated email addresses.
     */
    public function setEmails($value) {
        if (is_array($value))
            $this->_email = $value;
        else
            $this->_email = preg_split('/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * @return string email subject. Defaults to CEmailLogRoute::DEFAULT_SUBJECT
     */
    public function getSubject() {
        return $this->_mailer->Subject;
    }

    /**
     * @param string $value email subject.
     */
    public function setSubject($value) {
        $this->_mailer->Subject = $value;
    }

    /**
     * @return string send from address of the email
     */
    public function getSentFrom() {
        return $this->_from;
    }

    /**
     * @param string $value send from address of the email
     */
    public function setSentFrom($value) {
        $this->_from = $value;
    }

    /**
     * @return array additional headers to use when sending an email.
     * @since 1.1.4
     */
    public function getHeaders() {
        return $this->_headers;
    }

    /**
     * @param mixed $value list of additional headers to use when sending an email.
     * If the value is a string, it is assumed to be line break separated headers.
     * @since 1.1.4
     */
    public function setHeaders($value) {
        if (is_array($value))
            $this->_headers = $value;
        else
            $this->_headers = preg_split('/\r\n|\n/', $value, -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * @param boolean $value enable or disable SMTP authentication.
     * Set the value true to enable SMTP authentication.
     */
    public function setSMTPAuth($value) {
        $this->_mailer->SMTPAuth = $value;
    }

    /**
     * @param string $host the SMTP server host.
     */
    public function setHost($host) {
        $this->_mailer->Host = $host;
    }

    /**
     * @param int $port the SMTP server port
     */
    public function setPort($port) {
        $this->_mailer->Port = $port;
    }

    /**
     * @param string $username the SMTP account username
     */
    public function setUsername($username) {
        $this->_mailer->Username = $username;
    }

    /**
     * @param string $password the SMTP account password
     */
    public function setPassword($password) {
        $this->_mailer->Password = $password;
    }

    /**
     * Renders the view.
     * @param string $view the view name (file name without extension). The file is assumed to be located under framework/data/views.
     * @param array $data data to be passed to the view
     */
    protected function render($view, $data) {
        $app = Yii::app();
        $viewFile = YII_PATH . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . $view . '.php';
        ob_start();
        ob_implicit_flush(false);
        include($app->findLocalizedFile($viewFile, 'en'));
        return ob_get_clean();
    }
}
