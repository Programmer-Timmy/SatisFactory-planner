import React, {useEffect, useState, useMemo, useRef, useCallback} from 'react';
import PageTitle from "./PageTitle";
import ImportsCard from "./ImportsCard";
import ProductionRowCard from "./ProductionCard/index";
import Tooltip from "./Tooltip";
import ProductionAddCard from "./ProductionAddCard";
import { v4 as uuidv4 } from "uuid";

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
    collapsed?: boolean;
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

    // optimization helpers
    const importsTimerRef = useRef<number | null>(null);
    const idleRecalcRef = useRef<number | null>(null);
    const recipeMap = useMemo(() => {
        const m: Record<number, Recipe> = {};
        if (appData && appData.recipes) {
            for (const r of appData.recipes) m[r.id] = r;
        }
        return m;
    }, [appData?.recipes]);

    const itemsByName = useMemo(() => {
        const m: Record<string, Item> = {};
        if (appData && appData.items) {
            for (const it of appData.items) m[it.name.toLowerCase()] = it;
        }
        return m;
    }, [appData?.items]);

    useEffect(() => {
        const data = window.appData;
        if (data) {
            setAppData(data);
            setProductionRows(data.production.map(p => ({
                ...p,
                clock_speed: Math.max(0, Math.min(250, Number(p.clock_speed ?? 100)))
            })));
            setImportsList(data.imports.map(i => ({...i})));
            setLoading(false);
        }
        console.log(data);
    }, []);

    useEffect(() => {
        if (!appData) return;
        // schedule import recalculation during browser idle to keep add/delete instant
        if (idleRecalcRef.current != null) {
            if ('cancelIdleCallback' in window) {
                (window as any).cancelIdleCallback(idleRecalcRef.current);
            } else {
                clearTimeout(idleRecalcRef.current);
            }
            idleRecalcRef.current = null;
        }

        const schedule = () => {
            if ('requestIdleCallback' in window) {
                idleRecalcRef.current = (window as any).requestIdleCallback(() => {
                    recalculateImports();
                    idleRecalcRef.current = null;
                }, {timeout: 200});
            } else {
                idleRecalcRef.current = window.setTimeout(() => {
                    recalculateImports();
                    idleRecalcRef.current = null;
                }, 100);
            }
        };

        schedule();

        return () => {
            if (idleRecalcRef.current != null) {
                if ('cancelIdleCallback' in window) (window as any).cancelIdleCallback(idleRecalcRef.current);
                else clearTimeout(idleRecalcRef.current);
                idleRecalcRef.current = null;
            }
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [productionRows]);

    const findRecipe = (id?: number) => id != null ? (recipeMap[id] ?? null) : null;

    const recalculateImports = useCallback(() => {
        if (!appData) return;
        const rows = productionRows;
        const n = rows.length;
        const usageArr = new Array(n).fill(0);
        const extraUsageArr = new Array(n).fill(0);
        const importsMap: Record<string, { itemId: number; className: string; name: string; amount: number }> = {};

        // precompute producer metadata to avoid repeated finds
        const producers = rows.map(p => {
            const rec = recipeMap[p.recipe_id] ?? null;
            const primaryName = rec && rec.products && rec.products[0] ? rec.products[0].name : p.item_name_1 || '';
            const secondName = rec && rec.products && rec.products[1] ? rec.products[1].name : p.item_name_2 || '';
            const exportPerMin = rec?.export_amount_per_min ?? 0;
            const exportPerMin2 = rec?.export_amount_per_min2 ?? 0;
            const productQty = Number(p.product_quantity) || 0;
            const primaryNameLower = primaryName.toLowerCase();
            const secondNameLower = secondName.toLowerCase();
            const extraQty = (exportPerMin2 && exportPerMin) ? productQty * (exportPerMin2 / exportPerMin) : 0;
            return { rec, primaryNameLower, secondNameLower, exportPerMin, exportPerMin2, productQty, extraQty };
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
                    const foundItem = itemsByName[ing.name.toLowerCase()];
                    const itemId = foundItem ? foundItem.id : 0;
                    const className = foundItem ? foundItem.class_name : '';
                    const name = ing.name;
                    const key = `${itemId}-${name}`;
                    if (!importsMap[key]) importsMap[key] = { itemId, className, name, amount: 0 };
                    importsMap[key].amount += remainingNeed;
                }
            }
        }

        const newImports: ImportItem[] = Object.values(importsMap).map(it => ({
            ammount: it.amount,
            name: it.name,
            items_id: it.itemId,
            item_class_name: it.className
        }));
        setImportsList(newImports);
    }, [appData, productionRows, recipeMap, itemsByName]);

    // helper to change quantity
    const handleQuantityChange = (rowId: number, value: number) => {
        setProductionRows(prev => prev.map(r => r.id === rowId ? {...r, product_quantity: value} : r));
    };

    const handleRecipeChange = (rowId: number, recipeId: number) => {
        setProductionRows(prev => prev.map(r => r.id === rowId ? {
            ...r,
            recipe_id: recipeId,
            recipe_name: (appData?.recipes.find(x => x.id === recipeId)?.name) || ''
        } : r));
    };

    const handleClockSpeedChange = (rowId: number, value: number | '') => {
        // allow empty string during editing
        if (value === '') {
            setProductionRows(prev => prev.map(r => r.id === rowId ? {...r, clock_speed: '' as unknown as number} : r));
            return;
        }
        const v = Math.max(0, Math.min(250, Number(value)));
        setProductionRows(prev => prev.map(r => r.id === rowId ? {...r, clock_speed: v} : r));
    };

    const handleSomersloopChange = (rowId: number, checked: boolean) => {
        setProductionRows(prev => prev.map(r => r.id === rowId ? {...r, use_somersloop: checked} : r));
    };

    if (loading) {
        return <div className="container mt-5"><p>Loading...</p></div>;
    }

    if (!appData) {
        return <div className="container mt-5"><p>No data available</p></div>;
    }

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
                        {importsList.map((importItem) => (
                            <ImportsCard key={`${importItem.items_id}-${importItem.name}`} itemName={importItem.name} itemClass={importItem.item_class_name}
                                         amount={importItem.ammount}/>
                        ))}
                    </div>
                </div>
                <div className="col-md-9">
                    <div className="pl-production-header mb-1">
                        <h2 className="mb-0">Production</h2>
                        <Tooltip content="Collapse or expand all recipe cards." placement="top">
                            <button type="button" className="btn btn-outline-secondary btn-sm"
                                    onClick={() => {
                                        const allCollapsed = productionRows.every(r => r.collapsed);
                                        setProductionRows(prev => prev.map(r => ({...r, collapsed: !allCollapsed})));
                                    }}
                            >
                                <i className="fa-solid fa-compress me-1" aria-hidden="true"></i>
                                <span
                                    data-role="label">{productionRows.every(r => r.collapsed) ? 'Expand All' : 'Collapse All'}</span>
                            </button>
                        </Tooltip>
                    </div>
                    <p className="text-muted small mb-2">Flow: pick Recipe → set Qty/min → see output, usage and export.
                        Read-only
                        values are shown as labels (not inputs).</p>
                    <div className="pl-list mb-2">
                        {productionRows.map((productionItem) => (
                            <ProductionRowCard key={productionItem.id}
                                               row={productionItem}
                                               recipe={recipeMap[productionItem.recipe_id] || undefined}
                                               recipes={appData.recipes}
                                               onDelete={(id) => {
                                                   setProductionRows(prev => prev.filter(r => r.id !== id));
                                               }}
                                               onRecipeChange={(rowId, recipeId) => {
                                                   handleRecipeChange(rowId, recipeId);
                                               }}
                                               onQuantityChange={(rowId, value) => {
                                                   handleQuantityChange(rowId, value);
                                               }}
                                               onClockSpeedChange={(rowId, value) => {
                                                   handleClockSpeedChange(rowId, value);
                                               }}
                                               onSomersloopChange={(rowId, checked) => {
                                                   handleSomersloopChange(rowId, checked);
                                               }}
                                               onToggleCollapse={(rowId) => {
                                                   setProductionRows(prev => prev.map(r => r.id === rowId ? {
                                                       ...r,
                                                       collapsed: !r.collapsed
                                                   } : r));
                                               }}
                                               collapsed={productionItem.collapsed || false}
                            />
                        ))}
                        <ProductionAddCard onAdd={() => {
                            const newRow: ProductionItem = {
                                id: Date.now(),
                                item_name_1: '',
                                item_class_name_1: '',
                                item_name_2: null,
                                item_class_name_2: null,
                                local_usage: 0,
                                recipe_id: 0,
                                recipe_name: '',
                                export_amount_per_min: 0,
                                building_name: '',
                                building_class_name: '',
                                power_used: 0,
                                product_quantity: 0,
                                clock_speed: 100,
                                use_somersloop: false,
                                local_usage2: null,
                                export_ammount_per_min2: null
                            };
                            setProductionRows(prev => [...prev, newRow]);
                        }}/>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ProductionLineApp;

