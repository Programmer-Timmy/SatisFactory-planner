import React, {useState, useEffect} from 'react';
import {ProductionItem, Recipe} from '../../Types/global';
import RecipeSelect from '../RecipeSelect';
import OutputBlock from './OutputBlock';
import ExtraOutput from './ExtraOutput';
import BuildingStack from './BuildingStack';
import MachineControls from './MachineControls';
import {formatNumber} from '../../Utils/format';

type Props = {
    row: ProductionItem;
    recipe: Recipe | undefined;  // can be undefined when no recipe is selected yet
    recipes: Recipe[];

    collapsed: boolean;

    onDelete: (id: number) => void;
    onRecipeChange: (rowId: number, recipeId: number) => void;
    onQuantityChange: (rowId: number, value: number) => void;
    onClockSpeedChange: (rowId: number, value: number | '') => void;
    onSomersloopChange: (rowId: number, checked: boolean) => void;
    onToggleCollapse : (rowId: number) => void;
};

const getIcon = (className?: string) => {
    if (!className) return '';
    return `/image/items/${className.toLowerCase().replaceAll('_', '-')}_256.png`;
};

const ProductionRowCard = ({
                               row,
                               recipe,
                               recipes,
                               collapsed,
                               onDelete,
                               onRecipeChange,
                               onQuantityChange,
                               onClockSpeedChange,
                               onSomersloopChange,
                               onToggleCollapse
                           }: Props) => {
    const output1 = recipe?.products[0];
    const output2 = recipe?.products[1];
    const building = recipe?.building;

    // allow product quantity input to be empty for free typing while treating empty as 0 in calculations
    const [localQuantity, setLocalQuantity] = useState<string>(
        row.product_quantity === undefined || row.product_quantity === null
            ? ''
            : String(row.product_quantity)
    );
    const [isFocused, setIsFocused] = useState<boolean>(false);
    useEffect(() => {
        // don't overwrite local typing when the input is focused
        if (isFocused) return;
        const propStr = row.product_quantity === undefined || row.product_quantity === null
            ? ''
            : String(row.product_quantity);
        if (propStr !== localQuantity && Number(propStr) !== Number(localQuantity)) {
            setLocalQuantity(propStr);
        }
    }, [row.product_quantity, isFocused]);

    const numericProductQuantity = localQuantity === '' || localQuantity === undefined ? 0 : Number(localQuantity);

    const rawClock = (row.clock_speed === '' || row.clock_speed === undefined || row.clock_speed === null) ? 100 : Number(row.clock_speed);
    const clockValue = Math.min(250, Math.max(0, rawClock));

    const buildingAmount = (() => {
        if (!recipe?.export_amount_per_min) return 0;
        const useSomersloop = !!row.use_somersloop;
        return numericProductQuantity / (recipe.export_amount_per_min * (clockValue / 100)) / (useSomersloop ? 2 : 1);
    })();

    const localUsage = Number(row.local_usage ?? 0);
    const exportPerMin = numericProductQuantity - localUsage;

    const extraQuantity = (() => {
        if (!recipe?.export_amount_per_min || recipe.export_amount_per_min2 == null) return 0;
        return numericProductQuantity * (recipe.export_amount_per_min2 / recipe.export_amount_per_min);
    })();

    const localUsage2 = Number(row.local_usage2 ?? 0);
    const exportPerMin2 = extraQuantity - localUsage2;

    return (
        <div className={`pl-row pl-production-row ${collapsed ? 'is-collapsed' : ''}`} data-row-index={row.id}>
            <button
                type="button"
                className="btn btn-sm pl-collapse-toggle"
                aria-label="Collapse/expand recipe details"
                aria-expanded={!collapsed}
                onClick={() => onToggleCollapse(row.id)}
            >
                <i className={`fa-solid ${collapsed ? 'fa-chevron-down' : 'fa-chevron-up'}`}/>
            </button>

            <div className="pl-side-actions">
                <button
                    type="button"
                    className="btn btn-sm delete-production-row"
                    onClick={() => onDelete(row.id)}
                >
                    <i className="fa-solid fa-trash"/>
                </button>
            </div>

            <input type="hidden" value={row.id}/>

            <div className={`pl-row-grid ${collapsed ? 'is-hidden' : 'is-visible'}`} aria-hidden={collapsed}>
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
                                value={localQuantity}
                                onChange={(e) => {
                                    const v = e.target.value;
                                    setLocalQuantity(v);
                                    // If the field is empty while focused, don't push 0 to parent yet to allow free typing
                                    if (v === '') {
                                        if (!isFocused) {
                                            onQuantityChange(row.id, 0);
                                        }
                                    } else {
                                        onQuantityChange(row.id, Number(v));
                                    }
                                }}
                                onFocus={() => setIsFocused(true)}
                                onBlur={() => {
                                    setIsFocused(false);
                                    if (localQuantity === '') {
                                        setLocalQuantity('0');
                                        onQuantityChange(row.id, 0);
                                    }
                                }}
                            />
                        </div>
                    </div>

                    <div className="pl-row-details">
                        <div className="pl-row-flow">
                            <OutputBlock product={output1} localUsage={localUsage} exportPerMin={exportPerMin}/>
                        </div>

                        {output2 && (
                            <div className="pl-extra-output is-visible">
                                <div className="pl-row-flow">
                                    <ExtraOutput product={output2} localUsage={localUsage2}
                                                 exportPerMin={exportPerMin2}/>
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                <div className="pl-row-side">
                    <div className="pl-machine-settings">
                        {building && (
                            <BuildingStack building={building} buildingAmount={buildingAmount}/>
                        )}

                        <MachineControls
                            clockValue={row.clock_speed === '' ? '' : (row.clock_speed ?? 100)}
                            onClockChange={(val) => onClockSpeedChange(row.id, val)}
                            useSomersloop={!!row.use_somersloop}
                            onSomersloopChange={(checked) => onSomersloopChange(row.id, checked)}
                        />
                    </div>
                </div>
            </div>

            <div className={`pl-collapsed-summary ${collapsed ? 'is-visible' : 'is-hidden'}`}>
                {recipe ? (
                    <>
                        <div className="pl-collapsed-recipe" data-role="collapsed-recipe-name">{recipe.name}</div>
                        <div className="pl-collapsed-right">
                            {output1 && (
                                <span className="pl-collapsed-output">
                                    <img className="pl-item-icon" data-role="collapsed-output-icon" loading="lazy"
                                         src={getIcon(output1.class_name)} alt=""/>
                                    <span className="pl-collapsed-qty"
                                          data-role="collapsed-output-qty">{formatNumber(numericProductQuantity)}/min</span>
                                </span>
                            )}
                            {output2 && recipe.export_amount_per_min2 && recipe.export_amount_per_min && (
                                <span className="pl-collapsed-output">
                                    <img className="pl-item-icon" data-role="collapsed-output-icon" loading="lazy"
                                         src={getIcon(output2.class_name)} alt=""/>
                                    <span className="pl-collapsed-qty"
                                          data-role="collapsed-output-qty">{formatNumber(numericProductQuantity * (recipe.export_amount_per_min2 / recipe.export_amount_per_min))}/min</span>
                                </span>
                            )}
                        </div>
                    </>
                ) : (
                    <div className="pl-collapsed-recipe text-muted" data-role="collapsed-recipe-name">No recipe</div>
                )}
            </div>
        </div>
    );
};

export default React.memo(ProductionRowCard);