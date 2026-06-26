# Conflux API Payment for Magento 2

[English](README.md)

Conflux API Payment for Magento 2 为 Magento 结账流程提供两种支付方式：

- **Conflux 信用卡支付** (`conflux_direct`)
- **Conflux 托管收银台** (`conflux_checkout`)

该模块会根据 Magento 订单创建 Conflux 支付请求，在需要时跳转客户到支付页面，并处理 Conflux 支付通知以更新 Magento 订单状态。

## 环境要求

- PHP `^8.1`
- Magento framework `^103.0`
- Magento 2 模块：
  - `Magento_Checkout`
  - `Magento_Config`
  - `Magento_Directory`
  - `Magento_Payment`
  - `Magento_Sales`
  - `Magento_Store`

## 安装

使用 Composer 安装模块：

```bash
composer require confluxapi/magento2-payment
bin/magento module:enable Conflux_Payment
bin/magento setup:upgrade
bin/magento cache:flush
```

如果 Magento 运行在生产模式，还需要执行：

```bash
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
```

## 手动安装

如果包暂未发布到 Packagist，可以将模块放到以下目录：

```text
app/code/Conflux/Payment
```

然后启用模块：

```bash
bin/magento module:enable Conflux_Payment
bin/magento setup:upgrade
bin/magento cache:flush
```

## 配置

在 Magento 后台进入：

```text
Stores > Configuration > Sales > Payment Methods
```

可以配置以下一个或两个支付方式。

### Conflux 信用卡支付

配置分组：

```text
Conflux Credit Card
```

支付方式代码：

```text
conflux_direct
```

可配置字段：

- **启用**：启用或禁用信用卡直连支付方式。
- **标题**：结账页展示的支付方式标题。
- **新订单状态**：下单后的 Magento 初始订单状态。
- **API 基础地址**：Conflux 提供的完整 API 接口地址。
- **App ID**：Conflux API 应用 ID。
- **App Secret**：Conflux API 密钥，该值会由 Magento 加密保存。
- **商户号**：Conflux 商户号。
- **网关号**：Conflux 网关号。
- **适用国家**：允许全部国家或仅允许指定国家。
- **指定国家**：启用指定国家时的国家白名单。
- **最小订单金额**：该支付方式允许的最小订单金额。
- **最大订单金额**：该支付方式允许的最大订单金额。
- **排序**：结账页支付方式显示顺序。

### Conflux 托管收银台

配置分组：

```text
Conflux Hosted Checkout
```

支付方式代码：

```text
conflux_checkout
```

可配置字段：

- **启用**：启用或禁用托管收银台支付方式。
- **标题**：结账页展示的支付方式标题。
- **新订单状态**：下单后的 Magento 初始订单状态。
- **API 基础地址**：Conflux 提供的完整 API 接口地址。
- **App ID**：Conflux API 应用 ID。
- **App Secret**：Conflux API 密钥，该值会由 Magento 加密保存。
- **商户号**：Conflux 商户号。
- **网关号**：Conflux 网关号。
- **收银台过期时间（秒）**：托管收银台过期时间。模块会限制该值在 `7200` 到 `86400` 秒之间。
- **适用国家**：允许全部国家或仅允许指定国家。
- **指定国家**：启用指定国家时的国家白名单。
- **最小订单金额**：该支付方式允许的最小订单金额。
- **最大订单金额**：该支付方式允许的最大订单金额。
- **排序**：结账页支付方式显示顺序。

## Conflux 回调地址

模块创建支付时会向 Conflux 传递以下 URL。

### 支付通知地址

```text
https://your-domain.com/conflux/notify/index
```

Conflux 应使用 `POST` 方法向该地址发送支付通知。

### 托管收银台取消地址

```text
https://your-domain.com/conflux/checkout/cancel
```

客户在托管收银台取消支付后会返回该地址。

### 支付成功返回地址

```text
https://your-domain.com/checkout/onepage/success
```

支付流程完成后，客户会返回 Magento 标准结账成功页面。

## 支付通知处理

通知控制器接收 Conflux 的 `PAYMENT` 类型通知。

模块会从以下字段读取 Magento 订单增量编号：

```text
data.mch_order_no
```

通知处理行为：

- `trade_status = SUCCESS`：订单状态会被设置为 `processing`。
- `trade_status = FAILED`：如果 Magento 允许取消订单，则取消该订单。
- 相同 `notify_id` 的重复通知会在首次成功处理后被忽略。
- JSON 无效、订单不存在、支付方式不匹配或签名无效时会返回错误 HTTP 响应。

## 测试

建议先使用 sandbox API 基础地址测试。

推荐测试流程：

1. 在 Magento 后台启用一个支付方式。
2. 配置 `API Base URL`、`App ID`、`App Secret`、`Merchant Number` 和 `Gateway Number`。
3. 清理 Magento 缓存。
4. 在前台使用已启用的 Conflux 支付方式下单。
5. 确认客户跳转或支付发起流程正常。
6. 触发 Conflux 支付通知。
7. 确认支付成功后 Magento 订单进入 `processing` 状态。

常用 Magento 命令：

```bash
bin/magento module:status Conflux_Payment
bin/magento cache:flush
bin/magento setup:upgrade
```

## 故障排查

### 结账页不显示支付方式

检查：

- 支付方式已在 Magento 后台启用。
- Magento 缓存已清理。
- 订单国家符合支付方式的国家限制。
- 订单金额在配置的最小和最大金额范围内。

### API 凭证错误

确认当前支付方式已配置以下字段：

- App ID
- App Secret
- Merchant Number / 商户号
- Gateway Number / 网关号

同时确认配置的 API 基础地址与凭证匹配：

- Sandbox API 基础地址
- Production API 基础地址

### 支付通知未更新订单

检查：

- Conflux 可以访问 `https://your-domain.com/conflux/notify/index`。
- 通知请求使用 `POST` 方法。
- `data.mch_order_no` 与 Magento 订单增量编号一致。
- 订单支付方式为 `conflux_direct` 或 `conflux_checkout`。
- 通知签名使用匹配的 App Secret 生成。

## 包名

```text
confluxapi/magento2-payment
```

## 模块名

```text
Conflux_Payment
```

## 许可证

MIT
