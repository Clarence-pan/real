Tuniu定制的Yii插件
1. TEmailLogRoute.php
日志路由，将日志作为邮件发送到指定邮箱。只需要配置log router处，添加如下代码：
                array(
                    'class' => 'ext.phpmailer.TEmailLogRoute',
                    'levels' => 'error',
                    // 邮件标题
                    'subject' => '[网站错误报警]主站出现致命错误，服务器IP为: ' . $_SERVER['SERVER_ADDR'],
                    // 发件人邮箱地址
                    'sentFrom' => 'zhangjun@tuniu.com',
                    // 收件人邮箱地址
                    'emails' => array(
                        'zhangjun@tuniu.com'
                    ),
                    // 需要SMTP认证
                    'smtpAuth' => true,
                    // SMTP服务器地址
                    'host' => 'mail.tuniu.com',
                    // SMTP端口
                    'port' => 25,
                    // 对应发件人邮箱的用户
                    'username' => 'zhangjun',
                    'password' => 'tuniu520'
                ),

