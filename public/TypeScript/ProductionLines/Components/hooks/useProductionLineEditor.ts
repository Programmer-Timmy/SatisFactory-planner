import {useCallback, useEffect, useMemo, useState} from 'react';
import type {Dispatch, SetStateAction} from 'react';
import type {AppData, ImportItem, ImportSourceSelection, PowerItem, ProductionItem, Recipe} from '../../Types/global';
import {calculateProductionPlan} from '../service/ProductionService';
import {calculateAutoPowerRows, computeConsumption, totalConsumption} from '../service/PowerService';
import {trackProductionLineEvent} from '../service/AnalyticsService';

declare global {
    interface Window {
        appData?: AppData;
    }
}

type ImportedData = Partial<Pick<AppData, 'checklist' | 'imports' | 'powers' | 'production'>>;
type SaveServiceModule = typeof import('../service/SaveService');

export interface UseProductionLineEditorResult {
    appData: AppData | null;
    loading: boolean;
    productionRows: ProductionItem[];
    calculatedProductionRows: ProductionItem[];
    importsList: ImportItem[];
    importSourceSelections: ImportSourceSelection[];
    recipeMap: Record<number, Recipe>;
    powerRows: PowerItem[];
    totalConsumptionValue: number;
    setImportSourceSelections: Dispatch<SetStateAction<ImportSourceSelection[]>>;
    handleQuantityChange: (rowId: number, value: number) => void;
    handleAddRecipeFromImport: (recipeId: number, importAmount: number) => void;
    handleRecipeChange: (rowId: number, recipeId: number) => void;
    handleClockSpeedChange: (rowId: number, value: number | '') => void;
    handleSomersloopChange: (rowId: number, checked: boolean) => void;
    handleDeleteProductionRow: (rowId: number) => void;
    handleToggleProductionRowCollapse: (rowId: number) => void;
    handleToggleAllProductionRows: () => void;
    handleAddProductionRow: () => void;
    handleSave: () => Promise<void>;
    handleChecklistSave: (checklist: AppData['checklist']) => void;
    handleProductLineSettingsSave: (productLine: Partial<AppData['productLine']>) => void;
    handleSettingsImport: (data: ImportedData) => Promise<boolean>;
    handlePowerRowChange: (index: number, field: keyof PowerItem, value: unknown) => void;
    handleAddPowerRow: () => void;
    handleDeletePowerRow: (index: number) => void;
    handleSavePowerRows: () => void;
    computePowerConsumption: (row: Partial<PowerItem>) => number;
}

const clampClockSpeed = (value: unknown): number => Math.max(0, Math.min(250, Number(value ?? 100)));

const toNumber = (value: unknown): number => {
    const parsed = Number(value ?? 0);
    return Number.isFinite(parsed) ? parsed : 0;
};

const normalizeImportSourceSelection = (source: Partial<ImportSourceSelection> & { exporting_production_lines_id?: unknown; items_id?: unknown; requested_amount?: unknown; assigned_amount?: unknown; production_line_title?: unknown; item_name?: unknown; item_class_name?: unknown }): ImportSourceSelection => ({
    exportingProductionLineId: Number(source.exportingProductionLineId ?? source.exporting_production_lines_id ?? 0),
    itemId: Number(source.itemId ?? source.items_id ?? 0),
    requestedAmount: Number(source.requestedAmount ?? source.requested_amount ?? 0),
    assignedAmount: Number(source.assignedAmount ?? source.assigned_amount ?? 0),
    productionLineTitle: String(source.productionLineTitle ?? source.production_line_title ?? ''),
    itemName: source.itemName === undefined && source.item_name === undefined ? undefined : String(source.itemName ?? source.item_name),
    itemClassName: source.itemClassName === undefined && source.item_class_name === undefined ? undefined : String(source.itemClassName ?? source.item_class_name),
});

const normalizeProductionRows = (rows: ProductionItem[]): ProductionItem[] => rows.map((row) => ({
    ...row,
    clock_speed: clampClockSpeed(row.clock_speed),
}));

const normalizeImportedProductionRows = (rows: ProductionItem[]): ProductionItem[] => rows.map((row, index) => ({
    ...row,
    id: toNumber((row as ProductionItem & Record<string, unknown>).row_id ?? row.id ?? (row as ProductionItem & Record<string, unknown>).rowId ?? row.recipe_id ?? `0`) || Date.now() + index,
    clock_speed: clampClockSpeed(row.clock_speed),
}));

const normalizePowerRows = (rows: PowerItem[], appData: AppData | null): PowerItem[] => rows.map((row) => ({
    ...row,
    clock_speed: clampClockSpeed(row.clock_speed),
    building: appData?.buildings.find((building) => building.id === row.buildings_id) || row.building || null,
}));

const mapSavedProductionIds = (
    rows: ProductionItem[],
    mappings: Array<{ old: string | number; new: string | number }>
): ProductionItem[] => {
    if (!mappings.length) return rows;

    const mapOldToNew = new Map<string, number>();
    mappings.forEach((mapping) => mapOldToNew.set(String(mapping.old), Number(mapping.new)));
    return rows.map((row) => ({...row, id: mapOldToNew.get(String(row.id)) ?? row.id}));
};

const normalizeSavedImportSources = (sources: unknown[]): ImportSourceSelection[] => (
    sources as Array<Partial<ImportSourceSelection> & Record<string, unknown>>
).map(normalizeImportSourceSelection);

export function useProductionLineEditor(): UseProductionLineEditorResult {
    const [appData, setAppData] = useState<AppData | null>(null);
    const [loading, setLoading] = useState(true);
    const [productionRows, setProductionRows] = useState<ProductionItem[]>([]);
    const [importSourceSelections, setImportSourceSelections] = useState<ImportSourceSelection[]>([]);
    const [powerRows, setPowerRows] = useState<PowerItem[]>([]);

    const recipeMap = useMemo(() => {
        const map: Record<number, Recipe> = {};
        if (appData?.recipes) {
            for (const recipe of appData.recipes) map[recipe.id] = recipe;
        }
        return map;
    }, [appData?.recipes]);

    const productionPlan = useMemo(
        () => calculateProductionPlan(appData, productionRows, recipeMap),
        [appData, productionRows, recipeMap]
    );

    const calculatedProductionRows = productionPlan.productionRows;
    const importsList = productionPlan.imports;

    useEffect(() => {
        const data = window.appData;
        if (!data) {
            setLoading(false);
            return;
        }

        setAppData(data);
        setProductionRows(normalizeProductionRows(data.production));
        setImportSourceSelections((data.importSourceSelections || []).map(normalizeImportSourceSelection));
        setLoading(false);
    }, []);

    useEffect(() => {
        if (!appData) return;
        setPowerRows(normalizePowerRows(appData.powers, appData));
    }, [appData]);

    useEffect(() => {
        if (!appData) return;

        const autoRows = calculateAutoPowerRows(appData, calculatedProductionRows, recipeMap);
        setPowerRows((previousRows) => {
            const manualRows = (previousRows || []).filter((row) => !!row.user);
            return [...autoRows, ...manualRows];
        });
        trackProductionLineEvent(appData, 'Calculate Power');
    }, [calculatedProductionRows, recipeMap, appData]);

    const totalConsumptionValue = useMemo(() => totalConsumption(powerRows, appData), [powerRows, appData]);

    const applySaveResponse = useCallback((
        saveService: SaveServiceModule,
        response: Awaited<ReturnType<SaveServiceModule['saveProductionLineData']>>,
        productionRowsToSave: ProductionItem[],
        powerRowsToSave: PowerItem[],
        importsToSave: ImportItem[],
        checklist: AppData['checklist'],
        successMessage: string
    ): boolean => {
        if (!response?.success) {
            saveService.showSaveMessage(false, response?.error || 'Failed to save production line');
            return false;
        }

        const mappings = response.data?.newAndOldIds || response.newAndOldIds || [];
        const savedImportSources = (response.data?.importSources || response.importSources || []) as unknown[];
        const normalizedSources = normalizeSavedImportSources(savedImportSources);
        const mappedProductionRows = mapSavedProductionIds(productionRowsToSave, mappings);

        setProductionRows((previousRows) => mapSavedProductionIds(previousRows, mappings));
        setImportSourceSelections(normalizedSources);
        setAppData((previousAppData) => previousAppData ? {
            ...previousAppData,
            production: mappedProductionRows,
            imports: importsToSave,
            powers: powerRowsToSave,
            checklist,
            importSourceSelections: normalizedSources,
        } : previousAppData);

        saveService.showSaveMessage(true, successMessage);
        return true;
    }, []);

    const handleQuantityChange = useCallback((rowId: number, value: number) => {
        setProductionRows((previousRows) => previousRows.map((row) => row.id === rowId ? {...row, product_quantity: value} : row));
        trackProductionLineEvent(appData, 'Change Recipe Quantity');
    }, [appData]);

    const handleAddRecipeFromImport = useCallback((recipeId: number, importAmount: number) => {
        const recipe = appData?.recipes.find((candidate) => candidate.id === recipeId);
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
            collapsed: false,
        };

        setProductionRows((previousRows) => [...previousRows, newRow]);
        trackProductionLineEvent(appData, 'Add Recipe From Import');
    }, [appData]);

    const handleRecipeChange = useCallback((rowId: number, recipeId: number) => {
        setProductionRows((previousRows) => previousRows.map((row) => row.id === rowId ? {
            ...row,
            recipe_id: recipeId,
            recipe_name: appData?.recipes.find((candidate) => candidate.id === recipeId)?.name || '',
        } : row));
        trackProductionLineEvent(appData, 'Change Recipe');
    }, [appData]);

    const handleClockSpeedChange = useCallback((rowId: number, value: number | '') => {
        if (value === '') {
            setProductionRows((previousRows) => previousRows.map((row) => row.id === rowId ? {...row, clock_speed: ''} : row));
            return;
        }

        setProductionRows((previousRows) => previousRows.map((row) => row.id === rowId ? {...row, clock_speed: clampClockSpeed(value)} : row));
    }, []);

    const handleSomersloopChange = useCallback((rowId: number, checked: boolean) => {
        setProductionRows((previousRows) => previousRows.map((row) => row.id === rowId ? {...row, use_somersloop: checked} : row));
    }, []);

    const handleDeleteProductionRow = useCallback((rowId: number) => {
        trackProductionLineEvent(appData, 'Delete Recipe', {production_id: rowId});
        setProductionRows((previousRows) => previousRows.filter((row) => row.id !== rowId));
    }, [appData]);

    const handleToggleProductionRowCollapse = useCallback((rowId: number) => {
        setProductionRows((previousRows) => previousRows.map((row) => row.id === rowId ? {...row, collapsed: !row.collapsed} : row));
    }, []);

    const handleToggleAllProductionRows = useCallback(() => {
        setProductionRows((previousRows) => {
            const allCollapsed = previousRows.every((row) => row.collapsed);
            return previousRows.map((row) => ({...row, collapsed: !allCollapsed}));
        });
    }, []);

    const handleAddProductionRow = useCallback(() => {
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
            export_ammount_per_min2: null,
        };

        setProductionRows((previousRows) => [...previousRows, newRow]);
        trackProductionLineEvent(appData, 'Add Recipe');
    }, [appData]);

    const handleSave = useCallback(async () => {
        trackProductionLineEvent(appData, 'Save Production Line');

        const saveService = await import('../service/SaveService');
        try {
            applySaveResponse(
                saveService,
                await saveService.saveProductionLineData(appData, calculatedProductionRows, powerRows, importsList, undefined, importSourceSelections),
                calculatedProductionRows,
                powerRows,
                importsList,
                appData?.checklist || [],
                'Production line saved successfully.'
            );
        } catch (error) {
            console.error('Save failed', error);
            saveService.showSaveMessage(false, String(error));
        }
    }, [appData, applySaveResponse, calculatedProductionRows, importSourceSelections, importsList, powerRows]);

    const handleChecklistSave = useCallback((checklist: AppData['checklist']) => {
        setAppData((previousAppData) => previousAppData ? {...previousAppData, checklist} : previousAppData);
    }, []);

    const handleProductLineSettingsSave = useCallback((productLine: Partial<AppData['productLine']>) => {
        setAppData((previousAppData) => previousAppData ? {
            ...previousAppData,
            productLine: {...previousAppData.productLine, ...productLine},
        } : previousAppData);
    }, []);

    const handleSettingsImport = useCallback(async (data: ImportedData): Promise<boolean> => {
        if (!appData) return false;

        const newProduction = data.production?.length ? normalizeImportedProductionRows(data.production) : productionRows;
        const newPowers = data.powers?.length ? normalizePowerRows(data.powers, appData) : powerRows;
        const newImports = data.imports?.length ? data.imports.map((importItem) => ({...importItem})) : importsList;
        const newChecklist = data.checklist?.length ? data.checklist : appData.checklist || [];
        const importedAppData = {...appData, production: newProduction, powers: newPowers, imports: newImports, checklist: newChecklist};
        const importedPlan = calculateProductionPlan(importedAppData, newProduction, recipeMap);
        const productionToSave = importedPlan.productionRows;
        const importsToSave = importedPlan.imports;

        setProductionRows(newProduction);
        setPowerRows(newPowers);
        setAppData((previousAppData) => previousAppData ? {
            ...previousAppData,
            powers: newPowers,
            production: productionToSave,
            imports: importsToSave,
            checklist: newChecklist,
        } : previousAppData);

        try {
            const saveService = await import('../service/SaveService');
            const response = await saveService.saveProductionLineData(
                {...appData, production: productionToSave, powers: newPowers, imports: importsToSave, checklist: newChecklist},
                productionToSave,
                newPowers,
                importsToSave,
                newProduction.map((row) => row.id),
                importSourceSelections
            );

            return applySaveResponse(
                saveService,
                response,
                productionToSave,
                newPowers,
                importsToSave,
                newChecklist,
                'Production line imported and saved successfully.'
            );
        } catch (error) {
            console.error('Import+Save failed', error);
            try {
                const saveService = await import('../service/SaveService');
                saveService.showSaveMessage(false, String(error));
            } catch (e) {
                // Keep the original import failure as the important error.
            }
            return false;
        }
    }, [appData, applySaveResponse, importSourceSelections, importsList, powerRows, productionRows, recipeMap]);

    const handlePowerRowChange = useCallback((index: number, field: keyof PowerItem, value: unknown) => {
        if (field === 'buildings_id') {
            const buildingId = toNumber(value);
            const building = appData?.buildings.find((candidate) => candidate.id === buildingId) || null;
            setPowerRows((previousRows) => previousRows.map((row, rowIndex) => rowIndex === index ? {
                ...row,
                buildings_id: buildingId,
                building,
            } : row));
            return;
        }

        const normalizedValue = field === 'clock_speed'
            ? clampClockSpeed(value)
            : field === 'building_ammount'
                ? Math.max(0, toNumber(value))
                : value;

        setPowerRows((previousRows) => previousRows.map((row, rowIndex) => rowIndex === index ? {
            ...row,
            [field]: normalizedValue,
        } : row));
    }, [appData]);

    const handleAddPowerRow = useCallback(() => {
        trackProductionLineEvent(appData, 'Add Power Row');
        setPowerRows((previousRows) => [...previousRows, {
            idpower: Date.now(),
            building_ammount: 0,
            clock_speed: 100,
            buildings_id: 0,
            production_lines_id: appData?.productLine.id || 0,
            power_used: 0,
            user: 1,
            building: null,
        }]);
    }, [appData]);

    const handleDeletePowerRow = useCallback((index: number) => {
        setPowerRows((previousRows) => previousRows.filter((_, rowIndex) => rowIndex !== index));
    }, []);

    const handleSavePowerRows = useCallback(() => {
        trackProductionLineEvent(appData, 'Save Power');
        setAppData((previousAppData) => previousAppData ? {...previousAppData, powers: powerRows} : previousAppData);
    }, [appData, powerRows]);

    const computePowerConsumption = useCallback((row: Partial<PowerItem>) => computeConsumption(row, appData), [appData]);

    return {
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
    };
}
