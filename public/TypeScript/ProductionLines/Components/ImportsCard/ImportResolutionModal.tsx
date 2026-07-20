import React, {FC} from 'react';
import Modal from '../Modal';
import {formatNumber} from '../../Utils/format';

export interface ImportSourceCandidate {
    production_line_id: number;
    production_line_title: string;
    items_id: number;
    item_name: string;
    item_class_name: string;
    output_amount: number;
    assigned_amount: number;
    available_amount: number;
}

export interface ImportSourceSelection {
    exportingProductionLineId: number;
    itemId: number;
    requestedAmount: number;
    assignedAmount: number;
    productionLineTitle?: string;
    itemName?: string;
    itemClassName?: string;
}

interface ImportResolutionModalProps {
    isOpen: boolean;
    onClose: () => void;
    itemName: string;
    amount: number;
    producingRecipes: any[];
    sourceCandidates: ImportSourceCandidate[];
    sourceSelections: ImportSourceSelection[];
    onSelectRecipe: (recipe: any) => void;
    onChangeSources: (sources: ImportSourceSelection[]) => void;
}

const sourceKey = (lineId: number, itemId: number) => `${lineId}-${itemId}`;

const toNumber = (value: unknown) => {
    const parsed = Number(value ?? 0);
    return Number.isFinite(parsed) ? parsed : 0;
};

const getIcon = (className?: string) => {
    if (!className) return '';
    return `/image/items/${String(className).toLowerCase().replace(/_/g, '-')}_256.png`;
};

const ImportResolutionModal: FC<ImportResolutionModalProps> = ({
    isOpen,
    onClose,
    itemName,
    amount,
    producingRecipes,
    sourceCandidates,
    sourceSelections,
    onSelectRecipe,
    onChangeSources
}) => {
    const getSelection = (candidate: ImportSourceCandidate) => sourceSelections.find(source =>
        source.exportingProductionLineId === Number(candidate.production_line_id) &&
        source.itemId === Number(candidate.items_id)
    );

    const getAssignedAmount = (selection: ImportSourceSelection, candidate?: ImportSourceCandidate) => {
        const available = candidate ? toNumber(candidate.available_amount) : Number.POSITIVE_INFINITY;
        return Math.max(0, Math.min(toNumber(selection.requestedAmount), available));
    };

    const assignedTotal = sourceSelections.reduce((total, selection) => {
        const candidate = sourceCandidates.find(source =>
            Number(source.production_line_id) === selection.exportingProductionLineId &&
            Number(source.items_id) === selection.itemId
        );
        return total + getAssignedAmount(selection, candidate);
    }, 0);

    const replaceSelection = (nextSelection: ImportSourceSelection) => {
        const nextKey = sourceKey(nextSelection.exportingProductionLineId, nextSelection.itemId);
        const next = sourceSelections.filter(source => sourceKey(source.exportingProductionLineId, source.itemId) !== nextKey);
        if (nextSelection.requestedAmount > 0) {
            next.push(nextSelection);
        }
        onChangeSources(next);
    };

    const setCandidateAmount = (candidate: ImportSourceCandidate, requestedAmount: number) => {
        const cleanRequested = Math.max(0, requestedAmount);
        const assignedAmount = Math.min(cleanRequested, toNumber(candidate.available_amount));
        replaceSelection({
            exportingProductionLineId: Number(candidate.production_line_id),
            itemId: Number(candidate.items_id),
            requestedAmount: cleanRequested,
            assignedAmount,
            productionLineTitle: candidate.production_line_title,
            itemName: candidate.item_name,
            itemClassName: candidate.item_class_name
        });
    };

    const fillCandidate = (candidate: ImportSourceCandidate) => {
        const current = getSelection(candidate);
        const currentAssigned = current ? getAssignedAmount(current, candidate) : 0;
        const assignedWithoutCurrent = assignedTotal - currentAssigned;
        const remaining = Math.max(0, amount - assignedWithoutCurrent);
        setCandidateAmount(candidate, Math.min(toNumber(candidate.available_amount), remaining));
    };

    const fillAll = () => {
        let remaining = Math.max(0, amount);
        const next: ImportSourceSelection[] = [];
        for (const candidate of sourceCandidates) {
            if (remaining <= 0) break;
            const requestedAmount = Math.min(toNumber(candidate.available_amount), remaining);
            if (requestedAmount <= 0) continue;
            next.push({
                exportingProductionLineId: Number(candidate.production_line_id),
                itemId: Number(candidate.items_id),
                requestedAmount,
                assignedAmount: requestedAmount,
                productionLineTitle: candidate.production_line_title,
                itemName: candidate.item_name,
                itemClassName: candidate.item_class_name
            });
            remaining -= requestedAmount;
        }
        onChangeSources(next);
    };

    const calculateBuildingsNeeded = (recipe: any, targetAmount: number) => {
        const exportPerMin = recipe.export_amount_per_min || 0;
        if (exportPerMin <= 0) return 0;
        return Math.ceil((targetAmount / exportPerMin) * 100) / 100;
    };

    const handleSelectRecipe = (recipe: any) => {
        onSelectRecipe(recipe);
        onClose();
    };

    return (
        <Modal
            isOpen={isOpen}
            onClose={onClose}
            title={`Resolve ${itemName} import`}
            size="lg"
        >
            <Modal.Body>
                <style>{`
                    .import-source-card, .recipe-card-button {
                        transition: background-color 150ms ease-in-out, border-color 150ms ease-in-out;
                    }
                    .import-source-card:hover, .recipe-card-button:hover {
                        background-color: rgba(255, 138, 71, 0.12);
                        border-color: #ff8a47 !important;
                    }
                    .import-source-actions { display:flex; gap:6px; flex-wrap:wrap; }
                    .import-source-control-tuple { display:flex; align-items:center; gap:8px; flex-wrap:wrap; justify-content:flex-end; }
                    .import-source-input { flex:1 1 110px; min-width:90px; max-width:140px; }
                    .recipe-flow-icon { width:26px; height:26px; }
                `}</style>

                <div className="d-flex align-items-center justify-content-between gap-2 mb-2">
                    <div>
                        <div className="fw-semibold">Import from production lines</div>
                        <div className="text-muted small">Required: <span className="pl-number">{formatNumber(amount)}</span>/min</div>
                    </div>
                    {sourceCandidates.length > 0 && (
                        <div className="import-source-actions">
                            <button type="button" className="btn btn-sm btn-outline-primary" onClick={fillAll}>Fill all</button>
                            <button type="button" className="btn btn-sm btn-outline-secondary" onClick={() => onChangeSources([])}>Clear all</button>
                        </div>
                    )}
                </div>

                {sourceCandidates.length === 0 ? (
                    <div className="text-muted small border rounded-2 p-2 mb-3">No active production line currently exports {itemName}.</div>
                ) : (
                    <div className="d-flex flex-column gap-2 mb-3">
                        {sourceCandidates.map((candidate) => {
                            const selection = getSelection(candidate);
                            const requested = selection ? toNumber(selection.requestedAmount) : 0;
                            const assigned = selection ? getAssignedAmount(selection, candidate) : 0;
                            const available = toNumber(candidate.available_amount);
                            const isShort = requested > assigned;

                            return (
                                <div key={sourceKey(Number(candidate.production_line_id), Number(candidate.items_id))}
                                     className="import-source-card border rounded-2 p-2">
                                    <div className="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                        <div className="d-flex align-items-center gap-2 min-w-0">
                                            <img src={getIcon(candidate.item_class_name)} alt="" loading="lazy" style={{width: 28, height: 28}} />
                                            <div className="min-w-0">
                                                <div className="fw-semibold text-truncate">{candidate.production_line_title}</div>
                                                <div className="text-muted small">
                                                    Available <span className="pl-number">{formatNumber(available)}</span>/min
                                                    {isShort && <span className="text-warning ms-2">Short {formatNumber(requested - assigned)}/min</span>}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="import-source-control-tuple">
                                            <input
                                                type="number"
                                                min={0}
                                                step="0.01"
                                                className="form-control form-control-sm import-source-input"
                                                value={requested || ''}
                                                onChange={(event) => setCandidateAmount(candidate, toNumber(event.target.value))}
                                                placeholder="0"
                                            />
                                            <button type="button" className="btn btn-sm btn-outline-primary" onClick={() => fillCandidate(candidate)}>Fill</button>
                                            <button type="button" className="btn btn-sm btn-outline-secondary" onClick={() => setCandidateAmount(candidate, 0)}>Clear</button>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}

                <div className="fw-semibold mb-2">Add production recipe</div>
                {producingRecipes.length === 0 ? (
                    <div className="text-muted small border rounded-2 p-2">No recipe in this save can produce {itemName}.</div>
                ) : (
                    <div className="d-flex flex-column gap-2">
                        {producingRecipes.map((recipe) => {
                            const buildingsNeeded = calculateBuildingsNeeded(recipe, amount);
                            return (
                                <div
                                    key={recipe.id}
                                    className="recipe-card-button text-start d-flex align-items-center justify-content-between border border-primary rounded-2 p-2"
                                    onClick={() => handleSelectRecipe(recipe)}
                                    style={{cursor: 'pointer'}}
                                    role="button"
                                >
                                    <div className="d-flex flex-column min-w-0">
                                        <div className="mb-2 fw-semibold">{recipe.name}</div>
                                        <div className="d-flex align-items-center mt-1 flex-wrap recipe-visuals" style={{gap: 4}}>
                                            {recipe.ingredients && recipe.ingredients.map((ing: any) => (
                                                <div key={ing.id} className="d-flex align-items-center recipe-ingredient" style={{gap: 2}}>
                                                    <img src={getIcon(ing.class_name)} title={ing.name} className="img-fluid recipe-flow-icon" loading="lazy" alt="" />
                                                    <small className="text-muted">{formatNumber(ing.quantity)}</small>
                                                </div>
                                            ))}
                                            {(recipe.ingredients && recipe.ingredients.length) ? <i className="fa-solid fa-arrow-right" style={{fontSize: 12}}/> : null}
                                            {recipe.building && recipe.building[0] ? (
                                                <img src={getIcon(String(recipe.building[0].class_name).replace(/build/i, 'desc'))} title={recipe.building[0].name} className="img-fluid recipe-flow-icon" loading="lazy" alt="" />
                                            ) : null}
                                            {(recipe.products && recipe.products.length) ? <i className="fa-solid fa-arrow-right" style={{fontSize: 12}}/> : null}
                                            {recipe.products && recipe.products.map((prod: any) => (
                                                <div key={prod.id} className="d-flex align-items-center recipe-product" style={{gap: 2}}>
                                                    <img src={getIcon(prod.class_name)} title={prod.name} className="img-fluid recipe-flow-icon" loading="lazy" alt="" />
                                                    <small className="text-muted">{formatNumber(prod.quantity)}</small>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                    <div className="d-flex flex-column align-items-center gap-1 ms-3" style={{minWidth: '60px'}}>
                                        {recipe.building && recipe.building[0] && (
                                            <>
                                                <img src={getIcon(String(recipe.building[0].class_name).replace(/build/i, 'desc'))} title={recipe.building[0].name} style={{width: 32, height: 32}} loading="lazy" alt="" />
                                                <small className="text-muted text-center fw-bold">{formatNumber(buildingsNeeded)}</small>
                                            </>
                                        )}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </Modal.Body>
        </Modal>
    );
};

export default ImportResolutionModal;