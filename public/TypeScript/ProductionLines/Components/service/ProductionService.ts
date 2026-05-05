// Service to calculate imports from production rows (moved from ProductionLineApp)
export function calculateImports(appData: any, productionRows: any[], recipeMap: Record<number, any>) {
    const rows = productionRows || [];
    const n = rows.length;
    const usageArr = new Array(n).fill(0);
    const extraUsageArr = new Array(n).fill(0);
    
    if (!appData) return { imports: [], usageArr, extraUsageArr };
    
    const importsMap: Record<string, { itemId: number; className: string; name: string; amount: number }> = {};

    // precompute producer metadata
    const producers = rows.map((p: any) => {
        const rec = recipeMap[p.recipe_id] ?? null;
        const primaryName = rec && rec.products && rec.products[0] ? rec.products[0].name : p.item_name_1 || '';
        const secondName = rec && rec.products && rec.products[1] ? rec.products[1].name : p.item_name_2 || '';
        const exportPerMin = rec?.export_amount_per_min ?? 0;
        const exportPerMin2 = rec?.export_amount_per_min2 ?? 0;
        const productQty = Number(p.product_quantity) || 0;
        const primaryNameLower = primaryName.toLowerCase();
        const secondNameLower = secondName.toLowerCase();
        const extraQty = (exportPerMin2 && exportPerMin) ? productQty * (exportPerMin2 / exportPerMin) : 0;
        return {rec, primaryNameLower, secondNameLower, exportPerMin, exportPerMin2, productQty, extraQty};
    });

    for (let i = 0; i < n; i++) {
        const row = rows[i];
        const recipe = recipeMap[row.recipe_id] ?? null;
        if (!recipe) continue;
        const rowQty = Number(row.product_quantity) || 0;
        const productionRate = recipe.export_amount_per_min ? rowQty / recipe.export_amount_per_min : 0;

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
                usageArr[j] += take;
                remainingNeed -= take;
            }

            // consume from secondary products (extra)
            if (remainingNeed > 0) {
                for (let j = 0; j < n && remainingNeed > 0; j++) {
                    if (!producers[j].rec || !producers[j].exportPerMin2) continue;
                    if (producers[j].secondNameLower !== ingNameLower) continue;
                    const available = producers[j].extraQty - extraUsageArr[j];
                    if (available <= 0) continue;
                    const take = Math.min(available, remainingNeed);
                    extraUsageArr[j] += take;
                    remainingNeed -= take;
                }
            }

            if (remainingNeed > 1e-7) {
                const foundItem = (appData.items || []).find((it: any) => it.name && it.name.toLowerCase() === ing.name.toLowerCase());
                const itemId = foundItem ? foundItem.id : 0;
                const className = foundItem ? foundItem.class_name : '';
                const name = ing.name;
                const key = `${itemId}-${name}`;
                if (!importsMap[key]) importsMap[key] = {itemId, className, name, amount: 0};
                importsMap[key].amount += remainingNeed;
            }
        }
    }

    const newImports: any[] = Object.values(importsMap).map(it => ({
        ammount: it.amount,
        name: it.name,
        items_id: it.itemId,
        item_class_name: it.className
    }));

    return { imports: newImports, usageArr, extraUsageArr };
}
