import React from 'react';
import {ProductionItem, Recipe} from "./ProductionLineApp";
import RecipeSelect from "./RecipeSelect";

type ItemRate = {
    id: number;
    name: string;
    className: string;
    quantity: number;
};

type Building = {
    name: string;
    className: string;
};

type Props = {
    row: ProductionItem;
    recipe: Recipe;
    recipes: Recipe[];

    onDelete: (id: number) => void;
    onRecipeChange: (rowId: number, recipeId: number) => void;
    onQuantityChange: (rowId: number, value: number) => void;
    onClockSpeedChange: (rowId: number, value: number) => void;
    onSomersloopChange: (rowId: number, checked: boolean) => void;
};

const getIcon = (className?: string) => {
    if (!className) return '';
    return `/image/items/${className.toLowerCase().replaceAll('_', '-')}_256.png`;
};

const getBuildingIcon = (className?: string) => {
    if (!className) return '';
    return `/image/items/${className
        .toLowerCase()
        .replaceAll('_', '-')
        .replace('build', 'desc')}_256.png`;
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

    const formatNumber = (value: any) => {
        const n = Number(value ?? 0);
        if (Number.isNaN(n)) return String(value ?? '');
        if (n % 1 === 0) return n.toFixed(0);
        const rounded = Math.round(n * 100000) / 100000;
        return rounded.toFixed(5).replace(/0+$/, '').replace(/\.$/, '');
    };

    const productionRate = (() => {
        const exportPerMin = recipe?.export_amount_per_min || 0;
        if (!exportPerMin) return 0;
        return row.product_quantity / exportPerMin;
    })();

    const buildingAmount = (() => {
        if (!recipe || !recipe.export_amount_per_min) return 0;
        const rawClock = (row.clock_speed === '' || row.clock_speed === undefined || row.clock_speed === null) ? 100 : Number(row.clock_speed);
        const clock = Math.min(250, Math.max(0, rawClock));
        const useSomersloop = !!row.use_somersloop;
        return row.product_quantity / (recipe.export_amount_per_min * (clock / 100)) / (useSomersloop ? 2 : 1);
    })();

    const localUsage = (() => {
        // best-effort: use provided local_usage when present, otherwise 0
        return Number(row.local_usage ?? 0);
    })();

    const exportPerMin = (() => {
        return row.product_quantity - localUsage;
    })();

    const extraQuantity = (() => {
        if (!recipe || recipe.export_amount_per_min2 == null || !recipe.export_amount_per_min) return 0;
        const second = recipe.export_amount_per_min2;
        const first = recipe.export_amount_per_min;
        return row.product_quantity * (second / first);
    })();

    const localUsage2 = Number(row.local_usage2 ?? 0);
    const exportPerMin2 = extraQuantity - localUsage2;

    // building icon src
    const buildingIcon = building && building[0] ? getBuildingIcon(building[0].class_name) : '';

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
                            <div className="pl-field">
                                <div className="pl-label">Output</div>

                                <div className="pl-field-with-icon">
                                    {output1 && (
                                        <img
                                            className="pl-item-icon"
                                            src={getIcon(output1.class_name)}
                                        />
                                    )}
                                    <div className="pl-value">{output1?.name}</div>
                                </div>
                            </div>

                            <div className="pl-field">
                                <div className="pl-label">Local usage / min</div>
                                <div className="pl-value pl-number">{formatNumber(localUsage)}</div>
                            </div>

                            <div className="pl-field">
                                <div className="pl-label">Export / min</div>
                                <div className="pl-value pl-number">{formatNumber(exportPerMin)}</div>
                            </div>
                        </div>

                        {output2 && (
                            <div className="pl-extra-output is-visible">
                                <div className="pl-row-flow">
                                    <div className="pl-field">
                                        <div className="pl-label">By-product</div>

                                        <div className="pl-field-with-icon">
                                            <img
                                                className="pl-item-icon"
                                                src={getIcon(output2.class_name)}
                                            />
                                            <div className="pl-value">{output2.name}</div>
                                        </div>
                                    </div>

                                    <div className="pl-field">
                                        <div className="pl-label">Local usage / min</div>
                                        <div className="pl-value pl-number">
                                            {formatNumber(localUsage2)}
                                        </div>
                                    </div>

                                    <div className="pl-field">
                                        <div className="pl-label">Export / min</div>
                                        <div className="pl-value pl-number">
                                            {formatNumber(exportPerMin2)}
                                        </div>
                                    </div>
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
                        <div className="pl-building-stack">
                            {building && (
                                <img
                                    className="pl-building-icon"
                                    src={buildingIcon}
                                />
                            )}

                            <div className="pl-building-amount pl-number">
                                {formatNumber(buildingAmount)}
                            </div>
                        </div>

                        <div className="pl-machine-controls">
                            <div className="pl-field">
                                <div className="pl-label">Clock %</div>

                                <input
                                    type="number"
                                    min={0}
                                    max={250}
                                    step="any"
                                    className="form-control rounded-0"
                                    value={row.clock_speed === '' ? '' : (row.clock_speed ?? 100)}
                                    onChange={(e) =>
                                        onClockSpeedChange(row.id, e.target.value === '' ? '' : Number(e.target.value))
                                    }
                                    onBlur={() => {
                                        if (row.clock_speed === '' || row.clock_speed === undefined || row.clock_speed === null) {
                                            onClockSpeedChange(row.id, 100);
                                        }
                                    }}
                                />
                            </div>

                            <div className="form-check mt-1">
                                <input
                                    className="form-check-input"
                                    type="checkbox"
                                    checked={!!row.use_somersloop}
                                    onChange={(e) =>
                                        onSomersloopChange(row.id, e.target.checked)
                                    }
                                />

                                <label className="form-check-label small">
                                    Somersloop
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}