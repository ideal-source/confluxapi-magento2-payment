<?php

declare(strict_types=1);

namespace Conflux\Payment\Controller\Notify;

use Conflux\Payment\Model\Api\Client;
use Conflux\Payment\Model\Checkout;
use Conflux\Payment\Model\Direct;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;

class Index implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly RawFactory $rawFactory,
        private readonly Json $json,
        private readonly Client $client,
        private readonly OrderFactory $orderFactory,
        private readonly OrderRepositoryInterface $orderRepository
    ) {
    }

    public function execute(): Raw
    {
        $result = $this->rawFactory->create();
        $body = (string)$this->request->getContent();

        try {
            $notification = $this->json->unserialize($body);
        } catch (\InvalidArgumentException) {
            return $result->setHttpResponseCode(400)->setContents('invalid json');
        }

        if (!is_array($notification) || ($notification['notify_type'] ?? '') !== 'PAYMENT') {
            return $result->setContents('success');
        }

        $data = is_array($notification['data'] ?? null) ? $notification['data'] : [];
        $incrementId = (string)($data['mch_order_no'] ?? '');

        if ($incrementId === '') {
            return $result->setHttpResponseCode(400)->setContents('missing order');
        }

        $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
        if (!$order->getEntityId()) {
            return $result->setHttpResponseCode(404)->setContents('order not found');
        }

        $methodCode = (string)$order->getPayment()->getMethod();
        if (!in_array($methodCode, [Checkout::METHOD_CODE, Direct::METHOD_CODE], true)) {
            return $result->setHttpResponseCode(400)->setContents('invalid method');
        }

        if (!$this->client->verifyNotification($notification, $methodCode, (int)$order->getStoreId())) {
            return $result->setHttpResponseCode(401)->setContents('invalid sign');
        }

        $payment = $order->getPayment();
        $notifyId = (string)($notification['notify_id'] ?? '');
        $handledNotifyIds = (array)$payment->getAdditionalInformation('conflux_notify_ids');
        if ($notifyId !== '' && in_array($notifyId, $handledNotifyIds, true)) {
            return $result->setContents('success');
        }

        if ($notifyId !== '') {
            $handledNotifyIds[] = $notifyId;
            $payment->setAdditionalInformation('conflux_notify_ids', $handledNotifyIds);
        }

        if (!empty($data['flow_order_no'])) {
            $flowOrderNo = (string)$data['flow_order_no'];
            $payment->setTransactionId($flowOrderNo);
            $payment->setLastTransId($flowOrderNo);
        }
        $payment->setAdditionalInformation('conflux_last_notification', $notification);

        $tradeStatus = (string)($data['trade_status'] ?? '');
        if ($tradeStatus === 'SUCCESS') {
            $order->setState(Order::STATE_PROCESSING);
            $order->setStatus(Order::STATE_PROCESSING);
            $order->addCommentToStatusHistory(__('Conflux payment succeeded. Flow order: %1', $data['flow_order_no'] ?? ''));
        } elseif ($tradeStatus === 'FAILED') {
            if ($order->canCancel()) {
                $order->cancel();
            }
            $order->addCommentToStatusHistory(__('Conflux payment failed: %1', $data['fail_reason'] ?? ''));
        }

        $this->orderRepository->save($order);

        return $result->setContents('success');
    }

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
