<?php

declare(strict_types=1);

namespace Conflux\Payment\Model\Api;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\ScopeInterface;

class Client
{
    public function __construct(
        private readonly Curl $curl,
        private readonly Json $json,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly EncryptorInterface $encryptor
    ) {
    }

    public function createCheckout(array $payload, ?int $storeId = null): array
    {
        return $this->post('/api/v1/checkout/create', $payload, 'conflux_checkout', $storeId);
    }

    public function createPayment(array $payload, ?int $storeId = null): array
    {
        return $this->post('/api/v1/pay', $payload, 'conflux_direct', $storeId);
    }

    public function verifyNotification(array $notification, string $methodCode, ?int $storeId = null): bool
    {
        $timestamp = isset($notification['timestamp']) ? (string)$notification['timestamp'] : '';
        $nonce = isset($notification['nonce']) ? (string)$notification['nonce'] : '';
        $dataRaw = isset($notification['data_raw']) ? (string)$notification['data_raw'] : '';
        $sign = isset($notification['sign']) ? (string)$notification['sign'] : '';

        if ($timestamp === '' || $nonce === '' || $dataRaw === '' || $sign === '') {
            return false;
        }

        $expected = hash_hmac(
            'sha256',
            $timestamp . $nonce . $dataRaw,
            $this->getAppSecret($methodCode, $storeId)
        );

        return hash_equals($expected, strtolower($sign));
    }

    private function post(string $path, array $payload, string $methodCode, ?int $storeId): array
    {
        $body = $this->json->serialize($payload);
        $appId = $this->getConfig($methodCode, 'app_id', $storeId);
        $appSecret = $this->getAppSecret($methodCode, $storeId);

        if ($appId === '' || $appSecret === '') {
            throw new LocalizedException(__('Conflux App ID or App Secret is not configured.'));
        }

        $timestamp = (string)round(microtime(true) * 1000);
        $nonce = bin2hex(random_bytes(16));
        $signature = base64_encode(hash_hmac('sha256', $appId . $timestamp . $nonce . $body, $appSecret, true));

        $this->curl->setHeaders([
            'Content-Type' => 'application/json',
            'X-App-Id' => $appId,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
            'X-Signature' => $signature,
        ]);
        $this->curl->post($this->getBaseUrl($methodCode, $storeId) . $path, $body);

        $responseBody = (string)$this->curl->getBody();
        $status = (int)$this->curl->getStatus();
        $response = $responseBody !== '' ? $this->json->unserialize($responseBody) : [];

        if ($status < 200 || $status >= 300) {
            throw new LocalizedException(__('Conflux API HTTP error: %1', $status));
        }

        if (!is_array($response) || empty($response['success']) || ($response['code'] ?? '') !== '0000') {
            throw new LocalizedException(__(
                'Conflux API error: %1',
                is_array($response) ? $this->getErrorMessage($response) : 'Invalid response'
            ));
        }

        return $response;
    }

    private function getErrorMessage(array $response): string
    {
        $message = $response['message'] ?? '';
        if (!is_scalar($message)) {
            $message = '';
        }

        $message = trim((string)$message);
        $code = isset($response['code']) && is_scalar($response['code']) ? trim((string)$response['code']) : '';

        if ($message === '') {
            return $code !== '' ? $code : 'Unknown error';
        }

        return $code !== '' && $code !== '0000' ? $code . ': ' . $message : $message;
    }

    private function getBaseUrl(string $methodCode, ?int $storeId): string
    {
        $baseUrl = rtrim($this->getConfig($methodCode, 'environment', $storeId), '/');

        if ($baseUrl === '' || !preg_match('/^https?:\/\//', $baseUrl)) {
            throw new LocalizedException(__('Conflux API base URL is not configured correctly.'));
        }

        return $baseUrl;
    }

    private function getAppSecret(string $methodCode, ?int $storeId): string
    {
        $value = $this->getConfig($methodCode, 'app_secret', $storeId);

        return $value !== '' ? (string)$this->encryptor->decrypt($value) : '';
    }

    private function getConfig(string $methodCode, string $field, ?int $storeId): string
    {
        return (string)$this->scopeConfig->getValue(
            'payment/' . $methodCode . '/' . $field,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}
