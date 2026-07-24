import React, {useEffect, useRef, useState} from 'react';
import Tooltip from "../Tooltip";
import LegacyBootstrapToggle from "../LegacyBootstrapToggle";
import {formatNumber} from "../../Utils/format";
import type {AppData, ProductionItem, Recipe} from "../../Types/global";

interface ChecklistStateItem {
    production_id: number;
    been_build: boolean;
    been_tested: boolean;
}

interface ChecklistSourceItem {
    production_id?: number;
    productionRow?: { row_id?: number };
    been_build?: boolean | number;
    beenBuild?: boolean | number;
    been_tested?: boolean | number;
    beenTested?: boolean | number;
}

type ChecklistProductionRow = ProductionItem & {
    recipe?: Recipe;
    row_id?: number;
    production_id?: number;
    quantity?: number;
    quantityPerMin?: number;
    item_class_name?: string;
};

interface Props {
    isOpen: boolean;
    onClose: () => void;
    appData: AppData;
    productionRows: ChecklistProductionRow[];
    onSave: (checklist: any[]) => void;
}

const buildStorageKey = (appData: AppData): string => {
    const productionLineId = appData?.productLine?.id || Number(new URL(window.location.href).searchParams.get('id')) || 0;
    return `pl-checklist-${productionLineId}`;
};

const ChecklistModal: React.FC<Props> = ({isOpen, onClose, appData, productionRows, onSave}) => {
    const offcanvasRef = useRef<HTMLDivElement | null>(null);
    const [rendered, setRendered] = useState<boolean>(isOpen);
    const hideTimeoutRef = useRef<number | null>(null);
    const [checks, setChecks] = useState<ChecklistStateItem[]>([]);
    const [filter, setFilter] = useState('');

    useEffect(() => {
        if (!isOpen) return;
        const storageKey = buildStorageKey(appData);

        let persisted: ChecklistSourceItem[] | null = null;
        try {
            const raw = localStorage.getItem(storageKey);
            if (raw) persisted = JSON.parse(raw) as ChecklistSourceItem[];
        } catch (e) {
            // Ignore invalid local checklist cache and fall back to appData.
        }

        const source = (persisted ?? (appData?.checklist || [])) as ChecklistSourceItem[];
        const existing = (source || []).map((check) => ({
            production_id: Number(check.production_id ?? check.productionRow?.row_id ?? 0),
            been_build: !!(check.been_build ?? check.beenBuild ?? false),
            been_tested: !!(check.been_tested ?? check.beenTested ?? false),
        }));

        const checkByProductionId = new Map<number, ChecklistStateItem>();
        existing.forEach((check) => {
            if (check.production_id) checkByProductionId.set(check.production_id, check);
        });

        const built: ChecklistStateItem[] = [];
        productionRows.forEach((row) => {
            const productionId = Number(row.id ?? row.row_id ?? row.production_id ?? 0);
            if (!productionId) return;
            built.push(checkByProductionId.get(productionId) || {
                production_id: productionId,
                been_build: false,
                been_tested: false,
            });
        });

        setChecks(built);
    }, [isOpen, appData, productionRows]);

    useEffect(() => {
        const el = offcanvasRef.current;
        if (isOpen) {
            if (hideTimeoutRef.current) {
                window.clearTimeout(hideTimeoutRef.current);
                hideTimeoutRef.current = null;
            }
            if (!rendered) setRendered(true);
            if (!el) return;

            el.classList.remove('offcanvas-mounted', 'offcanvas-closing');
            el.classList.add('offcanvas-opening');
            const raf = requestAnimationFrame(() => {
                el.classList.remove('offcanvas-opening');
                el.classList.add('offcanvas-mounted');
            });
            return () => cancelAnimationFrame(raf);
        }

        if (!isOpen && rendered) {
            if (el) {
                el.classList.remove('offcanvas-mounted', 'offcanvas-opening');
                el.classList.add('offcanvas-closing');
            }
            hideTimeoutRef.current = window.setTimeout(() => {
                setRendered(false);
                hideTimeoutRef.current = null;
            }, 380);
        }

        return () => {
            if (hideTimeoutRef.current) {
                window.clearTimeout(hideTimeoutRef.current);
                hideTimeoutRef.current = null;
            }
        };
    }, [isOpen, rendered]);

    const getRowRecipe = (row: ChecklistProductionRow | undefined) => {
        return row?.recipe ?? appData?.recipes?.find((recipe) => Number(recipe.id) === Number(row?.recipe_id));
    };

    const buildingAmount = (row: ChecklistProductionRow | undefined) => {
        const numericProductQuantity = Number(row?.product_quantity ?? row?.quantity ?? row?.quantityPerMin ?? 0);
        const rawClock = (row?.clock_speed === '' || row?.clock_speed === undefined || row?.clock_speed === null) ? 100 : Number(row.clock_speed);
        const clockValue = Math.min(250, Math.max(0, rawClock));
        const recipe = getRowRecipe(row);
        const exportAmountPerMin = Number(recipe?.export_amount_per_min ?? row?.export_amount_per_min ?? 0);
        if (!exportAmountPerMin) return 0;
        const useSomersloop = !!row?.use_somersloop;
        return numericProductQuantity / (exportAmountPerMin * (clockValue / 100)) / (useSomersloop ? 2 : 1);
    };

    const updateCheck = React.useCallback((productionId: number, field: 'been_build' | 'been_tested', value: boolean) => {
        setChecks((previousChecks) => {
            const updated = previousChecks.map((check) => check.production_id === productionId ? {...check, [field]: value} : check);
            try {
                localStorage.setItem(buildStorageKey(appData), JSON.stringify(updated));
            } catch (e) {
                // Local persistence is helpful, but the parent state remains authoritative for saving.
            }

            try { onSave(updated); } catch (e) { /* ignore */ }
            try {
                if (appData) (appData as unknown as { checklist: ChecklistStateItem[] }).checklist = updated;
                if (window.appData) (window.appData as unknown as { checklist: ChecklistStateItem[] }).checklist = updated;
            } catch (e) { /* ignore */ }
            return updated;
        });
    }, [appData, onSave]);

    const visibleChecks = checks.filter((check) => {
        if (!filter) return true;
        const search = filter.toLowerCase();
        const row = productionRows.find((candidate) => Number(candidate.id ?? candidate.row_id ?? 0) === check.production_id);
        const recipeName = (row?.recipe_name || row?.recipe?.name || '').toString().toLowerCase();
        return recipeName.includes(search);
    });

    if (!rendered) return null;

    return (
        <>
            <style>{`
                .offcanvas .toggle { width: 42px !important; height: 32px !important; }
                .offcanvas .toggle .btn { padding: 0.25rem 0.4rem; }
                .offcanvas .card-title { font-size: 1rem; }
                .pl-help-icon { cursor: pointer; color: rgba(0,0,0,0.45); }

                /* Custom offcanvas animation */
                .custom-offcanvas { transform: translateX(12px); opacity: 0; transition: transform 360ms ease, opacity 360ms ease; }
                .custom-offcanvas.offcanvas-opening { transform: translateX(12px); opacity: 0; }
                .custom-offcanvas.offcanvas-mounted { transform: translateX(0); opacity: 1; }
                .custom-offcanvas.offcanvas-closing { transform: translateX(12px); opacity: 0; }

                .offcanvas-backdrop.fade { opacity: 0; transition: opacity 360ms ease; }
                .offcanvas-backdrop.fade.show { opacity: 0.45; }
            `}</style>

            <div className="offcanvas-backdrop fade show" onClick={onClose} />
            <div ref={offcanvasRef} className="offcanvas offcanvas-end show custom-offcanvas" data-bs-scroll="true" data-bs-backdrop="false" tabIndex={-1} id="Checklist" aria-labelledby="offcanvasChecklist" aria-modal="true" role="dialog">
                <div className="offcanvas-header pb-1">
                    <h5 className="offcanvas-title" id="offcanvasChecklist">Checklist</h5>
                    <button type="button" className="btn-close" aria-label="Close" onClick={onClose}></button>
                </div>

                <div className="input-group p-3 pt-0">
                    <input type="search" className="form-control mt-2" id="searchChecklist" placeholder="Search" value={filter} onChange={e => setFilter(e.target.value)} />
                    <button className="btn btn-primary mt-2" id="resetSearchChecklist" onClick={() => setFilter('')}><i className="fa-solid fa-undo" aria-hidden="true"></i></button>
                </div>

                <div className="offcanvas-body overflow-y-auto">
                    <div className="d-none" id="checkListData">{/* legacy data placeholder */}</div>
                    {visibleChecks.length === 0 && <div className="text-muted p-3">No checklist items</div>}
                    {visibleChecks.map((check) => {
                        const row = productionRows.find((candidate) => Number(candidate.id ?? candidate.row_id ?? 0) === check.production_id);
                        const recipe = getRowRecipe(row);
                        const recipeName = row?.recipe_name || recipe?.name || 'Unknown';
                        const qty = row?.product_quantity ?? row?.quantity ?? row?.quantityPerMin ?? 0;
                        const building = (row?.building_name || recipe?.building?.[0]?.name) || '';
                        const getIcon = (className?: string) => {
                            if (!className) return '';
                            return `/image/items/${className.toLowerCase().replaceAll('_', '-')}_256.png`;
                        };
                        const iconSrc = getIcon(recipe?.products?.[0]?.class_name || row?.item_class_name_1 || row?.item_class_name || '');
                        return (
                            <div key={check.production_id} className="card mb-2" id={`check-${check.production_id}`}>
                                <div className="card-body p-3">
                                    <div className="d-flex align-items-center">
                                        {iconSrc && <img src={iconSrc} alt="" className="pl-item-icon me-2" style={{width: 32, height: 32}} loading="lazy" />}
                                        <h5 className="card-title recipeName mb-0">{recipeName}</h5>
                                    </div>
                                    <p className="card-text mt-2"><span className="productionAmount">{formatNumber(qty)}</span> per min - <span className="buildingAmount">{formatNumber(buildingAmount(row))}</span> <span className="buildingName">{building}</span></p>
                                    <div style={{display: 'flex', justifyContent: 'space-between', alignItems: 'center'}}>
                                        <div style={{display: 'flex', alignItems: 'center', gap: '0.5rem'}}>
                                            <LegacyBootstrapToggle
                                                id={`build-${check.production_id}`}
                                                checked={!!check.been_build}
                                                onChange={(checked) => updateCheck(check.production_id, 'been_build', checked)}
                                                ariaLabel="Mark as built"
                                                dataAttributes={{'data-checklist-toggle': 1}}
                                            />
                                            <label htmlFor={`build-${check.production_id}`} className="mb-0">Build</label>
                                            <Tooltip content="Mark this recipe as built. You've constructed this part of the line.">
                                                <i className="fa-solid fa-circle-question ms-1 pl-help-icon" />
                                            </Tooltip>
                                        </div>

                                        <div style={{display: 'flex', alignItems: 'center', gap: '0.5rem'}}>
                                            <LegacyBootstrapToggle
                                                id={`tested-${check.production_id}`}
                                                checked={!!check.been_tested}
                                                onChange={(checked) => updateCheck(check.production_id, 'been_tested', checked)}
                                                ariaLabel="Mark as tested"
                                                dataAttributes={{'data-checklist-toggle': 1}}
                                            />
                                            <label htmlFor={`tested-${check.production_id}`} className="mb-0">Tested</label>
                                            <Tooltip content="Mark as tested. Production and outputs have been verified.">
                                                <i className="fa-solid fa-circle-question ms-1 pl-help-icon" />
                                            </Tooltip>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                </div>

                <div className="p-3 d-flex justify-content-end gap-2 border-top">
                    <button className="btn btn-secondary" onClick={onClose}>Close</button>
                </div>

            </div>
        </>
    );
};

export default ChecklistModal;