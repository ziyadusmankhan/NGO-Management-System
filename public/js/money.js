/* Decimal text at the boundary; integer paisa for browser previews. */
window.FoundationMoney = Object.freeze({
    minor(value) {
        const text = String(value ?? '').trim();
        if (!/^-?\d{1,12}(?:\.\d{1,2})?$/.test(text)) return 0n;
        const negative = text.startsWith('-');
        const [whole, fraction = ''] = text.replace(/^-/, '').split('.');
        const minor = BigInt(whole) * 100n + BigInt(fraction.padEnd(2, '0'));
        return negative ? -minor : minor;
    },
    formatMinor(value) {
        const negative = value < 0n;
        const digits = (negative ? -value : value).toString().padStart(3, '0');
        const whole = digits.slice(0, -2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        return `${negative ? '-' : ''}${whole}.${digits.slice(-2)}`;
    },
    format(value) {
        return this.formatMinor(this.minor(value));
    },
});
