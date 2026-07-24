import type {AppData, ImportItem, ProductionItem, Recipe} from '../../Types/global';

export interface ProductionPlan {
    productionRows: ProductionItem[];
    imports: ImportItem[];
    usageArr: number[];
    extraUsageArr: number[];
}

interface ProducerMetadata {
    rec: Recipe | null;
    primaryNameLower: string;
    secondNameLower: string;
    exportPerMin: number;
    exportPerMin2: number;
    productQty: number;
    extraQty: number;
}

const toNumber = (value: unknown): number => {
    const parsed = Number(value ?? 0);
    return Number.isFinite(parsed) ? parsed : 0;
};

const calculateProductionFields = (
    row: ProductionItem,
    recipe: Recipe | undefined,
    index: number,
    usageArr: number[],
    extraUsageArr: number[]
): Pick<ProductionItem, 'local_usage' | 'local_usage2' | 'export_amount_per_min' | 'export_ammount_per_min2'> => {
    const productQuantity = toNumber(row.product_quantity);
    const localUsage = usageArr[index] ?? 0;
    const hasSecondProduct = !!(recipe?.export_amount_per_min && recipe.export_amount_per_min2 != null);
    const localUsage2 = hasSecondProduct ? (extraUsageArr[index] ?? 0) : null;
    const extraQuantity = hasSecondProduct
        ? productQuantity * (toNumber(recipe.export_amount_per_min2) / toNumber(recipe.export_amount_per_min))
        : null;

    return {
        local_usage: localUsage,
        local_usage2: localUsage2,
        export_amount_per_min: productQuantity - localUsage,
        export_ammount_per_min2: extraQuantity === null ? null : extraQuantity - toNumber(localUsage2),
    };
};

export function calculateProductionPlan(
    appData: AppData | null,
    productionRows: ProductionItem[],
    recipeMap: Record<number, Recipe>
): ProductionPlan {
    const rows = productionRows || [];
    const usageArr = new Array<number>(rows.length).fill(0);
    const extraUsageArr = new Array<number>(rows.length).fill(0);

    if (!appData) {
        return {productionRows: rows, imports: [], usageArr, extraUsageArr};
    }

    const importsMap: Record<string, { itemId: number; className: string; name: string; amount: number }> = {};

    const producers: ProducerMetadata[] = rows.map((row) => {
        const rec = recipeMap[row.recipe_id] ?? null;
        const primaryName = rec?.products?.[0]?.name ?? row.item_name_1 ?? '';
        const secondName = rec?.products?.[1]?.name ?? row.item_name_2 ?? '';
        const exportPerMin = toNumber(rec?.export_amount_per_min);
        const exportPerMin2 = toNumber(rec?.export_amount_per_min2);
        const productQty = toNumber(row.product_quantity);
        const extraQty = exportPerMin2 && exportPerMin ? productQty * (exportPerMin2 / exportPerMin) : 0;

        return {
            rec,
            primaryNameLower: primaryName.toLowerCase(),
            secondNameLower: secondName.toLowerCase(),
            exportPerMin,
            exportPerMin2,
            productQty,
            extraQty,
        };
    });

    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const recipe = recipeMap[row.recipe_id] ?? null;
        if (!recipe) continue;

        const rowQty = toNumber(row.product_quantity);
        const recipeExportPerMin = toNumber(recipe.export_amount_per_min);
        const productionRate = recipeExportPerMin ? rowQty / recipeExportPerMin : 0;

        for (const ingredient of recipe.ingredients || []) {
            const amountNeeded = (toNumber(ingredient.quantity) * productionRate) / (row.use_somersloop ? 2 : 1);
            let remainingNeed = amountNeeded;
            const ingredientNameLower = ingredient.name.toLowerCase();

            for (let j = 0; j < rows.length && remainingNeed > 0; j++) {
                if (producers[j].primaryNameLower !== ingredientNameLower) continue;
                const available = producers[j].productQty - usageArr[j];
                if (available <= 0) continue;

                const take = Math.min(available, remainingNeed);
                usageArr[j] += take;
                remainingNeed -= take;
            }

            if (remainingNeed > 0) {
                for (let j = 0; j < rows.length && remainingNeed > 0; j++) {
                    if (!producers[j].rec || !producers[j].exportPerMin2) continue;
                    if (producers[j].secondNameLower !== ingredientNameLower) continue;

                    const available = producers[j].extraQty - extraUsageArr[j];
                    if (available <= 0) continue;

                    const take = Math.min(available, remainingNeed);
                    extraUsageArr[j] += take;
                    remainingNeed -= take;
                }
            }

            if (remainingNeed > 1e-7) {
                const foundItem = (appData.items || []).find((item) => item.name?.toLowerCase() === ingredientNameLower);
                const itemId = foundItem?.id ?? 0;
                const className = foundItem?.class_name ?? '';
                const key = `${itemId}-${ingredient.name}`;

                if (!importsMap[key]) {
                    importsMap[key] = {itemId, className, name: ingredient.name, amount: 0};
                }
                importsMap[key].amount += remainingNeed;
            }
        }
    }

    const imports: ImportItem[] = Object.values(importsMap).map((item) => ({
        ammount: item.amount,
        name: item.name,
        items_id: item.itemId,
        item_class_name: item.className,
    }));

    const calculatedProductionRows = rows.map((row, index) => ({
        ...row,
        ...calculateProductionFields(row, recipeMap[row.recipe_id], index, usageArr, extraUsageArr),
    }));

    return {productionRows: calculatedProductionRows, imports, usageArr, extraUsageArr};
}

export function calculateImports(
    appData: AppData | null,
    productionRows: ProductionItem[],
    recipeMap: Record<number, Recipe>
): Pick<ProductionPlan, 'imports' | 'usageArr' | 'extraUsageArr'> {
    const {imports, usageArr, extraUsageArr} = calculateProductionPlan(appData, productionRows, recipeMap);
    return {imports, usageArr, extraUsageArr};
}
