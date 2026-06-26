<?php

declare(strict_types=1);

namespace Conflux\Payment\Controller\Checkout;

use Conflux\Payment\Model\Checkout;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory;

class Cancel implements HttpGetActionInterface
{
    public function __construct(
        private readonly CheckoutSession $checkoutSession,
        private readonly RedirectFactory $redirectFactory
    ) {
    }

    public function execute(): Redirect
    {
        $resultRedirect = $this->redirectFactory->create();
        $order = $this->checkoutSession->getLastRealOrder();

        if (!$order || !$order->getEntityId()) {
            return $resultRedirect->setPath('checkout/cart');
        }

        if ((string)$order->getPayment()->getMethod() !== Checkout::METHOD_CODE) {
            return $resultRedirect->setPath('checkout/cart');
        }

        if ($this->checkoutSession->restoreQuote()) {
            return $resultRedirect->setPath('checkout', ['_fragment' => 'payment']);
        }

        return $resultRedirect->setPath('checkout/cart');
    }
}
