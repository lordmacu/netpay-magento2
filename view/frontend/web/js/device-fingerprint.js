define([], function () {
    'use strict';

    /**
     * Port of NetPay's WooCommerce plugin `netpay_devicefingerprint.js` (doProfile).
     *
     * Generates a random session id, fires the ThreatMetrix device-profiling tags
     * (a <script> plus a hidden <iframe>) and returns the session id. The same id is
     * later sent to NetPay on the charge as `deviceFingerPrint` and `sessionId`, so the
     * gateway (CyberSource Decision Manager) can correlate the collected device data.
     * The value itself is only a correlation id; ThreatMetrix gathers the actual device
     * profile out-of-band, keyed by (org_id, session_id).
     *
     * @param {String} orgId - ThreatMetrix org id provided by NetPay (test/prod).
     * @return {String} the generated session id.
     */
    return function doProfile(orgId) {
        var length = 30,
            chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!',
            base = 'https://h.online-metrix.net/fp/tags',
            sessionId = '',
            i,
            script,
            iframe;

        for (i = length; i > 0; --i) {
            sessionId += chars[Math.floor(Math.random() * chars.length)];
        }

        script = document.createElement('script');
        script.type = 'text/javascript';
        script.src = base + '.js?org_id=' + orgId + '&session_id=' + sessionId;
        document.getElementsByTagName('head')[0].appendChild(script);

        iframe = document.createElement('iframe');
        iframe.setAttribute('id', 'iframeTM');
        iframe.style.width = '100px';
        iframe.style.height = '100px';
        iframe.style.border = '0';
        iframe.style.position = 'absolute';
        iframe.style.top = '-5000px';
        iframe.src = base + '?org_id=' + orgId + '&session_id=' + sessionId;
        document.body.appendChild(iframe);

        return sessionId;
    };
});
