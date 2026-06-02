// Loads the PagBank card-encryption SDK on demand and encrypts a card
// client-side (the raw PAN never touches our server — keeps PCI scope minimal).

const SDK_URL = 'https://assets.pagseguro.com.br/checkout-sdk-js/rc/dist/browser/pagseguro.min.js';

let sdkPromise = null;

export function loadPagBankSdk() {
    if (typeof window !== 'undefined' && window.PagSeguro) {
        return Promise.resolve(window.PagSeguro);
    }
    if (sdkPromise) return sdkPromise;

    sdkPromise = new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = SDK_URL;
        script.async = true;
        script.onload = () => resolve(window.PagSeguro);
        script.onerror = () => {
            sdkPromise = null;
            reject(new Error('Falha ao carregar o SDK de pagamento.'));
        };
        document.head.appendChild(script);
    });

    return sdkPromise;
}

/**
 * @returns {Promise<{encrypted:string|null, errors:Array}>}
 */
export async function encryptCard(publicKey, { holder, number, expMonth, expYear, securityCode }) {
    const PagSeguro = await loadPagBankSdk();

    const result = PagSeguro.encryptCard({
        publicKey,
        holder,
        number: (number || '').replace(/\s+/g, ''),
        expMonth,
        expYear,
        securityCode,
    });

    if (result.hasErrors) {
        return { encrypted: null, errors: result.errors || [{ message: 'Dados do cartão inválidos.' }] };
    }

    return { encrypted: result.encryptedCard, errors: [] };
}

// Compute installment options. Installments up to `interestFree` are shown
// without added interest; we only gate by a minimum installment value.
export function installmentOptions(totalCents, max, minInstallmentCents) {
    const options = [];
    for (let n = 1; n <= max; n++) {
        const per = Math.floor(totalCents / n);
        if (n > 1 && minInstallmentCents && per < minInstallmentCents) break;
        options.push({
            n,
            per,
            label: `${n}x de R$ ${(per / 100).toFixed(2).replace('.', ',')}${n === 1 ? ' à vista' : ' sem juros'}`,
        });
    }
    return options;
}
