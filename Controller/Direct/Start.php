<?php

declare(strict_types=1);

namespace Conflux\Payment\Controller\Direct;

use Conflux\Payment\Model\Api\PaymentRedirectHandler;
use Conflux\Payment\Model\Direct;
use Conflux\Payment\Model\Direct\PaymentStarter;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;

class Start implements HttpGetActionInterface
{
    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly RedirectFactory $redirectFactory,
        private readonly ManagerInterface $messageManager,
        private readonly PaymentStarter $paymentStarter,
        private readonly PaymentRedirectHandler $redirectHandler
    ) {
    }

    public function execute(): Redirect
    {
        $resultRedirect = $this->redirectFactory->create();
        $order = $this->checkoutSession->getLastRealOrder();

        if (!$order || !$order->getEntityId()) {
            $this->messageManager->addErrorMessage(__('We could not find the order for Conflux direct payment.'));
            return $resultRedirect->setPath('checkout/cart');
        }

        if ($order->getPayment()->getMethod() !== Direct::METHOD_CODE) {
            $this->messageManager->addErrorMessage(__('The order is not using Conflux direct payment.'));
            return $resultRedirect->setPath('checkout/onepage/failure');
        }

        try {
            $response = $this->paymentStarter->start($order);
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            return $resultRedirect->setPath('checkout/onepage/failure');
        }

        return $this->redirectHandler->redirectFromResponse($order, $response);
    }
}
