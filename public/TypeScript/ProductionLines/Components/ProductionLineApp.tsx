import React, {useEffect, useState} from 'react';
import PageTitle from "./PageTitle";
import ImportsCard from "./ImportsCard";
import ProductionRowCard from "./ProductionCard/index";

interface ProductLine {
    id: number;
    title?: string;
    active?: number;
    created_at?: string;
    updated_at?: string;
    description?: string;
    power_consumbtion?: number;
    game_saves_id: number;
}

interface ImportItem {
    ammount: number;
    name: string;
    items_id: number;
    item_class_name: string;
}

export interface ProductionItem {
    id: number;
    item_name_1: string;
    item_class_name_1: string;
    item_name_2: string | null;
    item_class_name_2: string | null;
    local_usage2: number | null;
    export_ammount_per_min2: number | null;
    recipe_id: number;
    local_usage: number;
    recipe_name: string;
    export_amount_per_min: number;
    building_name: string;
    building_class_name: string;
    power_used: number;
    product_quantity: number;
    // allow empty string while editing clock
    clock_speed: number | '';
    use_somersloop: number | boolean | null;
}

interface PowerItem {
    idpower: number;
    building_ammount: number;
    clock_speed: number;
    buildings_id: number;
    production_lines_id: number;
    power_used: number;
    user: number;
    building: string;
}

interface ChecklistItem {
    id: number;
    production_lines_id: number;
    production_id: number;
    been_build: number;
    been_tested: number;
}

export interface Item {
    id: number;
    name: string;
    class_name: string;
    form: string;
}

interface RecipeIngredient {
    id: number;
    form: string;
    name: string;
    quantity: number;
    class_name: string;
}

interface RecipeBuilding {
    id: number;
    name: string;
    class_name: string;
    power_used: number;
    power_generated: number;
}

interface RecipeProduct {
    id: number;
    form: string;
    name: string;
    quantity: number;
    class_name: string;
}

export interface Recipe {
    id: number;
    name: string;
    export_amount_per_min: number;
    export_amount_per_min2: number | null;
    class_name: string;
    buildings_id: number;
    item_id: number;
    item_id2: number | null;
    ingredients: RecipeIngredient[];
    building: RecipeBuilding[];
    products: RecipeProduct[];
}

interface Building {
    id: number;
    name: string;
    class_name: string;
    power_used: number;
    power_generation: number;
    image: string;
}

interface ProductionSetting {
    id: number;
    clockSpeed: number;
    useSomersloop: boolean;
}

interface AppData {
    productLine: ProductLine;
    imports: ImportItem[];
    production: ProductionItem[];
    powers: PowerItem[];
    checklist: ChecklistItem[];
    items: Item[];
    recipes: Recipe[];
    buildings: Building[];
    itemClassMap: Record<string, string>;
    productionSettings: ProductionSetting[];
    viewOnly: boolean;
    firstProduction: boolean;
    userId: number | null;
    importsReadonly: boolean;
}

declare global {
    interface Window {
        appData?: AppData;
    }
}

const ProductionLineApp: React.FC = () => {
    const [appData, setAppData] = useState<AppData | null>(null);
    const [loading, setLoading] = useState(true);

    // local editable production rows and imports
    const [productionRows, setProductionRows] = useState<ProductionItem[]>([]);
    const [importsList, setImportsList] = useState<ImportItem[]>([]);

    useEffect(() => {
        const data = window.appData;
        if (data) {
            setAppData(data);
            setProductionRows(data.production.map(p => ({ ...p, clock_speed: Math.max(0, Math.min(250, Number(p.clock_speed ?? 100))) })));
            setImportsList(data.imports.map(i => ({ ...i })));
            setLoading(false);
        }
        console.log(data);
    }, []);

    useEffect(() => {
        if (!appData) return;
        // Recalculate imports whenever productionRows change
        recalculateImports();
    // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [productionRows]);

    const findRecipe = (id: number | undefined) => appData?.recipes.find(r => r.id === id) || null;

    const recalculateImports = () => {
        if (!appData) return;
        // clone rows to mutate
        const rows = productionRows.map(r => ({ ...r })) as ProductionItem[];

        // reset usage and extra usage
        const usageArr = new Array(rows.length).fill(0);
        const extraUsageArr = new Array(rows.length).fill(0);

        const importsMap: Record<string, { itemId: number; className: string; name: string; amount: number }> = {};

        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const recipe = findRecipe(row.recipe_id);
            if (!recipe) continue;

            const rowQty = Number(row.product_quantity) || 0;
            const productionRate = recipe.export_amount_per_min ? rowQty / recipe.export_amount_per_min : 0;
            // for each ingredient
            for (const ing of (recipe.ingredients || [])) {
                const useSomersloop = !!row.use_somersloop;
                const amountNeeded = (ing.quantity * productionRate) / (useSomersloop ? 2 : 1);
                let remainingNeed = amountNeeded;

                // try to consume from produced rows primary products
                for (let j = 0; j < rows.length && remainingNeed > 0; j++) {
                    const producer = rows[j];
                    const producerRecipe = findRecipe(producer.recipe_id);
                    if (!producerRecipe) continue;
                    // primary product name
                    const producedName = producerRecipe.products && producerRecipe.products[0] ? producerRecipe.products[0].name : producer.item_name_1;
                    if (producedName?.toLowerCase() !== ing.name.toLowerCase()) continue;

                    const producerQty = Number(producer.product_quantity) || 0;
                    const available = producerQty - usageArr[j];
                    if (available <= 0) continue;
                    const take = Math.min(available, remainingNeed);
                    usageArr[j] += take;
                    remainingNeed -= take;
                }

                // try to consume from double-export extra products
                if (remainingNeed > 0) {
                    for (let j = 0; j < rows.length && remainingNeed > 0; j++) {
                        const producer = rows[j];
                        const producerRecipe = findRecipe(producer.recipe_id);
                        if (!producerRecipe) continue;
                        if (!producerRecipe.export_amount_per_min2) continue;
                        const secondName = producerRecipe.products && producerRecipe.products[1] ? producerRecipe.products[1].name : producer.item_name_2 || '';
                        if (secondName?.toLowerCase() !== ing.name.toLowerCase()) continue;

                        // compute extraQuantity for producer using its recipe data
                        const producerQty = Number(producer.product_quantity) || 0;
                        const extraQty = (producerRecipe.export_amount_per_min2 != null && producerRecipe.export_amount_per_min) ? producerQty * (producerRecipe.export_amount_per_min2 / producerRecipe.export_amount_per_min) : 0;
                        const available = extraQty - extraUsageArr[j];
                        if (available <= 0) continue;
                        const take = Math.min(available, remainingNeed);
                        extraUsageArr[j] += take;
                        remainingNeed -= take;
                    }
                }

                // if still remaining, add to imports
                if (remainingNeed > 0.0000001) {
                    // find item id and class from appData.items
                    const foundItem = appData.items.find(it => it.name.toLowerCase() === ing.name.toLowerCase());
                    const itemId = foundItem ? foundItem.id : 0;
                    const className = foundItem ? foundItem.class_name : '';
                    const name = ing.name;
                    const key = `${itemId}-${name}`;
                    if (!importsMap[key]) importsMap[key] = { itemId: itemId, className: className, name: name, amount: 0 };
                    importsMap[key].amount += remainingNeed;
                }
            }
        }

        // build importsList
        const newImports: ImportItem[] = Object.values(importsMap).map(it => ({ ammount: it.amount, name: it.name, items_id: it.itemId, item_class_name: it.className }));
        setImportsList(newImports);
    };

    // helper to change quantity
    const handleQuantityChange = (rowId: number, value: number) => {
        setProductionRows(prev => prev.map(r => r.id === rowId ? { ...r, product_quantity: value } : r));
    };

    const handleRecipeChange = (rowId: number, recipeId: number) => {
        setProductionRows(prev => prev.map(r => r.id === rowId ? { ...r, recipe_id: recipeId, recipe_name: (appData?.recipes.find(x=>x.id===recipeId)?.name) || '' } : r));
    };

    const handleClockSpeedChange = (rowId: number, value: number | '' ) => {
        // allow empty string during editing
        if (value === '') {
            setProductionRows(prev => prev.map(r => r.id === rowId ? { ...r, clock_speed: '' as unknown as number } : r));
            return;
        }
        const v = Math.max(0, Math.min(250, Number(value)));
        setProductionRows(prev => prev.map(r => r.id === rowId ? { ...r, clock_speed: v } : r));
    };

    const handleSomersloopChange = (rowId: number, checked: boolean) => {
        setProductionRows(prev => prev.map(r => r.id === rowId ? { ...r, use_somersloop: checked } : r));
    };

    if (loading) {
        return <div className="container mt-5"><p>Loading...</p></div>;
    }

    if (!appData) {
        return <div className="container mt-5"><p>No data available</p></div>;
    }

    console.log(appData);

    return (
        <div className="px-3 px-lg-5">
            <PageTitle GameSaveId={appData.productLine.game_saves_id}
                       ProductionLineTitle={appData.productLine.title || "Unnamed Production Line"}/>
            <div className="row">
                <div className="col-md-3">
                    <h2 className="mb-0">Imports</h2>
                    <p className="text-muted small mb-2">Auto-calculated imports update as you edit recipes (toggle Auto
                        Import-Export in Edit).</p>
                    <div className="pl-list">
                        {importsList.map((importItem, index) => (
                            <ImportsCard key={index} itemName={importItem.name} itemClass={importItem.item_class_name}
                                         amount={importItem.ammount}/>
                        ))}
                    </div>
                </div>
                <div className="col-md-9">
                    <div className="pl-production-header mb-1">
                        <h2 className="mb-0">Production</h2>
                        <button type="button" className="btn btn-outline-secondary btn-sm" id="pl-toggle-collapse-all"
                                data-state="expanded" data-bs-toggle="tooltip" data-bs-placement="top"
                                data-bs-container="body"
                                data-bs-title="Collapse or expand all recipe cards.">
                            <i className="fa-solid fa-compress me-1" aria-hidden="true"></i>
                            <span data-role="label">Collapse all</span>
                        </button>
                    </div>
                    <p className="text-muted small mb-2">Flow: pick Recipe → set Qty/min → see output, usage and export.
                        Read-only
                        values are shown as labels (not inputs).</p>
                    <div className="pl-list">
                        {productionRows.map((productionItem, index) => (
                            <ProductionRowCard key={index}
                                               row={productionItem}
                                               recipe={appData.recipes.find(r => r.id === productionItem.recipe_id)!}
                                               recipes={appData.recipes}
                                               onDelete={(id) => { setProductionRows(prev => prev.filter(r => r.id !== id)); }}
                                               onRecipeChange={(rowId, recipeId) => { handleRecipeChange(rowId, recipeId); }}
                                               onQuantityChange={(rowId, value) => { handleQuantityChange(rowId, value); }}
                                               onClockSpeedChange={(rowId, value) => { handleClockSpeedChange(rowId, value); }}
                                               onSomersloopChange={(rowId, checked) => { handleSomersloopChange(rowId, checked); }}
                            />
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ProductionLineApp;

