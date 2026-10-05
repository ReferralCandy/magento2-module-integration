# ReferralCandy for Magento 2

Connects a Magento Open Source or Adobe Commerce store to [ReferralCandy](https://www.referralcandy.com).
After each completed checkout, the order success page sends the purchase to ReferralCandy, which
enrolls the customer as an advocate and credits the friend who referred them.

## Requirements

- Magento Open Source or Adobe Commerce 2.4.x, on a PHP version that release supports
- Command-line access to the Magento root, with Composer
- A ReferralCandy account

## Installation

From the Magento root:

```bash
composer require referralcandy/magento2-module-integration
bin/magento module:enable ReferralCandy_Integration
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

In production mode, also run `bin/magento setup:static-content:deploy` before flushing the cache.

## Configuration

1. In ReferralCandy, open **Account > Profile** and find the **API Tokens** section. It shows
   your **API Access ID** and **API Secret ID**.
2. In the Magento Admin, go to **Stores > Configuration > ReferralCandy > General**.
3. Under **General Configuration**, fill in:
   - **APP ID**: your API Access ID
   - **API Access ID**: your API Access ID (the same value)
   - **API Secret Key**: your API Secret ID. Magento stores it encrypted.
4. Set **Module Enable** to **Yes** and click **Save Config**.
5. Flush the cache: **System > Cache Management > Flush Magento Cache**.

If you reset your API secret in ReferralCandy, paste the new value into **API Secret Key**.
Purchases are rejected until the two match.

## Verify the connection

Place a test order and complete checkout. If the purchase does not reach ReferralCandy, open
**Integrations > Standalone** in ReferralCandy: it lists checksum errors, which mean the API Secret
Key in Magento does not match your API Secret ID.

## What is tracked

- One purchase per completed order, sent from the checkout success page
- The customer's name and email, the order number, currency and locale
- The order **subtotal**, which excludes tax, shipping and discounts

## Rewards

ReferralCandy cannot create discount codes in Magento. For coupon rewards, create the codes in
Magento (**Marketing > Cart Price Rules**, with **Use Auto Generation** under a specific coupon),
export them, and upload them to ReferralCandy as a coupon code list. Issued codes are not disabled automatically if an advocate
is later removed from the program.

## Uninstall

```bash
bin/magento module:disable ReferralCandy_Integration
composer remove referralcandy/magento2-module-integration
bin/magento setup:upgrade
bin/magento cache:flush
```

## Support

Email [support@referralcandy.com](mailto:support@referralcandy.com).

## License

MIT. See [LICENSE](LICENSE).
