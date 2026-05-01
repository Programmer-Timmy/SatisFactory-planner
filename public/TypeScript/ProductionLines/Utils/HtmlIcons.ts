let itemClassMap: Record<string, string> | null = null; // allow null during lazy init

function loadItemClassMap(): Record<string, string> {
    if (itemClassMap !== null) return itemClassMap;

    try {
        const el = document.getElementById('items-class-map');
        if (el) {
            const json = el.textContent?.trim() || '{}';
            itemClassMap = JSON.parse(json);
        } else if (typeof window !== 'undefined' && (window as any).appData && (window as any).appData.itemClassMap) {
            itemClassMap = (window as any).appData.itemClassMap || {};
        } else {
            itemClassMap = {};
        }
    } catch {
        itemClassMap = {};
    }

    return itemClassMap as Record<string, string>;
}

function normalizeItemClassName(className: string): string {
    return className.toLowerCase().replaceAll('_', '-');
}

function getItemIconSrc(itemId: number | null | undefined): string | null {
    if (!itemId) return null;
    const map = loadItemClassMap();
    const className = map[itemId.toString()];
    if (!className) return null;
    const safeClassName = className.replace(/[^a-zA-Z0-9_-]/g, '');
    if (!safeClassName) return null;
    return `/image/items/${normalizeItemClassName(safeClassName)}_256.png`;
}

export function getItemIconSrcForId(itemId: number | null | undefined): string | null {
    return getItemIconSrc(itemId);
}
