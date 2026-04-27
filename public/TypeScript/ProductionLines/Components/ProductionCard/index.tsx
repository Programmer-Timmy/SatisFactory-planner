import React from 'react';
import { ProductionItem, Recipe } from '../../Types/global';
import RecipeSelect from '../RecipeSelect';
import OutputBlock from './OutputBlock';
import ExtraOutput from './ExtraOutput';
import BuildingStack from './BuildingStack';
import MachineControls from './MachineControls';
import { formatNumber } from '../../Utils/format';

type Props = {
    row: ProductionItem;
    recipe: Recipe;
    recipes: Recipe[];

    onDelete: (id: number) => void;
    onRecipeChange: (rowId: number, recipeId: number) => void;
    onQuantityChange: (rowId: number, value: number) => void;
    onClockSpeedChange: (rowId: number, value: number | '') => void;
    onSomersloopChange: (rowId: number, checked: boolean) => void;
};

const getIcon = (className?: string) => {
    if (!className) return '';
    return `/image/items/${className.toLowerCase().replaceAll('_', '-')}_256.png`;
};

export default function ProductionRowCard({
    row,
    recipe,
    recipes,
    onDelete,
    onRecipeChange,
    onQuantityChange,
    onClockSpeedChange,
    onSomersloopChange,
}: Props) {
    const output1 = recipe.products[0];
    const output2 = recipe.products[1];
    const building = recipe.building;

    const productionRate = (() => {
        const exportPerMin = recipe?.export_amount_per_min || 0;
        if (!exportPerMin) return 0;
        return Number(row.product_quantity) / exportPerMin;
    })();

    const rawClock = (row.clock_speed === '' || row.clock_speed === undefined || row.clock_speed === null) ? 100 : Number(row.clock_speed);
    const clockValue = Math.min(250, Math.max(0, rawClock));

    const buildingAmount = (() => {
        if (!recipe || !recipe.export_amount_per_min) return 0;
        const useSomersloop = !!row.use_somersloop;
        return Number(row.product_quantity) / (recipe.export_amount_per_min * (clockValue / 100)) / (useSomersloop ? 2 : 1);
    })();

    const localUsage = Number(row.local_usage ?? 0);
    const exportPerMin = Number(row.product_quantity) - localUsage;

    const extraQuantity = (() => {
        if (!recipe || recipe.export_amount_per_min2 == null || !recipe.export_amount_per_min) return 0;
        return Number(row.product_quantity) * (recipe.export_amount_per_min2 / recipe.export_amount_per_min);
    })();

    const localUsage2 = Number(row.local_usage2 ?? 0);
    const exportPerMin2 = extraQuantity - localUsage2;

    return (
        <div className="pl-row pl-production-row" data-row-index={row.id}>
            <button
                type="button"
                className="btn btn-sm pl-collapse-toggle"
                aria-label="Collapse/expand recipe details"
            >
                <i className="fa-solid fa-chevron-up" />
            </button>

            <input type="hidden" value={row.id} />

            <div className="pl-row-grid">
                <div className="pl-row-main">
                    <div className="pl-header-main">
                        <div className="pl-field">
                            <div className="pl-label">Recipe</div>

                            <RecipeSelect
                                recipes={recipes}
                                value={row.recipe_id}
                                onChange={(id) => onRecipeChange(row.id, id)}
                            />
                        </div>

                        <div className="pl-field">
                            <div className="pl-label">Qty / min</div>
                            <input
                                type="number"
                                min={0}
                                step="any"
                                className="form-control rounded-0"
                                value={row.product_quantity}
                                onChange={(e) =>
                                    onQuantityChange(row.id, Number(e.target.value))
                                }
                            />
                        </div>
                    </div>

                    <div className="pl-row-details">
                        <div className="pl-row-flow">
                            <OutputBlock product={output1} localUsage={localUsage} exportPerMin={exportPerMin} />
                        </div>

                        {output2 && (
                            <div className="pl-extra-output is-visible">
                                <div className="pl-row-flow">
                                    <ExtraOutput product={output2} localUsage={localUsage2} exportPerMin={exportPerMin2} />
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                <div className="pl-side-actions">
                    <button
                        type="button"
                        className="btn btn-sm delete-production-row"
                        onClick={() => onDelete(row.id)}
                    >
                        <i className="fa-solid fa-trash" />
                    </button>
                </div>

                <div className="pl-row-side">
                    <div className="pl-machine-settings">
                        <BuildingStack building={building} buildingAmount={buildingAmount} />

                        <MachineControls
                            clockValue={row.clock_speed === '' ? '' : (row.clock_speed ?? 100)}
                            onClockChange={(val) => onClockSpeedChange(row.id, val)}
                            useSomersloop={!!row.use_somersloop}
                            onSomersloopChange={(checked) => onSomersloopChange(row.id, checked)}
                        />
                    </div>
                </div>
            </div>
        </div>
    );
}
