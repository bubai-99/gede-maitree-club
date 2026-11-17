// Razorpay Standard Checkout (https://checkout.razorpay.com/v1/checkout.js), opened from Alpine.
// The server created the order; the handler sends razorpay_payment_id, razorpay_order_id and
// razorpay_signature back to the server, which checks the signature before anything is marked paid.

function waitForRazorpay(timeout = 8000) {
    return new Promise((resolve) => {
        const started = Date.now();
        const tick = () => {
            if (window.Razorpay) {
                resolve(true);
            } else if (Date.now() - started > timeout) {
                resolve(false);
            } else {
                setTimeout(tick, 100);
            }
        };
        tick();
    });
}

export function razorpayCheckout({ options, verifyUrl, csrf, autoOpen, text }) {
    return {
        state: 'idle', // idle | opening | failed | dismissed | verifying | error
        message: '',
        detail: '',

        init() {
            if (autoOpen) {
                this.open();
            }
        },

        async open() {
            if (this.state === 'opening' || this.state === 'verifying') {
                return;
            }

            this.state = 'opening';
            this.message = '';
            this.detail = '';

            if (!(await waitForRazorpay())) {
                this.state = 'error';
                this.message = text.unavailable;
                return;
            }

            const checkout = new window.Razorpay({
                ...options,
                handler: (response) => this.verify(response),
                modal: {
                    ondismiss: () => {
                        if (this.state === 'opening') {
                            this.state = 'dismissed';
                            this.message = text.dismissed;
                        }
                    },
                },
            });

            checkout.on('payment.failed', (response) => {
                this.state = 'failed';
                this.message = text.failed;
                this.detail = response?.error?.description || '';
            });

            checkout.open();
        },

        async verify(response) {
            this.state = 'verifying';
            this.message = '';
            this.detail = '';

            try {
                const res = await fetch(verifyUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_signature: response.razorpay_signature,
                    }),
                });
                const data = await res.json().catch(() => ({}));

                if (res.ok && data.redirect) {
                    window.location.assign(data.redirect);
                    return;
                }

                this.state = 'error';
                this.message = data.message || text.verifyFailed;
            } catch {
                this.state = 'error';
                this.message = text.network;
            }
        },
    };
}
