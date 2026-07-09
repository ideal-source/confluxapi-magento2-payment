<?php

declare(strict_types=1);

namespace Conflux\Payment\Model;

use Conflux\Payment\Block\Info\Direct as DirectInfoBlock;
use Magento\Payment\Model\Method\AbstractMethod;

class Checkout extends AbstractMethod
{
    public const METHOD_CODE = 'conflux_checkout';

    /**
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * @var string
     */
    protected $_infoBlockType = DirectInfoBlock::class;

    /**
     * @var bool
     */
    protected $_isOffline = false;

    /**
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * @var bool
     */
    protected $_canCapture = true;

}
