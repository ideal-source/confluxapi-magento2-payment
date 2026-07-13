# Conflux API Payment for Magento 2

[简体中文](README.zh_CN.md)

Conflux API Payment for Magento 2 provides two payment methods for Magento checkout:

- **Conflux Credit Card** (`conflux_direct`)
- **Conflux Hosted Checkout** (`conflux_checkout`)

The module creates Conflux payment requests from Magento orders, redirects customers when required, and handles Conflux payment notifications to update Magento order status.

## Requirements

- PHP `^8.1`
- Magento framework `^103.0`
- Magento 2 modules:
  - `Magento_Checkout`
  - `Magento_Config`
  - `Magento_Directory`
  - `Magento_Payment`
  - `Magento_Sales`
  - `Magento_Store`

## Installation

Install the module with Composer:

```bash
composer require confluxapi/magento2-payment
bin/magento module:enable Conflux_Payment
bin/magento setup:upgrade
bin/magento cache:flush
```

If your Magento instance is running in production mode, also run:

```bash
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
```

## Upgrade

If the module was installed with Composer, upgrade it with:

```bash
composer update confluxapi/magento2-payment
bin/magento setup:upgrade
bin/magento cache:flush
```

To upgrade to a specific version, use:

```bash
composer require confluxapi/magento2-payment:1.0.1
bin/magento setup:upgrade
bin/magento cache:flush
```

If your Magento instance is running in production mode, also run:

```bash
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
```

If the module was installed manually under `app/code/Conflux/Payment`, replace the module files with the new release files, then run:

```bash
bin/magento setup:upgrade
bin/magento cache:flush
```

## Manual Installation

If the package is not available from Packagist yet, place the module under:

```text
app/code/Conflux/Payment
```

Then enable it:

```bash
bin/magento module:enable Conflux_Payment
bin/magento setup:upgrade
bin/magento cache:flush
```

## Uninstall

To disable the module without removing the package, run:

```bash
bin/magento module:disable Conflux_Payment
bin/magento setup:upgrade
bin/magento cache:flush
```

If the module was installed with Composer and you want to remove it completely, run:

```bash
bin/magento module:disable Conflux_Payment
composer remove confluxapi/magento2-payment
bin/magento setup:upgrade
bin/magento cache:flush
```

If your Magento instance is running in production mode, also run:

```bash
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f
```

If the module was installed manually, disable it first, then delete:

```text
app/code/Conflux/Payment
```

## Configuration

In Magento Admin, go to:

```text
Stores > Configuration > Sales > Payment Methods
```

Configure one or both payment methods.

### Conflux Credit Card

Configuration group:

```text
Conflux Credit Card
```

Method code:

```text
conflux_direct
```

Available fields:

- **Enabled**: Enable or disable the direct credit card payment method.
- **Title**: Payment method title shown at checkout.
- **Payment Icons**: Optional multi-select list of payment method icons shown next to the payment method title at checkout, including card brands, wallets, and local payment methods.
- **New Order Status**: Initial Magento order status after order placement.
- **API Base URL**: Full Conflux API endpoint provided by Conflux.
- **App ID**: Conflux API application ID.
- **App Secret**: Conflux API secret. This value is stored encrypted by Magento.
- **Merchant Number**: Conflux merchant number.
- **Gateway Number**: Conflux gateway number.
- **Payment from Applicable Countries**: Allow all countries or only selected countries.
- **Payment from Specific Countries**: Country allowlist when specific countries are enabled.
- **Minimum Order Total**: Minimum order total allowed for this payment method.
- **Maximum Order Total**: Maximum order total allowed for this payment method.
- **Sort Order**: Display order in checkout.

### Conflux Hosted Checkout

Configuration group:

```text
Conflux Hosted Checkout
```

Method code:

```text
conflux_checkout
```

Available fields:

- **Enabled**: Enable or disable the hosted checkout payment method.
- **Title**: Payment method title shown at checkout.
- **Payment Icons**: Optional multi-select list of payment method icons shown next to the payment method title at checkout, including card brands, wallets, and local payment methods.
- **New Order Status**: Initial Magento order status after order placement.
- **API Base URL**: Full Conflux API endpoint provided by Conflux.
- **App ID**: Conflux API application ID.
- **App Secret**: Conflux API secret. This value is stored encrypted by Magento.
- **Merchant Number**: Conflux merchant number.
- **Gateway Number**: Conflux gateway number.
- **Checkout Expires In Seconds**: Hosted checkout expiration time. The module enforces a range from `7200` to `86400` seconds.
- **Payment from Applicable Countries**: Allow all countries or only selected countries.
- **Payment from Specific Countries**: Country allowlist when specific countries are enabled.
- **Minimum Order Total**: Minimum order total allowed for this payment method.
- **Maximum Order Total**: Maximum order total allowed for this payment method.
- **Sort Order**: Display order in checkout.

## Conflux URLs

The module sends these URLs to Conflux when creating payments.

### Notification URL

```text
https://your-domain.com/conflux/notify/index
```

Conflux should send payment notifications to this URL with the `POST` method.

### Hosted Checkout Cancel URL

```text
https://your-domain.com/conflux/checkout/cancel
```

Customers are returned to this URL when they cancel hosted checkout.

### Success URL

```text
https://your-domain.com/checkout/onepage/success
```

Customers are returned to Magento's standard checkout success page after payment flow completion.

## Payment Notification Handling

The notification controller accepts Conflux `PAYMENT` notifications.

The module uses the Magento order increment ID from:

```text
data.mch_order_no
```

Expected notification behavior:

- `trade_status = SUCCESS`: order state and status are set to `processing`.
- `trade_status = FAILED`: order is cancelled when Magento allows cancellation.
- Duplicate notifications with the same `notify_id` are ignored after the first successful handling.
- Invalid JSON, unknown orders, invalid payment methods, or invalid signatures return an error HTTP response.

## Testing

Use the sandbox API base URL first.

Recommended test flow:

1. Enable one payment method in Magento Admin.
2. Configure `API Base URL`, `App ID`, `App Secret`, `Merchant Number`, and `Gateway Number`.
3. Clear Magento cache.
4. Place a frontend order using the enabled Conflux payment method.
5. Confirm the customer is redirected or payment is started correctly.
6. Trigger a Conflux payment notification.
7. Confirm the Magento order moves to `processing` after a successful payment.

Useful Magento commands:

```bash
bin/magento module:status Conflux_Payment
bin/magento cache:flush
bin/magento setup:upgrade
```

## Troubleshooting

### Payment method is not shown at checkout

Check:

- The payment method is enabled in Magento Admin.
- Magento cache has been flushed.
- The order country is allowed by the payment method country settings.
- The order total is within the configured minimum and maximum order total range.

### API credentials error

Confirm these fields are configured for the selected payment method:

- App ID
- App Secret
- Merchant Number
- Gateway Number

Also confirm the configured API base URL matches the credentials:

- Sandbox API base URL
- Production API base URL

### Notification does not update the order

Check:

- Conflux can access `https://your-domain.com/conflux/notify/index`.
- The notification uses `POST`.
- `data.mch_order_no` matches the Magento order increment ID.
- The order payment method is `conflux_direct` or `conflux_checkout`.
- The notification signature is generated with the matching App Secret.

## Package Name

```text
confluxapi/magento2-payment
```

## Module Name

```text
Conflux_Payment
```

## License

MIT
