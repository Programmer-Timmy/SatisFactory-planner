import React, {useCallback, useEffect, useMemo, useRef, useState} from 'react';
import PageTitle from "./PageTitle";
import PowerModal from "./modals/PowerModal";
import ImportsCard from "./ImportsCard";
import ProductionRowCard from "./ProductionCard/index";
import Tooltip from "./Tooltip";
import ProductionAddCard from "./ProductionAddCard";
import {calculateImports} from "./service/ProductionService";
import {calculateAutoPowerRows, computeConsumption, totalConsumption} from "./service/PowerService";
import VisualizationPanel from "./modals/VisualizationPanel";
import HelpModal from "./modals/HelpModal";
import Alert from "./Alert";


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

export interface PowerItem {
    idpower: number;
    building_ammount: number;
    clock_speed: number;
    buildings_id: number;
    production_lines_id: number;
    power_used: number;
    user: number;
    building: Building | null;
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

export interface Building {
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

export interface AppData {
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
    const [visualizationOpen, setVisualizationOpen] = useState(false);
    const [helpOpen, setHelpOpen] = useState(false);

    const [productionRows, setProductionRows] = useState<ProductionItem[]>([]);
    const [importsList, setImportsList] = useState<ImportItem[]>([]);

    const idleRecalcRef = useRef<number | null>(null);
    const recipeMap = useMemo(() => {
        const m: Record<number, Recipe> = {};
        if (appData && appData.recipes) {
            for (const r of appData.recipes) m[r.id] = r;
        }
        return m;
    }, [appData?.recipes]);

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
    }, []);

    useEffect(() => {
        if (!appData) return;
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
                idleRecalcRef.current = (window as any).setTimeout(() => {
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

    const recalculateImports = useCallback(() => {
        if (!appData) return;
        const newImports = calculateImports(appData, productionRows, recipeMap);
        setImportsList(newImports);
    }, [appData, productionRows, recipeMap]);

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

    const [powerOpen, setPowerOpen] = useState(false);
    const [powerRows, setPowerRows] = useState<PowerItem[]>([]);

    useEffect(() => {
        if (!appData) return;
        setPowerRows(appData.powers.map(p => ({
            ...p,
            clock_speed: Math.max(0, Math.min(250, Number(p.clock_speed ?? 100))),
            building: appData.buildings.find(b => b.id === p.buildings_id) || null
        })));
    }, [appData]);

    useEffect(() => {
        if (!appData) return;
        const autoRows = calculateAutoPowerRows(appData, productionRows, recipeMap);
        setPowerRows(prev => {
            const manual = (prev || []).filter(r => !!r.user);
            return [...autoRows, ...manual];
        });
    }, [productionRows, recipeMap, appData]);

    const totalConsumptionValue = useMemo(() => {
        return totalConsumption(powerRows, appData);
    }, [powerRows, appData]);

    const handlePowerRowChange = (index: number, field: keyof PowerItem, value: any) => {
        switch (field) {
            case 'clock_speed':
                value = Math.max(0, Math.min(250, Number(value)));
                break;
            case 'building_ammount':
                value = Math.max(0, Number(value));
                break;
            case 'buildings_id':
                const building = appData?.buildings.find(b => b.id === Number(value)) || null;
                setPowerRows(prev => prev.map((r, i) => i === index ? {...r, [field]: value, building} : r));
                return;
        }
        setPowerRows(prev => prev.map((r, i) => i === index ? {...r, [field]: value} : r));
    };

    const handleSave = async () => {
        const saveService = await import('./service/SaveService');
        try {
            const resp = await saveService.saveProductionLineData(appData, productionRows, powerRows, importsList);
            if (resp && resp.success) {
                const mappings = resp.data?.newAndOldIds || resp.data?.newAndOldIds || resp.newAndOldIds || [];

                if (mappings && mappings.length > 0) {
                    const mapOldToNew = new Map<number, number>();
                    mappings.forEach((m: any) => mapOldToNew.set(Number(m.old), Number(m.new)));
                    setProductionRows(prev => prev.map(r => ({...r, id: mapOldToNew.get(Number(r.id)) ?? r.id})));
                    setAppData(prev => prev ? {
                        ...prev,
                        production: (productionRows || []).map(r => ({...r, id: mapOldToNew.get(Number(r.id)) ?? r.id}))
                    } : prev);
                } else {
                    setAppData(prev => prev ? {...prev, production: productionRows} : prev);
                }
                setAppData(prev => prev ? {
                    ...prev,
                    imports: importsList,
                    powers: powerRows,
                    checklist: appData?.checklist || []
                } : prev);

                saveService.showSaveMessage(true, 'Production line saved successfully.');
            } else {
                const err = resp?.error || 'Failed to save production line';
                saveService.showSaveMessage(false, err);
            }
        } catch (e) {
            console.error('Save failed', e);
            saveService.showSaveMessage(false, String(e));
        }
    }

    const addPowerRow = () => {
        setPowerRows(prev => [...prev, {
            idpower: Date.now(),
            building_ammount: 0,
            clock_speed: 100,
            buildings_id: 0,
            production_lines_id: appData?.productLine.id || 0,
            power_used: 0,
            user: 1,
            building: null
        }]);
    };

    const savePowerRows = () => {
        setAppData(prev => prev ? {...prev, powers: powerRows} : prev);
        setPowerOpen(false);
    };

    useEffect(() => {
        const handler = (event: KeyboardEvent) => {
            const key = (event.key || '').toLowerCase();
            if (event.ctrlKey && key === 'p') {
                event.preventDefault();
                setPowerOpen(prev => !prev);
                return;
            }
            if (event.ctrlKey && key === 'v' && (document.activeElement?.tagName !== 'INPUT' && document.activeElement?.tagName !== 'TEXTAREA')) {
                event.preventDefault();
                setVisualizationOpen(true);
                return;
            }
            if (event.ctrlKey && key === 's') {
                event.preventDefault();
                handleSave();
            }
            if (event.ctrlKey && key === 'h') {
                event.preventDefault();
                setHelpOpen(true);
            }
            if (event.ctrlKey && key === 'q') {
                event.preventDefault();
                window.location.href = `/game_save/${appData?.productLine.game_saves_id}`
            }
        };
        document.addEventListener('keydown', handler, true);
        return () => document.removeEventListener('keydown', handler, true);
    }, [handleSave, appData]);

    if (loading) {
        return (
            <div className="mt-auto position-absolute top-50 start-50 translate-middle-x w-100">
                <div className="container mt-auto d-flex justify-content-center align-items-center">
                    <div className="spinner-border text-primary" role="status" style={{width: '3rem', height: '3rem'}}>
                        <span className="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        );
    }

    if (!appData) {
        return <div className="container mt-5"><p>No data available</p></div>;
    }

    return (
        <div className="px-3 px-lg-5">
            <Alert/>
            <PageTitle
                GameSaveId={appData.productLine.game_saves_id}
                ProductionLineTitle={appData.productLine.title || "Unnamed Production Line"}
                onEdit={() => {
                }}
                onSave={handleSave}
                onChecklist={() => {
                }}
                onHelp={() => setHelpOpen(true)}
                onPower={() => setPowerOpen(true)}
                onVisualization={() => setVisualizationOpen(true)}
            />
            <VisualizationPanel
                isOpen={visualizationOpen}
                onClose={() => setVisualizationOpen(false)}
                appData={appData}
                productionRows={productionRows}
                importsList={importsList}
                recipeMap={recipeMap}
            />

            <HelpModal
                isOpen={helpOpen}
                onClose={() => setHelpOpen(false)}
            />
            <div className="row">
                <div className="col-md-3">
                    <h2 className="mb-0">Imports</h2>
                    <p className="text-muted small mb-2">Auto-calculated imports update as you edit recipes (toggle Auto
                        Import-Export in Edit).</p>
                    <div className="pl-list">
                        {importsList.map((importItem) => (
                            <ImportsCard key={`${importItem.items_id}-${importItem.name}`} itemName={importItem.name}
                                         itemClass={importItem.item_class_name}
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

            {/* Power modal */}
            <PowerModal
                isOpen={powerOpen}
                onClose={() => setPowerOpen(false)}
                rows={powerRows}
                appData={appData}
                onChangeRow={handlePowerRowChange}
                onAddRow={addPowerRow}
                onDeleteRow={(idx) => setPowerRows(prev => prev.filter((_, i) => i !== idx))}
                onSave={savePowerRows}
                computeConsumption={(r) => computeConsumption(r, appData)}
                totalConsumption={totalConsumptionValue}
            />

        </div>
    );
};

export default ProductionLineApp;

