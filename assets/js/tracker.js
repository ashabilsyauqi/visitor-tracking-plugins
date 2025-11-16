(function () {
    let cid = localStorage.getItem("wa_client_id");
    if (!cid) {
        cid = "cid_" + Math.random().toString(36).substring(2);
        localStorage.setItem("wa_client_id", cid);
    }

    const payload = {
        client_id: cid,
        page: window.location.href,
        referrer: document.referrer
    };

    navigator.sendBeacon(
        WA_API.endpoint,
        JSON.stringify(payload)
    );
})();
