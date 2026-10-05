# ReferralCandy for Magento 2

*This guide applies if you run your store on Magento Open Source or Adobe Commerce 2.4 and install the ReferralCandy module via Composer.*

Merchants on **Magento Open Source or Adobe Commerce 2.4.x** who are able to install modules via Composer can integrate ReferralCandy with their Magento store. You need command-line access to your Magento server; if you don't have it, ask your developer or hosting provider to run Step 1. If this procedure doesn't work for you, try [email integration](https://help.referralcandy.com/en/articles/2457827-other-platforms-email-integration-overview).

## Set up Magento integration

**Note**: The module tracks purchases on Magento's standard order success page. Third-party checkout extensions that replace that page may interfere with referral detection. It's recommended to use the standard Magento checkout process when integrating with ReferralCandy.

### Step 1: Server-side setup

To install the ReferralCandy module via Composer, run these commands from your Magento root directory:

1. Install the [referralcandy/magento2-module-integration](https://packagist.org/packages/referralcandy/magento2-module-integration) package from Composer:

   ```bash
   composer require referralcandy/magento2-module-integration
   ```

2. Enable the module using the magento-cli:

   ```bash
   bin/magento module:enable ReferralCandy_Integration
   ```

3. Perform a setup upgrade:

   ```bash
   bin/magento setup:upgrade
   ```

4. Run the code compiler for Magento:

   ```bash
   bin/magento setup:di:compile
   ```

5. If your store runs in production mode, deploy static content:

   ```bash
   bin/magento setup:static-content:deploy
   ```

6. Flush your store's cache (recommended by Magento after module installations):

   ```bash
   bin/magento cache:flush
   ```

**Already have an older version installed?** Update only the ReferralCandy package, then repeat steps 2 to 6. Your saved settings are kept.

```bash
composer update referralcandy/magento2-module-integration
```

### Step 2: Admin dashboard setup

After ReferralCandy installation via Composer, configure the module:

1. Log in to your Magento store's admin panel.

2. Navigate to **STORES** and click **Configuration**.

3. On the configuration page, a new section called **REFERRALCANDY** should be available. Navigate to **REFERRALCANDY** and click **General.**

4. On the ReferralCandy dashboard, go to **Account** > [Profile](https://my.referralcandy.com/account/profile) > **API Tokens** to find your **API Access ID** and **API Secret ID**. Copy both values.

5. Go back to the configuration page and paste the values in: your **API Access ID** goes into both the **APP ID** and **API Access ID** fields (they take the same value), and your **API Secret ID** goes into the **API Secret Key** field.

6. Set **Module Enable** to **Yes** and click **Save Config**.

7. Go to **SYSTEM** > **Cache Management** and click **Flush Magento Cache**.

If you ever reset your API secret in ReferralCandy, paste the new value into the **API Secret Key** field in Magento. Purchases are rejected until the two match.

### Step 3: Test the integration

Make a test purchase in your store to ensure that referral detection works. Go to your [Purchases & Referrals](https://my.referralcandy.com/purchases) page to verify if purchases are tracked successfully.

If the purchase doesn't appear, go to [Integrations > Standalone](https://my.referralcandy.com/integrations/standalone). Checksum errors there mean the **API Secret Key** in Magento doesn't match your **API Secret ID**. Copy it again from **Account** > **Profile**, save, and flush the cache.

## Good to know

- **Purchase amount**: ReferralCandy receives the order **subtotal**, which excludes tax, shipping and discounts.

- **Coupon rewards**: ReferralCandy can't create discount codes in Magento. Create them in Magento under **MARKETING** > **Cart Price Rules** (choose **Specific Coupon** and **Use Auto Generation**), generate and export the codes, then upload them to your ReferralCandy campaign as a coupon code list.

- **Removed advocates**: codes already issued are not disabled automatically. Deactivate them in Magento if needed.

## Uninstall

To remove the module, run:

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
