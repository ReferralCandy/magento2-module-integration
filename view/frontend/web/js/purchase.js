/**
 * Loads the ReferralCandy purchase tracker for the order on the checkout success page.
 *
 * This used to be an inline <script> in order/success.phtml. `etc/csp_whitelist.xml`
 * whitelists `*.referralcandy.com` as a script-src HOST, which permits the script this
 * file injects but says nothing about the inline block that injects it — so on a store
 * running Magento's storefront CSP in `restrict` mode rather than the default
 * `report-only`, tracking stopped with no signal on either side. Served from the store's
 * own origin, this file needs no allowance at all.
 *
 * It reads its values from the `refcandy-mint` div's data attributes, so the template
 * stays the only place order data is rendered (and escaped).
 */
(function (document) {
    'use strict';

    var SCRIPT_TAG = 'script';
    var LOADER_ID = 'refcandy-purchase-js';
    var DIV_ID = 'refcandy-mint';
    var DEFAULT_PURCHASE_DOMAIN = 'go.referralcandy.com';
    var APP_ID_ATTRIBUTE = 'data-app-id';
    var PURCHASE_DOMAIN_ATTRIBUTE = 'data-purchase-domain';

    // Long data-attribute name to the short query key the purchase endpoint expects.
    var PARAMETER_KEYS = {
        email: 'a',
        fname: 'b',
        lname: 'c',
        amount: 'd',
        currency: 'e',
        'accepts-marketing': 'f',
        timestamp: 'g',
        'referral-code': 'h',
        locale: 'i',
        'external-reference-id': 'k',
        signature: 'ab'
    };

    function queryParameter(key, value) {
        return value ? key + '=' + encodeURIComponent(value) : '';
    }

    if (document.getElementById(LOADER_ID)) {
        return;
    }

    var div = document.getElementById(DIV_ID);
    if (!div) {
        return;
    }

    var firstScript = document.getElementsByTagName(SCRIPT_TAG)[0];
    if (!firstScript || !firstScript.parentNode) {
        return;
    }

    var parameters = [];
    for (var attribute in PARAMETER_KEYS) {
        if (!Object.prototype.hasOwnProperty.call(PARAMETER_KEYS, attribute)) {
            continue;
        }
        parameters.push(queryParameter(PARAMETER_KEYS[attribute], div.getAttribute('data-' + attribute)));
    }

    // Production unless the store has been pointed at a staging or local ReferralCandy.
    var domain = div.getAttribute(PURCHASE_DOMAIN_ATTRIBUTE) || DEFAULT_PURCHASE_DOMAIN;

    var loader = document.createElement(SCRIPT_TAG);
    loader.id = LOADER_ID;
    loader.src =
        '//' + domain + '/purchase/' + div.getAttribute(APP_ID_ATTRIBUTE) + '.js?aa=75&' + parameters.join('&');

    firstScript.parentNode.insertBefore(loader, firstScript);
})(document);
