import React, {useEffect, useState} from 'react';
import Modal from "../Modal";
import LegacyBootstrapToggle from "../LegacyBootstrapToggle";
import type {AppData, ImportItem, PowerItem, ProductLine, ProductionItem} from "../../Types/global";

interface ProductionLineImportPreview {
    productLine: Partial<ProductLine> | null;
    production: ProductionItem[];
    powers: PowerItem[];
    imports: ImportItem[];
    checklist: any[];
}

interface ProductionLineImportData {
    production?: ProductionItem[];
    powers?: PowerItem[];
    imports?: ImportItem[];
    checklist?: any[];
    productLine?: Partial<ProductLine> | null;
}

interface Props {
    isOpen: boolean;
    onClose: () => void;
    appData: AppData;
    productionRows?: ProductionItem[];
    powerRows?: PowerItem[];
    importsList?: ImportItem[];
    onSave: (productLine: { title?: string; active?: number }) => void;
    onImport?: (data: ProductionLineImportData) => void;
}

const toArray = <T,>(value: unknown): T[] => Array.isArray(value) ? value as T[] : [];

const ProductionLineSettingsModal: React.FC<Props> = ({isOpen, onClose, appData, productionRows, powerRows, importsList, onSave, onImport}) => {
    const [title, setTitle] = useState<string>(appData?.productLine?.title || '');
    const [active, setActive] = useState<boolean>(!!appData?.productLine?.active);
    const [message, setMessage] = useState<string | null>(null);
    const [importPreview, setImportPreview] = useState<ProductionLineImportPreview | null>(null);

    useEffect(() => {
        if (!isOpen) return;
        setTitle(appData?.productLine?.title || '');
        setActive(!!appData?.productLine?.active);
        setMessage(null);
        setImportPreview(null);
    }, [isOpen, appData]);

    const handleSave = () => {
        onSave({title: title || undefined, active: active ? 1 : 0});
        setMessage('Saved locally. Use Save to persist to server.');
        setTimeout(() => setMessage(null), 3000);
        onClose();
    };

    const handleExport = () => {
        try {
            const payload = {
                productLine: {
                    id: appData?.productLine?.id,
                    title: title || appData?.productLine?.title || '',
                    active: active ? 1 : 0,
                    game_saves_id: appData?.productLine?.game_saves_id || 0,
                },
                production: (productionRows && productionRows.length) ? productionRows : (appData?.production || []),
                powers: (powerRows && powerRows.length) ? powerRows : (appData?.powers || []),
                imports: (importsList && importsList.length) ? importsList : (appData?.imports || []),
                checklist: appData?.checklist || [],
            };
            const blob = new Blob([JSON.stringify(payload, null, 2)], {type: 'application/json'});
            const url = URL.createObjectURL(blob);
            const anchor = document.createElement('a');
            anchor.href = url;
            const name = (appData?.productLine?.title || 'production-line').replace(/[^a-z0-9\-_]/gi, '-').toLowerCase();
            anchor.download = `${name || 'production-line'}-${appData?.productLine?.id || '0'}.json`;
            document.body.appendChild(anchor);
            anchor.click();
            anchor.remove();
            URL.revokeObjectURL(url);
            setMessage('Export started, check your downloads folder.');
            setTimeout(() => setMessage(null), 2500);
        } catch (e) {
            setMessage('Export failed');
            setTimeout(() => setMessage(null), 2500);
        }
    };

    const handleImportJson = (text: string) => {
        try {
            const parsed = JSON.parse(text) as Record<string, unknown>;
            if (!parsed || typeof parsed !== 'object') throw new Error('Invalid JSON');

            const productLine = parsed.productLine || parsed.productionLine || parsed.product_line || parsed.productline || parsed.prodLine;
            const importedProduction = toArray<ProductionItem>(parsed.production || parsed.productionRows || parsed.production_table);
            const importedPowers = toArray<PowerItem>(parsed.powers || parsed.powerRows || parsed.power);
            const importedImports = toArray<ImportItem>(parsed.imports || parsed.importsList || parsed.imports_table);
            const importedChecklist = toArray<any>(parsed.checklist || parsed.checkList);

            if (!productLine && (!importedProduction.length && !importedPowers.length && !importedImports.length)) {
                throw new Error('No supported data found (productLine, production, powers, imports)');
            }

            setImportPreview({
                productLine: productLine && typeof productLine === 'object' ? productLine as Partial<ProductLine> : null,
                production: importedProduction,
                powers: importedPowers,
                imports: importedImports,
                checklist: importedChecklist,
            });
            setMessage('Preview loaded - press "Apply Import" to apply production/powers/imports to this production line.');
            setTimeout(() => setMessage(null), 4000);
        } catch (e: unknown) {
            setMessage('Import failed: ' + (e instanceof Error ? e.message : 'unknown'));
            setTimeout(() => setMessage(null), 4000);
        }
    };

    const onFileChange = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.currentTarget.files && event.currentTarget.files[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = () => handleImportJson(String(reader.result || ''));
        reader.readAsText(file);
    };

    return (
        <Modal isOpen={isOpen} onClose={onClose} title="Update production line" size="lg">
            <Modal.Body>
                <div id="successAlert" className={`alert alert-success ${message && message.startsWith('Import applied') ? '' : 'd-none'} fade`} role="alert">
                    {message && message.startsWith('Import applied') ? 'Import was successful!' : 'Import was successful!'}
                </div>
                <div id="errorAlert" className={`alert alert-danger ${message && message.startsWith('Import failed') ? '' : 'd-none'} fade`} role="alert">
                    {message && message.startsWith('Import failed') ? message : ''}
                </div>

                <form id="editProductionLineForm" onSubmit={(e) => { e.preventDefault(); handleSave(); }} className="row">
                    <h5>Production Line</h5>
                    <div className="mb-3 col-10">
                        <label htmlFor="productionLineName" className="form-label">Production Line Name</label>
                        <input id="productionLineName" name="productionLineName" type="text" value={title}
                               onChange={e => setTitle(e.target.value)} className="form-control" maxLength={45} required />
                    </div>
                    <div className="mb-3 col-12 col-md-2">
                        <label htmlFor="productionLineActiveToggle" className="form-label">Active</label>
                        <div>
                            <LegacyBootstrapToggle
                                id="productionLineActiveToggle"
                                checked={active}
                                onChange={setActive}
                                size="md"
                                ariaLabel="Production line active"
                                dataAttributes={{'data-pl-active': 1}}
                            />
                        </div>
                    </div>
                </form>

                <div className="row mb-3">
                    <h5>Import/Export</h5>
                    <div className="col-12 col-md-8 pe-0">
                        <input type="file" id="importFile" name="file" accept=".json" className="form-control rounded-0 rounded-start" onChange={onFileChange} />
                        <div className="invalid-feedback">Please select a valid JSON file.</div>
                    </div>
                    <div className="col-4 ps-0">
                        <button type="button" className="btn btn-primary w-100 rounded-0 rounded-end" id="exportButton" onClick={handleExport}>Export</button>
                    </div>
                </div>

                {importPreview && (
                    <div className="card mb-3">
                        <div className="card-body">
                            <h6 className="card-title">Import Preview</h6>
                            <div className="row">
                                <div className="col-6"><strong>Production rows:</strong> {importPreview.production?.length || 0}</div>
                                <div className="col-6"><strong>Power rows:</strong> {importPreview.powers?.length || 0}</div>
                            </div>
                            <div className="row mt-2">
                                <div className="col-6"><strong>Imports:</strong> {importPreview.imports?.length || 0}</div>
                                <div className="col-6"><strong>Checklist:</strong> {importPreview.checklist?.length || 0}</div>
                            </div>
                            {importPreview.productLine && importPreview.productLine.title && (
                                <div className="mt-2"><strong>Product line in file:</strong> {String(importPreview.productLine.title)}</div>
                            )}
                            <div className="d-flex gap-2 mt-3">
                                <button className="btn btn-success" onClick={() => {
                                    if (onImport) {
                                        onImport({
                                            production: importPreview.production || [],
                                            powers: importPreview.powers || [],
                                            imports: importPreview.imports || [],
                                            checklist: importPreview.checklist || [],
                                        });
                                    }
                                    setMessage('Import applied locally. Use Save to persist to server.');
                                    setTimeout(() => setMessage(null), 3000);
                                    setImportPreview(null);
                                }}>Apply Import</button>
                                <button className="btn btn-outline-secondary" onClick={() => setImportPreview(null)}>Cancel Preview</button>
                            </div>
                        </div>
                    </div>
                )}

                {message && <div className="alert alert-info mt-2">{message}</div>}

            </Modal.Body>
            <Modal.Footer>
                <button type="submit" form="editProductionLineForm" className="btn btn-primary" data-bs-toggle="tooltip"
                        data-bs-placement="top" data-bs-html="true"
                        title="This will update the production line with the new settings. Only needed if you changed the name or active status.">
                    Update Production Line
                </button>
            </Modal.Footer>
        </Modal>
    );
};

export default ProductionLineSettingsModal;