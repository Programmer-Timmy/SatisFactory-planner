import { computeConsumption } from './PowerService';

interface AppDataLike {
    checklist?: any[] | null;
    productLine?: any;
    production?: any[];
    powers?: any[];
    imports?: any[];
}

function localSaveData(data: Record<string, any>, id: number): Promise<Record<string, any>> {
    // determine gameSaveId from DOM or window.appData
    let gameSaveId = 0;
    try {
        const dom = document.getElementById('gameSaveId') as HTMLInputElement | null;
        const v = dom && dom.value ? Number(dom.value) : NaN;
        if (Number.isFinite(v) && v > 0) gameSaveId = v;
    } catch (e) {}
    if (!gameSaveId) {
        try {
            const fromApp = (window as any)?.appData?.productLine?.game_saves_id;
            const parsed = Number(fromApp);
            if (Number.isFinite(parsed) && parsed > 0) gameSaveId = parsed;
        } catch (e) {
        }
    }

    return new Promise((resolve, reject) => {
        const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null;
        const headers: Record<string,string> = {};
        if (meta?.content) headers['X-CSRF-Token'] = meta.content;

        // Use jQuery.ajax to perform an AJAX form POST compatible with existing backend expectations
        try {
            (window as any).$?.ajax({
                type: 'POST',
                url: '/saveProductionLine',
                data: {
                    gameSaveId: String(gameSaveId),
                    data: JSON.stringify(data),
                    id: String(id || 0)
                },
                headers,
                success: function(response: any) {
                    try {
                        // some servers return JSON string, some already parsed
                        const parsed = typeof response === 'string' ? JSON.parse(response) : response;
                        resolve(parsed);
                    } catch (e) {
                        reject(e);
                    }
                },
                error: function(xhr: any, status: any, error: any) {
                    const text = xhr && xhr.responseText ? xhr.responseText : String(error || status);
                    reject(new Error(text));
                }
            });
        } catch (err) {
            reject(err);
        }
    });
}

/**
 * Prepare payload and call server to save a production line.
 * Returns the raw server response (parsed JSON) or throws on network error.
 */
export async function saveProductionLineData(
    appData: AppDataLike | null,
    productionRows: any[],
    powerRows: any[],
    importsList: any[],
    importForceIds?: Array<string|number>
): Promise<Record<string, any>> {
    // Map importsList -> legacy importsTableRows (itemId, quantity)
    const importsTableRows = (importsList || []).map((r: any) => ({
        itemId: r.items_id ?? r.itemId ?? 0,
        quantity: r.ammount ?? r.amount ?? 0,
    }));

    // Map productionRows -> legacy productionTableRows
    const productionTableRows = (productionRows || []).map((r: any) => {
        // Detect if the recipe has a second product (byproduct) using appData.recipes if available
        let recipeObj: any = null;
        try {
            recipeObj = (appData as any)?.recipes?.find((x: any) => Number(x.id) === Number(r.recipe_id)) || null;
        } catch (e) { recipeObj = null; }

        const hasRecipeSecond = !!(recipeObj && (recipeObj.item_id2 || recipeObj.item_id2 === 0));

        const explicitSecond = (r.item_name_2 && r.item_name_2 !== '') || (r.export_ammount_per_min2 != null) || (r.local_usage2 != null) || (r.export_amount_per_min2 != null);
        const doubleExport = hasRecipeSecond || explicitSecond;

        const secondUsage = r.local_usage2 ?? r.localUsage2 ?? (r.extraCells?.Usage) ?? 0;
        const secondExport = r.export_ammount_per_min2 ?? r.export_amount_per_min2 ?? r.export_ammount_per_min2 ?? (r.extraCells?.ExportPerMin) ?? 0;

        const extraCells: Record<string, any> | null = doubleExport ? {
            Usage: secondUsage,
            ExportPerMin: secondExport,
            Product: r.item_name_2 ?? recipeObj?.secondItemName ?? recipeObj?.item_name_2 ?? ''
        } : null;

        return {
            row_id: r.id,
            recipeId: r.recipe_id ?? 0,
            quantity: r.product_quantity ?? r.product_quantity ?? r.quantity ?? 0,
            Usage: r.local_usage ?? r.localUsage ?? r.Usage ?? 0,
            exportPerMin: r.export_amount_per_min ?? r.exportPerMin ?? r.exportPerMin ?? 0,
            doubleExport: doubleExport,
            extraCells,
            recipeSetting: {
                clockSpeed: (r.clock_speed === '' || r.clock_speed === undefined || r.clock_speed === null) ? 100 : Number(r.clock_speed),
                useSomersloop: !!r.use_somersloop
            }
        };
    });

    // Map powerRows -> legacy powerTableRows
    const powerTableRows = (powerRows || []).map((r: any) => {
        const buildingId = r.buildings_id ?? r.buildingId ?? 0;
        const quantity = r.building_ammount ?? r.quantity ?? 0;
        const clockSpeed = r.clock_speed ?? r.clockSpeed ?? 100;
        // compute total consumption for this row (uses computeConsumption from PowerService)
        const computedConsumption = (() => {
            try {
                return computeConsumption({ building_ammount: quantity, clock_speed: clockSpeed, power_used: r.power_used ?? undefined, buildings_id: buildingId }, (window as any).appData || appData);
            } catch (e) {
                return Number(r.power_used ?? r.Consumption ?? 0) * quantity; // fallback
            }
        })();

        return {
            buildingId,
            quantity,
            clockSpeed,
            Consumption: computedConsumption,
            userRow: !!r.user
        };
    });

    // Map checklist from appData (if present) into legacy shape expected by server
    const checklist = (appData?.checklist || []).map((c: any) => ({
        productionRow: { row_id: c.production_id ?? c.productionRow?.row_id ?? null },
        beenBuild: !!(c.been_build ?? c.beenBuild ?? false),
        beenTested: !!(c.been_tested ?? c.beenTested ?? false),
    }));

    const payload: Record<string, any> = {
        importsTableRows,
        productionTableRows,
        powerTableRows,
        checklist,
        productLine: {
            title: (appData as any)?.productLine?.title || null,
            active: (appData as any)?.productLine?.active !== undefined ? Number((appData as any).productLine.active) : null
        }
    };

    if (importForceIds && importForceIds.length) {
        payload.import_force_ids = importForceIds.map(String);
    }

    // Determine production line id. Prefer appData.productLine.id, then DOM hidden #productionLineId, then URL param
    const url = new URL(window.location.href);
    const idFromUrl = Number(url.searchParams.get('id')) || 0;

    let id = 0;
    if (appData?.productLine?.id && !Number.isNaN(Number(appData.productLine.id)) && Number(appData.productLine.id) > 0) {
        id = Number(appData.productLine.id);
    } else {
        const el = document.getElementById('productionLineId') as HTMLInputElement | null;
        if (el && el.value) {
            const asNum = Number(el.value);
            if (!Number.isNaN(asNum) && asNum > 0) id = asNum;
            else id = idFromUrl;
        } else {
            id = idFromUrl;
        }
    }

    const response = await localSaveData(payload, id);
    return response;
}

export function showSaveMessage(success: boolean, message: string) {
    const type = success ? 'success' : 'danger';
    try {
        const ev = new CustomEvent('pl-alert', { detail: { type, message } });
        window.dispatchEvent(ev);
        return;
    } catch (e) {
        // fallback to alert
        if (success) window.alert(message); else window.alert(`Error: ${message}`);
    }
}
