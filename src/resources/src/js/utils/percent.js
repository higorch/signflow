window.percent = function (value, options = {}) {
    const {
        locale = 'en-US',
        minimumFractionDigits = 2,
        maximumFractionDigits = 2,
        min = 0,
        max = 100,
        fallback = ''
    } = options;

    if (value === null || value === undefined || value === '') return fallback;

    let number = Number(typeof value === 'string' ? value.replace(/[^\d,.-]/g, '').replace(',', '.') : value);

    if (Number.isNaN(number)) return fallback;

    number = Math.min(Math.max(number, min), max);

    return new Intl.NumberFormat(locale, {
        style: 'percent',
        minimumFractionDigits,
        maximumFractionDigits
    }).format(number / 100);
};