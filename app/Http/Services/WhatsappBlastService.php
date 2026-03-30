<?php

namespace App\Services;

use Exception;

class WhatsappBlastService
{
    protected $appKey;
    protected $authKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->appKey = config('app.saungwa.app_key');
        $this->authKey = config('app.saungwa.auth_key');
        $this->baseUrl = config('app.saungwa.base_url');
    }

    public function send($to, $templateId, array $variables = [])
    {
        $postFields = [
            'appkey' => $this->appKey,
            'authkey' => $this->authKey,
            'to' => $to,
            'template_id' => $templateId,
        ];

        foreach ($variables as $key => $value) {
            $postFields["variables[$key]"] = $value;
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
        ]);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new Exception('cURL Error: ' . curl_error($curl));
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($httpCode === 200) {
            return 200;
        }

        throw new Exception("Request failed with status {$httpCode}. Response: {$response}");
    }
}