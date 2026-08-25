/**
 * Renders a decimal string from the API the way the mockups do: `120$`, `5$`.
 * Trailing zero cents are dropped so the grid stays scannable.
 */
export const formatMoney = (
    amount: string | number | null | undefined,
): string => {
    if (amount === null || amount === undefined) {
        return '—';
    }

    const value =
        typeof amount === 'string' ? Number.parseFloat(amount) : amount;

    if (Number.isNaN(value)) {
        return '—';
    }

    const formatted = Number.isInteger(value)
        ? value.toLocaleString()
        : value.toLocaleString(undefined, {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
          });

    return `${formatted}$`;
};

/**
 * The amount a quick-bid chip should submit: the floor plus an increment, as a
 * decimal string the server can compare without a float round-trip.
 */
export const raiseBy = (
    base: string | number | null | undefined,
    increment: number,
): string => {
    const floor = typeof base === 'string' ? Number.parseFloat(base) : base;

    return ((floor ?? 0) + increment).toFixed(2);
};
