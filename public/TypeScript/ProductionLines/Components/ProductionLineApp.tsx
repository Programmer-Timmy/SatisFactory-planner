import React, {useEffect, useState} from 'react';
import PageTitle from "./PageTitle";
import PowerModal from "./modals/PowerModal";
import ImportsCard from "./ImportsCard";
import ProductionRowCard from "./ProductionCard/index";
import Tooltip from "./Tooltip";
import ProductionAddCard from "./ProductionAddCard";
import VisualizationPanel from "./modals/VisualizationPanel";
import HelpModal from "./modals/HelpModal";
import ChecklistModal from "./modals/ChecklistModal";
import ProductionLineSettingsModal from "./modals/ProductionLineSettingsModal";
import Alert from "./Alert";
import {useProductionLineEditor} from "./hooks/useProductionLineEditor";
import {trackProductionLineEvent} from "./service/AnalyticsService";
import type {AppData, ImportSourceSelection, PowerItem, ProductionItem, Recipe} from "../Types/global";
export type {AppData, ImportSourceSelection, PowerItem, ProductionItem, Recipe} from "../Types/global";

const ProductionLineApp: React.FC = () => {
    const editor = useProductionLineEditor();
    const {
        appData,
        loading,
        productionRows,
        calculatedProductionRows,
        importsList,
        importSourceSelections,
        recipeMap,
        powerRows,
        totalConsumptionValue,
        setImportSourceSelections,
        handleQuantityChange,
        handleAddRecipeFromImport,
        handleRecipeChange,
        handleClockSpeedChange,
        handleSomersloopChange,
        handleDeleteProductionRow,
        handleToggleProductionRowCollapse,
        handleToggleAllProductionRows,
        handleAddProductionRow,
        handleSave,
        handleChecklistSave,
        handleProductLineSettingsSave,
        handleSettingsImport,
        handlePowerRowChange,
        handleAddPowerRow,
        handleDeletePowerRow,
        handleSavePowerRows,
        computePowerConsumption,
    } = editor;

    const [visualizationOpen, setVisualizationOpen] = useState(false);
    const [helpOpen, setHelpOpen] = useState(false);
    const [checklistOpen, setChecklistOpen] = useState(false);
    const [settingsOpen, setSettingsOpen] = useState(false);
    const [powerOpen, setPowerOpen] = useState(false);

    const allProductionRowsCollapsed = productionRows.length > 0 && productionRows.every((row) => row.collapsed);

    useEffect(() => {
        const handler = (event: KeyboardEvent) => {
            const key = (event.key || '').toLowerCase();
            if (event.ctrlKey && key === 'p') {
                event.preventDefault();
                setPowerOpen((previousOpen) => !previousOpen);
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
                window.location.href = `/game_save/${appData?.productLine.game_saves_id}`;
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
                    trackProductionLineEvent(appData, 'Open Settings');
                    setSettingsOpen(true);
                }}
                onSave={handleSave}
                onChecklist={() => {
                    trackProductionLineEvent(appData, 'Open Checklist');
                    setChecklistOpen(true);
                }}
                onHelp={() => {
                    trackProductionLineEvent(appData, 'Open Help');
                    setHelpOpen(true);
                }}
                onPower={() => {
                    trackProductionLineEvent(appData, 'Open Power');
                    setPowerOpen(true);
                }}
                onVisualization={() => {
                    trackProductionLineEvent(appData, 'Open Visualization');
                    setVisualizationOpen(true);
                }}
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
                onSave={handleChecklistSave}
            />

            <ProductionLineSettingsModal
                isOpen={settingsOpen}
                onClose={() => setSettingsOpen(false)}
                appData={appData}
                productionRows={calculatedProductionRows}
                powerRows={powerRows}
                importsList={importsList}
                onSave={handleProductLineSettingsSave}
                onImport={async (data) => {
                    const imported = await handleSettingsImport(data);
                    if (imported) setSettingsOpen(false);
                }}
            />
            <div className="row">
                <div className="col-md-3">
                    <h2 className="mb-0">Imports</h2>
                    <p className="text-muted small mb-2">Auto-calculated imports update as you edit recipes (toggle Auto
                        Import-Export in Edit).</p>
                    <div className="pl-list">
                        {importsList.map((importItem) => {
                            const producingRecipes = appData.recipes.filter((recipe) =>
                                recipe.products?.some((product) => product.name?.toLowerCase() === importItem.name.toLowerCase())
                            );
                            const itemSourceCandidates = (appData.importSourceCandidates || []).filter((source) => Number(source.items_id) === Number(importItem.items_id));
                            const itemSourceSelections = importSourceSelections.filter((source) => Number(source.itemId) === Number(importItem.items_id));
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
                                        setImportSourceSelections((previousSources) => [
                                            ...previousSources.filter((source) => Number(source.itemId) !== Number(importItem.items_id)),
                                            ...sources,
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
                                    onClick={handleToggleAllProductionRows}
                            >
                                <i className="fa-solid fa-compress me-1" aria-hidden="true"></i>
                                <span data-role="label">{allProductionRowsCollapsed ? 'Expand All' : 'Collapse All'}</span>
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
                                               onDelete={handleDeleteProductionRow}
                                               onRecipeChange={handleRecipeChange}
                                               onQuantityChange={handleQuantityChange}
                                               onClockSpeedChange={handleClockSpeedChange}
                                               onSomersloopChange={handleSomersloopChange}
                                               onToggleCollapse={handleToggleProductionRowCollapse}
                                               collapsed={productionItem.collapsed || false}
                            />
                        ))}
                        <ProductionAddCard onAdd={handleAddProductionRow}/>
                    </div>
                </div>
            </div>

            <PowerModal
                isOpen={powerOpen}
                onClose={() => setPowerOpen(false)}
                rows={powerRows}
                appData={appData}
                onChangeRow={handlePowerRowChange}
                onAddRow={handleAddPowerRow}
                onDeleteRow={handleDeletePowerRow}
                onSave={() => {
                    handleSavePowerRows();
                    setPowerOpen(false);
                }}
                computeConsumption={computePowerConsumption}
                totalConsumption={totalConsumptionValue}
            />
        </div>
    );
};

export default ProductionLineApp;
