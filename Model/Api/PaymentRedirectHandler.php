<?php

declare(strict_types=1);

namespace Conflux\Payment\Model\Api;

use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class PaymentRedirectHandler
{
    public function __construct(
        private readonly RedirectFactory $redirectFactory,
        private readonly ManagerInterface $messageManager,
        private readonly OrderRepositoryInterface $orderRepository
    ) {
    }

    public function redirectFromResponse(OrderInterface $order, array $response): Redirect
    {
        $resultRedirect = $this->redirectFactory->create();
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $nextAction = is_array($data['next_action'] ?? null) ? $data['next_action'] : [];
        $redirectUrl = $this->getRedirectUrl($data, $nextAction, $response);

        $payment = $order->getPayment();
        $payment->setAdditionalInformation('conflux_response', $data);
        if (!empty($data['flow_order_no'])) {
            $payment->setTransactionId((string)$data['flow_order_no']);
        }
        $order->addCommentToStatusHistory(__('Conflux payment was initialized. Flow order: %1', $data['flow_order_no'] ?? ''));
        $this->orderRepository->save($order);

        if ($redirectUrl === '') {
            if (($data['trade_status'] ?? '') === 'SUCCESS') {
                $this->messageManager->addSuccessMessage(__('Conflux payment was completed successfully.'));
                return $resultRedirect->setPath('checkout/onepage/success');
            }

            $this->messageManager->addErrorMessage($this->getFailureMessage($data));
            return $resultRedirect->setPath('checkout/onepage/failure');
        }

        return $resultRedirect->setUrl($redirectUrl);
    }

    private function getRedirectUrl(array $data, array $nextAction, array $response): string
    {
        foreach ([
            $nextAction['url'] ?? null,
            $nextAction['qr_code'] ?? null,
            $data['checkout_url'] ?? null,
            $data['redirect_url'] ?? null,
            $response['checkout_url'] ?? null,
            $response['redirect_url'] ?? null,
        ] as $url) {
            if (!is_scalar($url)) {
                continue;
            }

            $url = trim((string)$url);
            if ($url !== '') {
                return $url;
            }
        }

        return '';
    }

    private function getFailureMessage(array $data): Phrase
    {
        $reason = $this->getFailureReason($data);

        if ($reason !== '') {
            return __('Your payment could not be completed. Please try another card or contact your bank. Reason: %1', $reason);
        }

        return __('Your payment could not be completed. Please try another card or contact your bank.');
    }

    private function getFailureReason(array $data): string
    {
        foreach (['fail_reason', 'message', 'error_message'] as $field) {
            if (!isset($data[$field]) || is_array($data[$field])) {
                continue;
            }

            $reason = trim((string)$data[$field]);
            if ($reason !== '') {
                return $reason;
            }
        }

        return '';
    }
}
