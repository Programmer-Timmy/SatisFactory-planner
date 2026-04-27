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
                                <div className="pl-value pl-number">{row.local_usage}</div>
                            </div>

                            <div className="pl-field">
                                <div className="pl-label">Export / min</div>
                                <div className="pl-value pl-number">{row.export_amount_per_min}</div>
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
                                            {row.local_usage2 ?? 0}
                                        </div>
                                    </div>

                                    <div className="pl-field">
                                        <div className="pl-label">Export / min</div>
                                        <div className="pl-value pl-number">
                                            {row.export_ammount_per_min2 ?? 0}
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
                                    src={getBuildingIcon(building[0].class_name)}
                                />
                            )}

                            <div className="pl-building-amount pl-number">
                                n/a
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
                                    value={100}
                                    onChange={(e) =>
                                        onClockSpeedChange(row.id, Number(e.target.value))
                                    }
                                />
                            </div>

                            <div className="form-check mt-1">
                                <input
                                    className="form-check-input"
                                    type="checkbox"
                                    checked={false}
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