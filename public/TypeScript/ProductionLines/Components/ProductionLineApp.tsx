import React, {useEffect, useMemo, useState} from 'react';
import PageTitle from "./PageTitle";
import PowerModal from "./modals/PowerModal";
import ImportsCard from "./ImportsCard";
import ProductionRowCard from "./ProductionCard/index";
import Tooltip from "./Tooltip";
import ProductionAddCard from "./ProductionAddCard";
import {calculateProductionPlan} from "./service/ProductionService";
import {calculateAutoPowerRows, computeConsumption, totalConsumption} from "./service/PowerService";
import VisualizationPanel from "./modals/VisualizationPanel";
import HelpModal from "./modals/HelpModal";
import ChecklistModal from "./modals/ChecklistModal";
import ProductionLineSettingsModal from "./modals/ProductionLineSettingsModal";
import Alert from "./Alert";
import type {AppData, ImportSourceSelection, PowerItem, ProductionItem, Recipe} from "../Types/global";
export type {AppData, ImportSourceSelection, PowerItem, ProductionItem, Recipe} from "../Types/global";


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
    const [checklistOpen, setChecklistOpen] = useState(false);
    const [settingsOpen, setSettingsOpen] = useState(false);

    const [productionRows, setProductionRows] = useState<ProductionItem[]>([]);
    const [importSourceSelections, setImportSourceSelections] = useState<ImportSourceSelection[]>([]);

    const normalizeImportSourceSelection = (source: any): ImportSourceSelection => ({
        exportingProductionLineId: Number(source.exportingProductionLineId ?? source.exporting_production_lines_id ?? 0),
        itemId: Number(source.itemId ?? source.items_id ?? 0),
        requestedAmount: Number(source.requestedAmount ?? source.requested_amount ?? 0),
        assignedAmount: Number(source.assignedAmount ?? source.assigned_amount ?? 0),
        productionLineTitle: source.productionLineTitle ?? source.production_line_title,
        itemName: source.itemName ?? source.item_name,
        itemClassName: source.itemClassName ?? source.item_class_name,
    });
    const recipeMap = useMemo(() => {
        const m: Record<number, Recipe> = {};
        if (appData && appData.recipes) {
            for (const r of appData.recipes) m[r.id] = r;
        }
        return m;
    }, [appData?.recipes]);

    const productionPlan = useMemo(
        () => calculateProductionPlan(appData, productionRows, recipeMap),
        [appData, productionRows, recipeMap]
    );
    const calculatedProductionRows = productionPlan.productionRows;
    const importsList = productionPlan.imports;



    useEffect(() => {
        const data = window.appData;
        if (data) {
            setAppData(data);
            setProductionRows(data.production.map(p => ({
                ...p,
                clock_speed: Math.max(0, Math.min(250, Number(p.clock_speed ?? 100)))
            })));
            setImportSourceSelections((data.importSourceSelections || []).map(normalizeImportSourceSelection));
            setLoading(false);
        }
    }, []);


    const handleQuantityChange = (rowId: number, value: number) => {
        setProductionRows(prev => prev.map(r => r.id === rowId ? {...r, product_quantity: value} : r));
        try {
            const umami = (window as any).umami;
            if (umami) {
                umami.track('Change Recipe Quantity', {
                    game_save: appData?.productLine.game_saves_id,
                    production_line: appData?.productLine.id
                });
            }
        } catch (e) { /* ignore */ }
    };

    const handleAddRecipeFromImport = (recipeId: number, importAmount: number) => {
        const recipe = appData?.recipes.find(r => r.id === recipeId);
        if (!recipe) return;

        const newRow: ProductionItem = {
            id: Date.now(),
            item_name_1: recipe.products?.[0]?.name || '',
            item_class_name_1: recipe.products?.[0]?.class_name || recipe.class_name || '',
            item_name_2: recipe.products?.[1]?.name || null,
            item_class_name_2: recipe.products?.[1]?.class_name || null,
            local_usage: 0,
            recipe_id: recipeId,
            recipe_name: recipe.name || '',
            export_amount_per_min: recipe.export_amount_per_min || 0,
            building_name: recipe.building?.[0]?.name || '',
            building_class_name: recipe.building?.[0]?.class_name || '',
            power_used: recipe.building?.[0]?.power_used || 0,
            product_quantity: importAmount,
            clock_speed: 100,
            use_somersloop: false,
            local_usage2: null,
            export_ammount_per_min2: recipe.export_amount_per_min2 || null,
            collapsed: false
        };
        setProductionRows(prev => [...prev, newRow]);
        try { const umami = (window as any).umami; if (umami) umami.track('Add Recipe From Import', { game_save: appData?.productLine.game_saves_id, production_line: appData?.productLine.id }); } catch(e){}
    };

    const handleRecipeChange = (rowId: number, recipeId: number) => {
        setProductionRows(prev => prev.map(r => r.id === rowId ? {
            ...r,
            recipe_id: recipeId,
            recipe_name: (appData?.recipes.find(x => x.id === recipeId)?.name) || ''
        } : r));
        try {
            const umami = (window as any).umami;
            if (umami) {
                umami.track('Change Recipe', {
                    game_save: appData?.productLine.game_saves_id,
                    production_line: appData?.productLine.id
                });
            }
        } catch (e) { /* ignore */ }
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
        const autoRows = calculateAutoPowerRows(appData, calculatedProductionRows, recipeMap);
        setPowerRows(prev => {
            const manual = (prev || []).filter(r => !!r.user);
            return [...autoRows, ...manual];
        });

        // Umami tracking for power recalculation
        try {
            const umami = (window as any).umami;
            if (umami) {
                umami.track('Calculate Power', {
                    game_save: appData.productLine.game_saves_id,
                    production_line: appData.productLine.id
                });
            }
        } catch (e) { /* ignore */ }
    }, [calculatedProductionRows, recipeMap, appData]);

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
        // Umami tracking: save action
        try {
            const umami = (window as any).umami;
            if (umami) umami.track('Save Production Line', { game_save: appData?.productLine.game_saves_id, production_line: appData?.productLine.id });
        } catch (e) { /* ignore */ }

        const saveService = await import('./service/SaveService');
        try {
            const importsToSave = productionPlan.imports;
            const productionRowsToSave = productionPlan.productionRows;

            const resp = await saveService.saveProductionLineData(appData, productionRowsToSave, powerRows, importsToSave, undefined, importSourceSelections);
            if (resp && resp.success) {
                const mappings = resp.data?.newAndOldIds || resp.data?.newAndOldIds || resp.newAndOldIds || [];
                const savedImportSources = (resp.data?.importSources || resp.importSources || []) as unknown[];
                const normalizedSources = savedImportSources.map(normalizeImportSourceSelection);

                if (mappings && mappings.length > 0) {
                    const mapOldToNew = new Map<string, number>();
                    mappings.forEach((m: any) => mapOldToNew.set(String(m.old), Number(m.new)));
                    setProductionRows(prev => prev.map(r => ({...r, id: mapOldToNew.get(String(r.id)) ?? r.id})));
                    setAppData(prev => prev ? {
                        ...prev,
                        production: (productionRowsToSave || []).map(r => ({...r, id: mapOldToNew.get(String(r.id)) ?? r.id}))
                    } : prev);
                } else {
                    setAppData(prev => prev ? {...prev, production: productionRowsToSave} : prev);
                }
                if (savedImportSources.length > 0 || importSourceSelections.length > 0) {
                    setImportSourceSelections(normalizedSources);
                    setAppData(prev => prev ? {...prev, importSourceSelections: normalizedSources} : prev);
                }

                setAppData(prev => prev ? {
                    ...prev,
                    imports: importsToSave,
                    powers: powerRows,
                    checklist: appData?.checklist || [],
                    importSourceSelections: normalizedSources
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
        try { const umami = (window as any).umami; if (umami) umami.track('Add Power Row', { game_save: appData?.productLine.game_saves_id, production_line: appData?.productLine.id }); } catch(e){}
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
        try { const umami = (window as any).umami; if (umami) umami.track('Save Power', { game_save: appData?.productLine.game_saves_id, production_line: appData?.productLine.id }); } catch(e){}
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
                onEdit={() => { try { const umami = (window as any).umami; if (umami) umami.track('Open Settings', { game_save: appData.productLine.game_saves_id, production_line: appData.productLine.id }); } catch(e){}; setSettingsOpen(true); }}
                onSave={handleSave}
                onChecklist={() => { try { const umami = (window as any).umami; if (umami) umami.track('Open Checklist', { game_save: appData.productLine.game_saves_id, production_line: appData.productLine.id }); } catch(e){}; setChecklistOpen(true); }}
                onHelp={() => { try { const umami = (window as any).umami; if (umami) umami.track('Open Help', { game_save: appData.productLine.game_saves_id, production_line: appData.productLine.id }); } catch(e){}; setHelpOpen(true) }}
                onPower={() => { try { const umami = (window as any).umami; if (umami) umami.track('Open Power', { game_save: appData.productLine.game_saves_id, production_line: appData.productLine.id }); } catch(e){}; setPowerOpen(true) }}
                onVisualization={() => { try { const umami = (window as any).umami; if (umami) umami.track('Open Visualization', { game_save: appData.productLine.game_saves_id, production_line: appData.productLine.id }); } catch(e){}; setVisualizationOpen(true) }}
            />
            <VisualizationPanel
                isOpen={visualizationOpen}
                onClose={() => setVisualizationOpen(false)}
                appData={appData}
                productionRows={calculatedProductionRows}
                importsList={importsList}
                recipeMap={recipeMap}
                importSourceSelections={importSourceSelections}
            />

            <HelpModal
                isOpen={helpOpen}
                onClose={() => setHelpOpen(false)}
            />

            <ChecklistModal
                isOpen={checklistOpen}
                onClose={() => setChecklistOpen(false)}
                appData={appData}
                productionRows={calculatedProductionRows}
                onSave={(checklist) => {
                    setAppData(prev => prev ? {...prev, checklist: checklist} : prev);
                }}
            />

            <ProductionLineSettingsModal
                isOpen={settingsOpen}
                onClose={() => setSettingsOpen(false)}
                appData={appData}
                productionRows={calculatedProductionRows}
                powerRows={powerRows}
                importsList={importsList}
                onSave={(pl) => {
                    setAppData(prev => prev ? {...prev, productLine: {...prev.productLine, ...pl}} : prev);
                }}
                onImport={async (data) => {
                    // prepare new local data but DO NOT change the productLine name
                    const newProduction = (data.production && data.production.length) ? data.production.map((p: any, idx: number) => ({...p, id: (p.row_id ?? p.id ?? p.rowId ?? p.recipeId ?? `import-${Date.now()}-${idx}`), clock_speed: Math.max(0, Math.min(250, Number(p.clock_speed ?? 100)))})) : productionRows;
                    const newPowers = (data.powers && data.powers.length) ? data.powers.map((p: any) => ({...p, clock_speed: Math.max(0, Math.min(250, Number(p.clock_speed ?? 100)))})) : powerRows;
                    const newImports = (data.imports && data.imports.length) ? data.imports.map((i: any) => ({...i})) : importsList;
                    const newChecklist = (data.checklist && data.checklist.length) ? data.checklist : appData.checklist || [];

                    // apply locally first
                    setProductionRows(newProduction);
                    setPowerRows(newPowers);
                    const importedPlan = calculateProductionPlan({...appData, production: newProduction, powers: newPowers, imports: newImports, checklist: newChecklist}, newProduction, recipeMap);
                    const importsToSave = importedPlan.imports;
                    const productionToSave = importedPlan.productionRows;
                    setAppData(prev => prev ? {...prev, powers: newPowers, production: productionToSave, imports: importsToSave, checklist: newChecklist} : prev);

                    // Now save the production line using SaveService (same behaviour as Save button)
                    try {
                        const saveService = await import('./service/SaveService');
                        const resp = await saveService.saveProductionLineData({...appData, production: productionToSave, powers: newPowers, imports: importsToSave, checklist: newChecklist}, productionToSave, newPowers, importsToSave, (newProduction || []).map((r: ProductionItem) => r.id), importSourceSelections);
                        if (resp && resp.success) {
                            const mappings = resp.data?.newAndOldIds || resp.data?.newAndOldIds || resp.newAndOldIds || [];
                            const savedImportSources = (resp.data?.importSources || resp.importSources || []) as unknown[];
                            const normalizedSources = savedImportSources.map(normalizeImportSourceSelection);

                            if (mappings && mappings.length > 0) {
                                const mapOldToNew = new Map<number, number>();
                                mappings.forEach((m: any) => mapOldToNew.set(Number(m.old), Number(m.new)));
                                // update productionRows ids
                                setProductionRows(prev => prev.map(r => ({...r, id: mapOldToNew.get(Number(r.id)) ?? r.id})));
                                setAppData(prev => prev ? {
                                    ...prev,
                                    production: (productionToSave || []).map(r => ({...r, id: mapOldToNew.get(Number(r.id)) ?? r.id}))
                                } : prev);
                            } else {
                                setAppData(prev => prev ? {...prev, production: productionToSave} : prev);
                            }

                            if (savedImportSources.length > 0 || importSourceSelections.length > 0) {
                                setImportSourceSelections(normalizedSources);
                                setAppData(prev => prev ? {...prev, importSourceSelections: normalizedSources} : prev);
                            }

                            setAppData(prev => prev ? {
                                ...prev,
                                imports: importsToSave,
                                powers: newPowers,
                                checklist: newChecklist,
                                importSourceSelections: normalizedSources
                            } : prev);

                            saveService.showSaveMessage(true, 'Production line imported and saved successfully.');
                            setSettingsOpen(false);
                        } else {
                            const err = resp?.error || 'Failed to save imported production line';
                            saveService.showSaveMessage(false, err);
                        }
                    } catch (e) {
                        console.error('Import+Save failed', e);
                        try {
                            const saveService = await import('./service/SaveService');
                            saveService.showSaveMessage(false, String(e));
                        } catch {}
                    }
                }}
            />
            <div className="row">
                <div className="col-md-3">
                    <h2 className="mb-0">Imports</h2>
                    <p className="text-muted small mb-2">Auto-calculated imports update as you edit recipes (toggle Auto
                        Import-Export in Edit).</p>
                    <div className="pl-list">
                        {importsList.map((importItem) => {
                            const producingRecipes = appData?.recipes.filter(r => 
                                r.products?.some(p => p.name?.toLowerCase() === importItem.name.toLowerCase())
                            ) || [];
                            const itemSourceCandidates = (appData.importSourceCandidates || []).filter(source => Number(source.items_id) === Number(importItem.items_id));
                            const itemSourceSelections = importSourceSelections.filter(source => Number(source.itemId) === Number(importItem.items_id));
                            return (
                                <ImportsCard 
                                    key={`${importItem.items_id}-${importItem.name}`} 
                                    itemName={importItem.name}
                                    itemClass={importItem.item_class_name}
                                    amount={importItem.ammount}
                                    itemId={importItem.items_id}
                                    producingRecipes={producingRecipes}
                                    sourceCandidates={itemSourceCandidates}
                                    sourceSelections={itemSourceSelections}
                                    onChangeSources={(sources) => {
                                        setImportSourceSelections(prev => [
                                            ...prev.filter(source => Number(source.itemId) !== Number(importItem.items_id)),
                                            ...sources
                                        ]);
                                    }}
                                    onAddRecipe={(recipeId) => handleAddRecipeFromImport(recipeId, importItem.ammount)}
                                />
                            );
                        })}
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
                        {calculatedProductionRows.map((productionItem) => (
                            <ProductionRowCard key={productionItem.id}
                                               row={productionItem}
                                               recipe={recipeMap[productionItem.recipe_id] || undefined}
                                               recipes={appData.recipes}
                                               onDelete={(id) => {
                                                   try { const umami = (window as any).umami; if (umami) umami.track('Delete Recipe', { game_save: appData.productLine.game_saves_id, production_line: appData.productLine.id, production_id: id }); } catch(e){}
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
                            try { const umami = (window as any).umami; if (umami) umami.track('Add Recipe', { game_save: appData?.productLine.game_saves_id, production_line: appData?.productLine.id }); } catch(e){}
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

