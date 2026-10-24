document.addEventListener('DOMContentLoaded', () => {
    const config = window.ComfyNestUPIPayment || {};
    const form = document.querySelector('[data-upi-payment-form]');
    const modal = document.querySelector('[data-upi-payment-modal]');
    if (!form || !modal) return;
    const title = modal.querySelector('[data-upi-modal-title]');
    const copy = modal.querySelector('[data-upi-modal-copy]');
    const mark = modal.querySelector('[data-upi-modal-mark]');
    const qrPanel = modal.querySelector('[data-upi-qr-panel]');
    const qrImage = modal.querySelector('[data-upi-qr-image]');
    const openAgain = modal.querySelector('[data-upi-open-again]');
    const complete = modal.querySelector('[data-upi-complete]');
    const cancel = modal.querySelector('[data-upi-cancel]');
    const reference = modal.querySelector('[data-upi-reference]');
    const submit = form.querySelector('button[type="submit"]');
    const mobile = /android|iphone|ipad|ipod/i.test(navigator.userAgent);
    let currentIntent = '';
    let attempt = null;
    let timer = null;
    let busy = false;
    let checking = false;
    let finished = false;
    let cancelTimer = null;
    let generation = 0;

    async function request(url, fields) {
        const controller = new AbortController();
        const timeout = window.setTimeout(() => controller.abort(), 12000);
        try {
            const response = await fetch(url, {
                method: 'POST', credentials: 'same-origin', signal: controller.signal,
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ csrf_token: config.csrf || '', ...fields })
            });
            const data = await response.json();
            if (!response.ok || !data.success) { const error = new Error(data.error || 'Unable to check payment.'); error.status = response.status; throw error; }
            return data;
        } finally { window.clearTimeout(timeout); }
    }
    function setOpen(open) {
        modal.hidden = !open;
        document.body.classList.toggle('upi-modal-open', open);
    }
    function stopChecks() {
        window.clearTimeout(timer);
        timer = null;
    }
    function finalise() {
        if (finished) return;
        finished = true;
        stopChecks();
        const field = document.createElement('input');
        field.type = 'hidden'; field.name = 'finalise'; field.value = '1';
        form.appendChild(field);
        form.submit(); // Server rechecks verified session and exact cart amount.
    }
    async function checkPayment() {
        if (!attempt || !attempt.tracked || finished) return;
        if (checking) { stopChecks(); timer = window.setTimeout(checkPayment, 2500); return; }
        stopChecks();
        checking = true;
        const active = generation;
        try {
            const result = await request(config.verifyUrl, { txnid: attempt.txnid });
            if (active !== generation) return;
            if (result.status === 'success') { finalise(); return; }
            if (result.status === 'failure') {
                attempt.tracked = false;
                stopChecks();
                window.location.replace(config.failureUrl || 'payment-failed.php');
                return;
            }
            if (copy) copy.textContent = result.message || 'Waiting for payment confirmation. Do not pay twice.';
        } catch (error) {
            if (active !== generation) return;
            if (error.status === 409 || error.status === 403) {
                attempt.tracked = false;
                if (copy) copy.textContent = error.message;
                return;
            }
            if (copy) copy.textContent = `${error.message} Retrying. Do not pay twice.`;
        } finally {
            checking = false;
            if (active === generation && !finished && attempt?.tracked) timer = window.setTimeout(checkPayment, 1000);
        }
    }
    function showPayment(method) {
        currentIntent = attempt.intents[method] || attempt.intents.upi;
        const names = { phonepe: 'PhonePe', paytm: 'Paytm', gpay: 'Google Pay', upi: 'your UPI app', qr_scan: 'QR payment' };
        const marks = { phonepe: 'पे', paytm: 'pay', gpay: 'G', upi: 'UPI', qr_scan: '▦' };
        const qrMode = method === 'qr_scan' || !mobile;
        if (title) title.textContent = qrMode ? `Pay ₹${Number(attempt.amount).toFixed(0)} by QR` : `Complete payment in ${names[method]}`;
        if (mark) mark.textContent = marks[method];
        if (reference) reference.textContent = attempt.txnid;
        if (qrPanel) qrPanel.hidden = !qrMode;
        if (openAgain) openAgain.hidden = qrMode;
        if (qrMode && qrImage) qrImage.src = `https://api.qrserver.com/v1/create-qr-code/?size=220x220&format=png&margin=10&data=${encodeURIComponent(attempt.intents.upi)}`;
        if (copy) copy.textContent = attempt.tracked
            ? 'Complete payment, then return here. Waiting for server confirmation.'
            : 'PhonePe opens normally, but this native flow has no automatic verification. Contact the store with your receipt after paying.';
        setOpen(true);
        window.clearTimeout(cancelTimer);
        if (cancel) cancel.hidden = true;
        cancelTimer = window.setTimeout(() => { if (cancel) cancel.hidden = false; }, 60000);
        if (attempt.tracked) timer = window.setTimeout(checkPayment, 2500);
        if (!qrMode) window.setTimeout(() => { window.location.href = currentIntent; }, 50);
    }
    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (busy || finished) return;
        const method = form.querySelector('input[name="payment_method"]:checked')?.value;
        if (!method || !config.ready) return;
        busy = true;
        if (submit) submit.disabled = true;
        stopChecks();
        generation += 1;
        try {
            const effectiveMethod = !mobile ? 'qr_scan' : method;
            attempt = await request(config.initiateUrl, { payment_method: effectiveMethod });
            showPayment(effectiveMethod);
        } catch (error) { window.alert(error.message); }
        finally { busy = false; if (submit) submit.disabled = false; }
    });
    if (config.ready) {
        busy = true;
        if (submit) submit.disabled = true;
        const initialMethod = !mobile ? 'qr_scan' : (form.querySelector('input[name="payment_method"]:checked')?.value || 'paytm');
        request(config.initiateUrl, { payment_method: initialMethod })
            .then(data => { attempt = data; if (reference) reference.textContent = data.txnid; })
            .catch(error => { if (copy) copy.textContent = error.message; })
            .finally(() => { busy = false; if (submit) submit.disabled = false; });
    }
    cancel?.addEventListener('click', () => {
        stopChecks();
        generation += 1;
        if (attempt) attempt.tracked = false;
        window.clearTimeout(cancelTimer);
        setOpen(false);
    });
    modal.querySelectorAll('[data-upi-close]').forEach(button => button.addEventListener('click', () => setOpen(false)));
    openAgain?.addEventListener('click', () => { if (currentIntent) window.location.href = currentIntent; });
    complete?.addEventListener('click', () => {
        if (!attempt) return;
        if (!attempt.tracked) {
            if (copy) copy.textContent = 'Automatic confirmation is unavailable for this PhonePe native payment. Contact the store with your receipt; do not pay again.';
            return;
        }
        window.clearTimeout(cancelTimer);
        if (cancel) cancel.hidden = true;
        cancelTimer = window.setTimeout(() => { if (cancel) cancel.hidden = false; }, 60000);
        checkPayment();
    });
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden && attempt?.tracked && !finished) checkPayment();
    });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && !modal.hidden) setOpen(false); });
});
