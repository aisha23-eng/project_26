export function formatCurrency(amount: number | string | null | undefined, currency = 'SAR'): string {
    const value = Number(amount ?? 0);
    return new Intl.NumberFormat('ar-SA-u-nu-latn', { style: 'currency', currency, maximumFractionDigits: 2 }).format(value);
}
