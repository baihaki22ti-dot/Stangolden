<?php

namespace App\Services;

use SendinBlue\Client\Api\TransactionalEmailsApi;
use SendinBlue\Client\Configuration;
use SendinBlue\Client\Model\SendSmtpEmail;

class BrevoMailService
{
    protected $apiInstance;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('api-key', env('BREVO_API_KEY'));

        $this->apiInstance = new TransactionalEmailsApi(null, $config);
    }

    public function send($to, $subject, $html)
    {
        $email = new SendSmtpEmail([
            'subject' => $subject,
            'sender' => [
                'email' => env('MAIL_FROM_ADDRESS'),
                'name'  => env('MAIL_FROM_NAME')
            ],
            'to' => [[ 'email' => $to ]],
            'htmlContent' => $html
        ]);

        return $this->apiInstance->sendTransacEmail($email);
    }
}
