<?php

class GmailMailer extends CApplicationComponent
{
    public $username;
    public $password;
    public $from;

    protected $mailer;

    public function init()
    {
        $transport = new Swift_SmtpTransport('smtp.gmail.com', 587, 'tls');
        $transport->setUsername($this->username);
        $transport->setPassword($this->password);

        $this->mailer = new Swift_Mailer($transport);
    }

    public function send($to, $subject, $body)
    {
        $message = new Swift_Message($subject);
        $message->setFrom([$this->from => 'Yii Logs']);
        $message->setTo($to);
        $message->setBody($body, 'text/html');

        return $this->mailer->send($message);
    }
}
