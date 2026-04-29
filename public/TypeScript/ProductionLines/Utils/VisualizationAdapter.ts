import { Visualization } from "../../Tables/Classes/Visualization";
import { Import as ProductionImport } from "../../Tables/Classes/Data/Import";

export function createVisualizationFromData(appData: any, productionRows: any[], importsList: any[], recipeMap: Record<number, any>, options?: { onProgress?: (pct: number) => void }) {
    const rows = productionRows || [];
    const n = rows.length;

    // set building to = recipe.building[0]
    recipeMap = Object.fromEntries(
        Object.entries(recipeMap).map(([key, rec]) => {
            if (!rec) return [key, null];

            const buildingInfo = rec.building?.[0] || null;

            const foundBuilding =
                appData.buildings.find((b: any) => b.class_name === (buildingInfo?.class_name || "")) ||
                appData.buildings.find((b: any) => b.id === (rec.buildings_id || 0)) ||
                null;

            return [
                key,
                {
                    ...rec,
                    building: foundBuilding,
                },
            ];
        })
    ) as Record<number, any>;

    // Precompute producer metadata similar to ProductionService
    const producers = rows.map((p: any) => {
        const rec = recipeMap[p.recipe_id] ?? null;
        const primaryName = rec && rec.products && rec.products[0] ? rec.products[0].name : p.item_name_1 || '';
        const secondName = rec && rec.products && rec.products[1] ? rec.products[1].name : p.item_name_2 || '';
        const exportPerMin = rec?.export_amount_per_min ?? 0;
        const exportPerMin2 = rec?.export_amount_per_min2 ?? 0;
        const productQty = Number(p.product_quantity) || 0;
        const extraQty = (exportPerMin2 && exportPerMin) ? productQty * (exportPerMin2 / exportPerMin) : 0;
        return { rec, primaryNameLower: primaryName.toLowerCase(), secondNameLower: secondName.toLowerCase(), exportPerMin, exportPerMin2, productQty, extraQty };
    });

    const usageArr = new Array(n).fill(0);
    const extraUsageArr = new Array(n).fill(0);

    // Build productionTableRows and populate productionImports by consuming from producers
    const productionTableRows: any[] = rows.map((r: any, i: number) => {
        const recipe = recipeMap[r.recipe_id] || null;
        const qty = Number(r.product_quantity) || Number(r.quantity) || 0;
        const usage = Number(r.Usage ?? r.local_usage ?? r.localUsage ?? 0) || 0;
        const extraCells: any = r.extraCells || {};

        // compute exportPerMin similar to ProductionLineFunctions.calculateProductionExport
        let exportPerMin = Number(r.exportPerMin ?? r.export_amount_per_min ?? 0);
        if (!exportPerMin) {
            exportPerMin = Math.max(0, qty - usage);
        }

        // handle double export if recipe provides second export info
        if (recipe && recipe.export_amount_per_min2 && recipe.export_amount_per_min) {
            const exportPerMin2 = recipe.export_amount_per_min2;
            const exportPerMinMain = recipe.export_amount_per_min;
            const secondExportPerMin = exportPerMinMain ? qty * (exportPerMin2 / exportPerMinMain) : 0;
            extraCells.Quantity = extraCells.Quantity ?? secondExportPerMin;
            extraCells.ExportPerMin = extraCells.ExportPerMin ?? secondExportPerMin;
        }

        return {
            row_id: r.id || i,
            recipe,
            quantity: qty,
            product: (recipe && (recipe.itemName || recipe.name)) || r.item_name_1 || '',
            recipeSetting: { clockSpeed: r.clock_speed === '' ? 100 : Number(r.clock_speed ?? r.clockSpeed ?? 100), useSomersloop: !!r.use_somersloop },
            productionImports: [] as ProductionImport[],
            imports: r.imports || [],
            exportPerMin: exportPerMin,
            extraCells: extraCells,
            Usage: usage
        };
    });

    // Prepare imports mapping (external imports) and iterate/consume ingredients from producers
    const importsMap: Record<string, { itemId: number; className: string; name: string; amount: number }> = {};
    const importsOrder: string[] = []; // keys in insertion order

    for (let i = 0; i < n; i++) {
        const row = rows[i];
        const recipe = recipeMap[row.recipe_id] ?? null;
        if (!recipe) continue;
        const rowQty = Number(row.product_quantity) || 0;
        const productionRate = recipe.export_amount_per_min ? rowQty / recipe.export_amount_per_min : 0;

        // reset per-row imports
        productionTableRows[i].imports = [];

        for (const ing of (recipe.ingredients || [])) {
            const useSomersloop = !!row.use_somersloop;
            const amountNeeded = (ing.quantity * productionRate) / (useSomersloop ? 2 : 1);
            let remainingNeed = amountNeeded;
            const ingNameLower = ing.name.toLowerCase();

            // consume from primary products
            for (let j = 0; j < n && remainingNeed > 0; j++) {
                if (producers[j].primaryNameLower !== ingNameLower) continue;
                const available = producers[j].productQty - usageArr[j];
                if (available <= 0) continue;
                const take = Math.min(available, remainingNeed);
                if (take > 0) {
                    productionTableRows[i].productionImports.push(new ProductionImport(j, take, ing.name, false));
                    usageArr[j] += take;
                    remainingNeed -= take;
                }
            }

            // consume from secondary products (extra)
            if (remainingNeed > 0) {
                for (let j = 0; j < n && remainingNeed > 0; j++) {
                    if (!producers[j].rec || !producers[j].exportPerMin2) continue;
                    if (producers[j].secondNameLower !== ingNameLower) continue;
                    const available = producers[j].extraQty - extraUsageArr[j];
                    if (available <= 0) continue;
                    const take = Math.min(available, remainingNeed);
                    if (take > 0) {
                        productionTableRows[i].productionImports.push(new ProductionImport(j, take, ing.name, true));
                        extraUsageArr[j] += take;
                        remainingNeed -= take;
                    }
                }
            }

            // remainingNeed > 0 becomes an external import; record it and attach to production row
            if (remainingNeed > 1e-7) {
                const foundItem = (appData.items || []).find((it: any) => it.name && it.name.toLowerCase() === ing.name.toLowerCase());
                const itemId = foundItem ? foundItem.id : 0;
                const className = foundItem ? foundItem.class_name : '';
                const name = ing.name;
                const key = `${itemId}-${name}`;

                if (!importsMap[key]) {
                    importsMap[key] = { itemId, className, name, amount: 0 };
                    importsOrder.push(key);
                }
                importsMap[key].amount += remainingNeed;

                const importIndex = importsOrder.indexOf(key);
                productionTableRows[i].imports.push({ index: importIndex, amount: remainingNeed, product: name });

                remainingNeed = 0;
            }
        }
    }

    const importsTableRows = importsOrder.map((key, i) => ({
        index: i,
        product: importsMap[key].name,
        quantity: importsMap[key].amount,
        itemId: importsMap[key].itemId
    }));

    // Build a checklist in the shape expected by Visualization/Checklist.ts
    const checklistFromServer: any[] = appData?.checklist || [];
    const checklistArray: any[] = [];

    for (let i = 0; i < checklistFromServer.length; i++) {
        const ck = checklistFromServer[i];
        // server row contains production_id -> map to productionTableRows row_id
        const productionRow = productionTableRows.find(pr => pr.row_id == ck.production_id || pr.row_id == (ck.productionRow && ck.productionRow.row_id));
        if (!productionRow) continue;
        checklistArray.push({ index: i, productionRow, beenBuild: !!ck.been_build || !!ck.beenBuild, beenTested: !!ck.been_tested || !!ck.beenTested });
    }

    const checklistObj = {
        getChecklist: () => checklistArray
    };

    const fakeTableHandler: any = {
        productionTableRows,
        importsTableRows,
        checklist: checklistObj,
        getRecipeById: (id: number) => recipeMap[id] || null,
        items: appData?.items || [],
    };

    return new Visualization(fakeTableHandler as any, options);
}
