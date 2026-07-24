import type {AppData, Building, ChecklistItem, ImportItem, ImportSourceSelection, Item, ProductionItem, Recipe} from "../Types/global";
import {Import as ProductionImport} from "./visualization-data/Import";
import {Visualization} from "./Visualization";

type VisualizationOptions = { onProgress?: (pct: number) => void };

type VisualizationRecipe = Omit<Recipe, 'building'> & {
    building: Building | null;
    itemName?: string;
    secondItemName?: string;
    resources?: Array<{ itemId: number }>;
};

interface ProducerMetadata {
    rec: VisualizationRecipe | null;
    primaryNameLower: string;
    secondNameLower: string;
    exportPerMin: number;
    exportPerMin2: number;
    productQty: number;
    extraQty: number;
}

interface ProductionTableImport {
    index: number;
    amount: number;
    product: string;
}

interface ProductionTableRow {
    row_id: number;
    recipe: VisualizationRecipe | null;
    quantity: number;
    product: string;
    recipeSetting: { clockSpeed: number; useSomersloop: boolean };
    productionImports: ProductionImport[];
    imports: ProductionTableImport[];
    exportPerMin: number;
    extraCells: { Quantity?: number; ExportPerMin?: number; Usage?: number; Product?: string };
    Usage: number;
}

interface ImportSourceVisualizationSelection extends ImportSourceSelection {
    shortAmount: number;
}

interface ImportTableRow {
    index: number;
    product: string;
    quantity: number;
    itemId: number;
    sourceSelections: ImportSourceVisualizationSelection[];
    sourcedQuantity: number;
    unresolvedQuantity: number;
}

interface ChecklistVisualizationItem {
    index: number;
    productionRow: ProductionTableRow;
    beenBuild: boolean;
    beenTested: boolean;
}

interface VisualizationTableHandler {
    productionTableRows: ProductionTableRow[];
    importsTableRows: ImportTableRow[];
    checklist: { getChecklist: () => ChecklistVisualizationItem[] };
    getRecipeById: (id: number) => VisualizationRecipe | null;
    items: Item[];
}

const toNumber = (value: unknown): number => {
    const parsed = Number(value ?? 0);
    return Number.isFinite(parsed) ? parsed : 0;
};

const normalizeImportSourceSelection = (source: Partial<ImportSourceSelection> & { exporting_production_lines_id?: unknown; items_id?: unknown; requested_amount?: unknown; assigned_amount?: unknown; production_line_title?: unknown; item_name?: unknown; item_class_name?: unknown }): ImportSourceSelection => ({
    exportingProductionLineId: Number(source.exportingProductionLineId ?? source.exporting_production_lines_id ?? 0),
    itemId: Number(source.itemId ?? source.items_id ?? 0),
    requestedAmount: Number(source.requestedAmount ?? source.requested_amount ?? 0),
    assignedAmount: Number(source.assignedAmount ?? source.assigned_amount ?? 0),
    productionLineTitle: String(source.productionLineTitle ?? source.production_line_title ?? 'Unknown line'),
    itemName: source.itemName === undefined && source.item_name === undefined ? undefined : String(source.itemName ?? source.item_name),
    itemClassName: source.itemClassName === undefined && source.item_class_name === undefined ? undefined : String(source.itemClassName ?? source.item_class_name),
});

const withVisualizationBuildings = (
    appData: AppData,
    recipeMap: Record<number, Recipe>
): Record<number, VisualizationRecipe | null> => Object.fromEntries(
    Object.entries(recipeMap).map(([key, recipe]) => {
        if (!recipe) return [key, null];

        const buildingInfo = recipe.building?.[0] || null;
        const foundBuilding =
            appData.buildings.find((building) => building.class_name === (buildingInfo?.class_name || "")) ||
            appData.buildings.find((building) => building.id === (recipe.buildings_id || 0)) ||
            null;

        return [
            key,
            {
                ...recipe,
                building: foundBuilding,
                itemName: recipe.products?.[0]?.name,
                secondItemName: recipe.products?.[1]?.name,
                resources: recipe.ingredients?.map((ingredient) => ({itemId: ingredient.id})),
            },
        ];
    })
) as Record<number, VisualizationRecipe | null>;

export function createVisualizationFromData(
    appData: AppData,
    productionRows: ProductionItem[],
    importsList: ImportItem[],
    recipeMap: Record<number, Recipe>,
    options?: VisualizationOptions
): Visualization {
    const rows = productionRows || [];
    const visualizationRecipeMap = withVisualizationBuildings(appData, recipeMap);

    const producers: ProducerMetadata[] = rows.map((row) => {
        const recipe = visualizationRecipeMap[row.recipe_id] ?? null;
        const primaryName = recipe?.products?.[0]?.name ?? row.item_name_1 ?? '';
        const secondName = recipe?.products?.[1]?.name ?? row.item_name_2 ?? '';
        const exportPerMin = toNumber(recipe?.export_amount_per_min);
        const exportPerMin2 = toNumber(recipe?.export_amount_per_min2);
        const productQty = toNumber(row.product_quantity);
        const extraQty = exportPerMin2 && exportPerMin ? productQty * (exportPerMin2 / exportPerMin) : 0;

        return {
            rec: recipe,
            primaryNameLower: primaryName.toLowerCase(),
            secondNameLower: secondName.toLowerCase(),
            exportPerMin,
            exportPerMin2,
            productQty,
            extraQty,
        };
    });

    const usageArr = new Array<number>(rows.length).fill(0);
    const extraUsageArr = new Array<number>(rows.length).fill(0);

    const productionTableRows: ProductionTableRow[] = rows.map((row, index) => {
        const recipe = visualizationRecipeMap[row.recipe_id] || null;
        const quantity = toNumber(row.product_quantity);
        const usage = toNumber((row as ProductionItem & { Usage?: number; localUsage?: number }).Usage ?? row.local_usage ?? (row as ProductionItem & { localUsage?: number }).localUsage);
        const extraCells: ProductionTableRow['extraCells'] = {};
        let exportPerMin = toNumber((row as ProductionItem & { exportPerMin?: number }).exportPerMin ?? row.export_amount_per_min);

        if (!exportPerMin) {
            exportPerMin = Math.max(0, quantity - usage);
        }

        if (recipe?.export_amount_per_min2 && recipe.export_amount_per_min) {
            const secondExportPerMin = quantity * (recipe.export_amount_per_min2 / recipe.export_amount_per_min);
            extraCells.Quantity = secondExportPerMin;
            extraCells.ExportPerMin = secondExportPerMin;
            extraCells.Product = recipe.products?.[1]?.name ?? row.item_name_2 ?? '';
        }

        return {
            row_id: row.id || index,
            recipe,
            quantity,
            product: recipe?.itemName || recipe?.name || row.item_name_1 || '',
            recipeSetting: {
                clockSpeed: row.clock_speed === '' ? 100 : toNumber(row.clock_speed ?? 100),
                useSomersloop: !!row.use_somersloop,
            },
            productionImports: [],
            imports: [],
            exportPerMin,
            extraCells,
            Usage: usage,
        };
    });

    const importSourceSelections = (appData.importSourceSelections || [])
        .map(normalizeImportSourceSelection)
        .filter((source) => source.itemId > 0 && source.requestedAmount > 0);

    const importSourcesByItem = new Map<number, ImportSourceVisualizationSelection[]>();
    for (const source of importSourceSelections) {
        const list = importSourcesByItem.get(source.itemId) || [];
        list.push({
            ...source,
            shortAmount: Math.max(0, source.requestedAmount - source.assignedAmount),
        });
        importSourcesByItem.set(source.itemId, list);
    }

    const importsMap: Record<string, { itemId: number; className: string; name: string; amount: number }> = {};
    const importsOrder: string[] = [];

    for (let i = 0; i < rows.length; i++) {
        const row = rows[i];
        const recipe = visualizationRecipeMap[row.recipe_id] ?? null;
        if (!recipe) continue;

        const rowQty = toNumber(row.product_quantity);
        const productionRate = recipe.export_amount_per_min ? rowQty / recipe.export_amount_per_min : 0;
        productionTableRows[i].imports = [];

        for (const ingredient of recipe.ingredients || []) {
            const amountNeeded = (ingredient.quantity * productionRate) / (row.use_somersloop ? 2 : 1);
            let remainingNeed = amountNeeded;
            const ingredientNameLower = ingredient.name.toLowerCase();

            for (let j = 0; j < rows.length && remainingNeed > 0; j++) {
                if (producers[j].primaryNameLower !== ingredientNameLower) continue;

                const available = producers[j].productQty - usageArr[j];
                if (available <= 0) continue;

                const take = Math.min(available, remainingNeed);
                productionTableRows[i].productionImports.push(new ProductionImport(j, take, ingredient.name, false));
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
                    productionTableRows[i].productionImports.push(new ProductionImport(j, take, ingredient.name, true));
                    extraUsageArr[j] += take;
                    remainingNeed -= take;
                }
            }

            if (remainingNeed > 1e-7) {
                const foundItem = (appData.items || []).find((item) => item.name?.toLowerCase() === ingredient.name.toLowerCase());
                const itemId = foundItem?.id ?? 0;
                const className = foundItem?.class_name ?? '';
                const key = `${itemId}-${ingredient.name}`;

                if (!importsMap[key]) {
                    importsMap[key] = {itemId, className, name: ingredient.name, amount: 0};
                    importsOrder.push(key);
                }
                importsMap[key].amount += remainingNeed;

                const importIndex = importsOrder.indexOf(key);
                productionTableRows[i].imports.push({index: importIndex, amount: remainingNeed, product: ingredient.name});
                remainingNeed = 0;
            }
        }
    }

    const importsTableRows: ImportTableRow[] = importsOrder.map((key, index) => {
        const row = importsMap[key];
        const sourceSelections = importSourcesByItem.get(row.itemId) || [];
        const sourcedQuantity = sourceSelections.reduce((total, source) => total + Math.max(0, toNumber(source.assignedAmount)), 0);

        return {
            index,
            product: row.name,
            quantity: row.amount,
            itemId: row.itemId,
            sourceSelections,
            sourcedQuantity,
            unresolvedQuantity: Math.max(0, row.amount - sourcedQuantity),
        };
    });

    const checklistArray: ChecklistVisualizationItem[] = [];
    for (let i = 0; i < (appData.checklist || []).length; i++) {
        const checklistItem = (appData.checklist || [])[i] as ChecklistItem & { productionRow?: { row_id?: number }; beenBuild?: boolean; beenTested?: boolean };
        const productionRow = productionTableRows.find((row) => row.row_id == checklistItem.production_id || row.row_id == checklistItem.productionRow?.row_id);
        if (!productionRow) continue;

        checklistArray.push({
            index: i,
            productionRow,
            beenBuild: !!checklistItem.been_build || !!checklistItem.beenBuild,
            beenTested: !!checklistItem.been_tested || !!checklistItem.beenTested,
        });
    }

    const tableHandler: VisualizationTableHandler = {
        productionTableRows,
        importsTableRows,
        checklist: {
            getChecklist: () => checklistArray,
        },
        getRecipeById: (id: number) => visualizationRecipeMap[id] || null,
        items: appData.items || [],
    };

    // Visualization is still a legacy boundary; keep its loose constructor isolated here.
    return new Visualization(tableHandler as unknown as ConstructorParameters<typeof Visualization>[0], options);
}