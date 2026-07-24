import type {AppData, ImportItem, ImportSourceSelection, PowerItem, ProductionItem} from '../../Types/global';
import {computeConsumption} from './PowerService';

interface JQueryWindow extends Window {
    $?: JQueryStatic;
    appData?: AppData;
}

export interface SaveProductionLinePayload {
    importsTableRows: Array<{ itemId: number; quantity: number }>;
    productionTableRows: Array<{
        row_id: number;
        recipeId: number;
        quantity: number;
        Usage: number;
        exportPerMin: number;
        doubleExport: boolean;
        extraCells: { Usage: number; ExportPerMin: number; Product: string } | null;
        recipeSetting: { clockSpeed: number; useSomersloop: boolean };
    }>;
    powerTableRows: Array<{
        buildingId: number;
        quantity: number;
        clockSpeed: number;
        Consumption: number;
        userRow: boolean;
    }>;
    checklist: Array<{
        productionRow: { row_id: number | null };
        beenBuild: boolean;
        beenTested: boolean;
    }>;
    productLine: {
        title: string | null;
        active: number | null;
    };
    importSources: Array<{
        exportingProductionLineId: number;
        itemId: number;
        requestedAmount: number;
    }>;
    import_force_ids?: string[];
}

export interface SaveProductionLineResponse {
    success?: string | boolean;
    error?: string;
    data?: {
        newAndOldIds?: Array<{ old: string | number; new: string | number }>;
        importSources?: unknown[];
    };
    newAndOldIds?: Array<{ old: string | number; new: string | number }>;
    importSources?: unknown[];
}

const toNumber = (value: unknown): number => {
    const parsed = Number(value ?? 0);
    return Number.isFinite(parsed) ? parsed : 0;
};

const resolveGameSaveId = (appData: AppData | null): number => {
    const dom = document.getElementById('gameSaveId') as HTMLInputElement | null;
    const domValue = toNumber(dom?.value);
    if (domValue > 0) return domValue;

    const appValue = toNumber(appData?.productLine?.game_saves_id ?? (window as JQueryWindow).appData?.productLine?.game_saves_id);
    return appValue > 0 ? appValue : 0;
};

const resolveProductionLineId = (appData: AppData | null): number => {
    const appId = toNumber(appData?.productLine?.id);
    if (appId > 0) return appId;

    const el = document.getElementById('productionLineId') as HTMLInputElement | null;
    const domId = toNumber(el?.value);
    if (domId > 0) return domId;

    const url = new URL(window.location.href);
    return toNumber(url.searchParams.get('id'));
};

export function buildProductionLineSavePayload(
    appData: AppData | null,
    productionRows: ProductionItem[],
    powerRows: PowerItem[],
    importsList: ImportItem[],
    importForceIds?: Array<string | number>,
    importSources: ImportSourceSelection[] = []
): SaveProductionLinePayload {
    const activeImportItemIds = new Set((importsList || []).map((row) => toNumber(row.items_id)));
    const activeImportSources = (importSources || []).filter((source) => {
        const itemId = toNumber(source.itemId);
        const requestedAmount = toNumber(source.requestedAmount);
        return itemId > 0 && requestedAmount > 0 && activeImportItemIds.has(itemId);
    });

    const importsTableRows = (importsList || []).map((row) => ({
        itemId: toNumber(row.items_id),
        quantity: toNumber(row.ammount),
    }));

    const productionTableRows = (productionRows || []).map((row) => {
        const recipe = appData?.recipes?.find((candidate) => Number(candidate.id) === Number(row.recipe_id));
        const hasRecipeSecond = recipe?.item_id2 != null;
        const explicitSecond = !!row.item_name_2 || row.export_ammount_per_min2 != null || row.local_usage2 != null;
        const doubleExport = hasRecipeSecond || explicitSecond;
        const secondUsage = toNumber(row.local_usage2);
        const secondExport = toNumber(row.export_ammount_per_min2);

        return {
            row_id: row.id,
            recipeId: row.recipe_id ?? 0,
            quantity: toNumber(row.product_quantity),
            Usage: toNumber(row.local_usage),
            exportPerMin: toNumber(row.export_amount_per_min),
            doubleExport,
            extraCells: doubleExport ? {
                Usage: secondUsage,
                ExportPerMin: secondExport,
                Product: row.item_name_2 ?? recipe?.products?.[1]?.name ?? '',
            } : null,
            recipeSetting: {
                clockSpeed: row.clock_speed === '' || row.clock_speed == null ? 100 : toNumber(row.clock_speed),
                useSomersloop: !!row.use_somersloop,
            },
        };
    });

    const powerTableRows = (powerRows || []).map((row) => {
        const buildingId = toNumber(row.buildings_id);
        const quantity = toNumber(row.building_ammount);
        const clockSpeed = toNumber(row.clock_speed || 100);

        return {
            buildingId,
            quantity,
            clockSpeed,
            Consumption: computeConsumption({
                building_ammount: quantity,
                clock_speed: clockSpeed,
                power_used: row.power_used,
                buildings_id: buildingId,
            }, appData),
            userRow: !!row.user,
        };
    });

    const checklist = (appData?.checklist || []).map((item) => ({
        productionRow: {row_id: item.production_id ?? null},
        beenBuild: !!item.been_build,
        beenTested: !!item.been_tested,
    }));

    const payload: SaveProductionLinePayload = {
        importsTableRows,
        productionTableRows,
        powerTableRows,
        checklist,
        productLine: {
            title: appData?.productLine?.title || null,
            active: appData?.productLine?.active !== undefined ? toNumber(appData.productLine.active) : null,
        },
        importSources: activeImportSources.map((source) => ({
            exportingProductionLineId: toNumber(source.exportingProductionLineId),
            itemId: toNumber(source.itemId),
            requestedAmount: Math.max(0, toNumber(source.requestedAmount)),
        })),
    };

    if (importForceIds?.length) {
        payload.import_force_ids = importForceIds.map(String);
    }

    return payload;
}

export function postProductionLineSave(
    payload: SaveProductionLinePayload,
    productionLineId: number,
    gameSaveId: number
): Promise<SaveProductionLineResponse> {
    return new Promise((resolve, reject) => {
        const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
        const headers: Record<string, string> = {};
        if (meta?.content) headers['X-CSRF-Token'] = meta.content;

        try {
            (window as JQueryWindow).$?.ajax({
                type: 'POST',
                url: '/saveProductionLine',
                data: {
                    gameSaveId: String(gameSaveId),
                    data: JSON.stringify(payload),
                    id: String(productionLineId || 0),
                },
                headers,
                success: (response: unknown) => {
                    try {
                        resolve(typeof response === 'string' ? JSON.parse(response) : response as SaveProductionLineResponse);
                    } catch (error) {
                        reject(error);
                    }
                },
                error: (xhr: JQuery.jqXHR, status: string, error: string) => {
                    reject(new Error(xhr?.responseText || error || status));
                },
            });
        } catch (error) {
            reject(error);
        }
    });
}

export async function saveProductionLineData(
    appData: AppData | null,
    productionRows: ProductionItem[],
    powerRows: PowerItem[],
    importsList: ImportItem[],
    importForceIds?: Array<string | number>,
    importSources: ImportSourceSelection[] = []
): Promise<SaveProductionLineResponse> {
    const payload = buildProductionLineSavePayload(appData, productionRows, powerRows, importsList, importForceIds, importSources);
    return postProductionLineSave(payload, resolveProductionLineId(appData), resolveGameSaveId(appData));
}

export function showSaveMessage(success: boolean, message: string) {
    const type = success ? 'success' : 'danger';
    try {
        const ev = new CustomEvent('pl-alert', {detail: {type, message}});
        window.dispatchEvent(ev);
        return;
    } catch (e) {
        if (success) window.alert(message); else window.alert(`Error: ${message}`);
    }
}
