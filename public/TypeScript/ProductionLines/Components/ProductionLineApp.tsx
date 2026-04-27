import React, {useEffect, useState} from 'react';
import PageTitle from "./PageTitle";
import ImportsCard from "./ImportsCard";
import ProductionRowCard from "./ProductionCard";

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
    clock_speed: number;
    use_somersloop: number;
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

    useEffect(() => {
        const data = window.appData;
        if (data) {
            setAppData(data);
            setLoading(false);
        }
        console.log(data);
    }, []);

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
                        {appData.imports.map((importItem, index) => (
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
                        {appData.production.map((productionItem, index) => (
                            //type Props = {
                            //     row: ProductionRow;
                            //     recipe: Recipe;
                            //     recipes: Recipe[];
                            //
                            //     onDelete: (id: number) => void;
                            //     onRecipeChange: (rowId: number, recipeId: number) => void;
                            //     onQuantityChange: (rowId: number, value: number) => void;
                            //     onClockSpeedChange: (rowId: number, value: number) => void;
                            //     onSomersloopChange: (rowId: number, checked: boolean) => void;
                            // };
                            <ProductionRowCard key={index} row={productionItem} recipe={appData.recipes.find(r => r.id === productionItem.recipe_id)!} recipes={appData.recipes} onDelete={() => {
                            }} onRecipeChange={() => {
                            }} onQuantityChange={() => {
                            }} onClockSpeedChange={() => {
                            }} onSomersloopChange={() => {
                            }}/>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
};

export default ProductionLineApp;
