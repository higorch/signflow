window.currency = function (value, options = {}) {
    const {
        locale = 'en-US',     // ex: pt-BR, en-US, de-DE, fr-FR
        currency = 'USD',     // ex: BRL, EUR, GBP, JPY
        fallback = '',
        minimumFractionDigits,
        maximumFractionDigits
    } = options;

    if (value === null || value === undefined || value === '') return fallback;

    const number = Number(typeof value === 'string' ? value.replace(/[^\d.-]/g, '') : value);

    if (Number.isNaN(number)) return fallback;

    return new Intl.NumberFormat(locale, {
        style: 'currency',
        currency,
        minimumFractionDigits,
        maximumFractionDigits
    }).format(number);
};
