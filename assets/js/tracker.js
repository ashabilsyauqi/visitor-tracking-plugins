(function () {
    try {
        var cid = localStorage.getItem("wa_client_id");
        if (!cid) {
            cid = "cid_" + Math.random().toString(36).substring(2);
            localStorage.setItem("wa_client_id", cid);
        }

        var payload = {
            client_id: cid,
            page: window.location.href,
            referrer: document.referrer || ''
        };

        // Use sendBeacon if available, fallback to fetch
        var body = JSON.stringify(payload);
        if (navigator.sendBeacon) {
            var blob = new Blob([body], { type: 'application/json' });
            navigator.sendBeacon(WA_API.endpoint, blob);
        } else {
            fetch(WA_API.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: body,
                keepalive: true
            }).catch(function(){});
        }
    } catch (e) {
        // silent
    }
})();
