export function formatNumber(value: any): string {
    const n = Number(value ?? 0);
    if (Number.isNaN(n)) return String(value ?? '');
    if (n % 1 === 0) return n.toFixed(0);
    const rounded = Math.round(n * 100000) / 100000;
    return rounded.toFixed(5).replace(/0+$/, '').replace(/\.$/, '');
}

export function clamp(value: number, min: number, max: number): number {
    return Math.max(min, Math.min(max, value));
}
