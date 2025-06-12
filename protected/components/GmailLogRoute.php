<?php
 
class GmailLogRoute extends CEmailLogRoute
{
    protected function sendEmail($email, $subject, $message)
    {
        Yii::app()->mailer->send($email, $subject, $message);
    }
}