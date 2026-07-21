import React, {FC, useMemo, useState} from 'react';
import ImportResolutionModal, {ImportSourceCandidate, ImportSourceSelection} from './ImportResolutionModal';
import Tooltip from "../Tooltip";
import {formatNumber} from '../../Utils/format';

interface ImportsCardProps {
    itemName: string;
    itemClass: string;
    amount: number;
    itemId: number;
    producingRecipes: any[];
    sourceCandidates: ImportSourceCandidate[];
    sourceSelections: ImportSourceSelection[];
    onAddRecipe: (recipeId: number, itemId: number) => void;
    onChangeSources: (sources: ImportSourceSelection[]) => void;
}

const ImportsCard: FC<ImportsCardProps> = ({
    itemName,
    itemClass,
    amount,
    itemId,
    producingRecipes,
    sourceCandidates,
    sourceSelections,
    onAddRecipe,
    onChangeSources
}) => {
    const [showModal, setShowModal] = useState(false);
    const canResolve = producingRecipes.length > 0 || sourceCandidates.length > 0 || sourceSelections.length > 0;

    const candidatesByKey = useMemo(() => {
        const map = new Map<string, ImportSourceCandidate>();
        for (const candidate of sourceCandidates) {
            map.set(`${Number(candidate.production_line_id)}-${Number(candidate.items_id)}`, candidate);
        }
        return map;
    }, [sourceCandidates]);

    const sourceSummaries = sourceSelections
        .filter(source => source.itemId === itemId && source.requestedAmount > 0)
        .map(source => {
            const candidate = candidatesByKey.get(`${source.exportingProductionLineId}-${source.itemId}`);
            const assignedAmount = Math.max(0, Math.min(
                Number(source.requestedAmount || 0),
                candidate ? Number(candidate.available_amount || 0) : Number(source.assignedAmount || 0)
            ));
            return {
                ...source,
                assignedAmount,
                title: source.productionLineTitle || candidate?.production_line_title || 'Unknown line',
                shortAmount: Math.max(0, Number(source.requestedAmount || 0) - assignedAmount)
            };
        });

    const sourcedAmount = sourceSummaries.reduce((total, source) => total + source.assignedAmount, 0);
    const unresolvedAmount = Math.max(0, Number(amount || 0) - sourcedAmount);
    const shortSourceAmount = sourceSummaries.reduce((total, source) => total + source.shortAmount, 0);

    const handleSelectRecipe = (recipe: any) => {
        onAddRecipe(recipe.id, itemId);
    };

    return (
        <>
            <div className="pl-row pl-import-row is-collapsed" data-row-index="0">
                <div className="d-flex justify-content-between gap-2" aria-hidden="true">
                    <div className={"pl-import-collapsed flex-grow-1"}>
                        <img className="pl-item-icon" data-role="import-icon-collapsed" loading="lazy"
                             src={`/image/items/${itemClass.toLowerCase().replaceAll("_", "-")}_256.png`} alt=""/>
                        <div className="pl-import-collapsed-main">
                            <div className="pl-import-collapsed-name" data-role="import-name-collapsed">{itemName}</div>
                            <div className="pl-import-collapsed-qty">
                                <span className="pl-number" data-role="import-qty-collapsed">{formatNumber(amount)}</span>
                                <span className="text-muted">/min</span>
                            </div>
                            {sourceSummaries.length > 0 && (
                                <div className="d-flex flex-wrap gap-1 mt-1">
                                    {sourceSummaries.map(source => (
                                        <span
                                            key={`${source.exportingProductionLineId}-${source.itemId}`}
                                            className={`badge rounded-pill fw-normal d-inline-flex flex-wrap align-items-center gap-1 text-wrap text-start pl-import-source-badge ${source.shortAmount > 0 ? 'is-warning' : ''}`}
                                            style={{whiteSpace: 'normal', overflowWrap: 'anywhere', maxWidth: '100%'}}
                                            title={`${source.title}: ${formatNumber(source.assignedAmount)}/min`}
                                        >
                                            <span>{source.title}</span>
                                            <span className="pl-number">{formatNumber(source.assignedAmount)}/min</span>
                                        </span>
                                    ))}
                                </div>
                            )}
                            {(sourceSummaries.length > 0 && (unresolvedAmount > 0 || shortSourceAmount > 0)) && (
                                <div className="small text-warning mt-1">
                                    {unresolvedAmount > 0 ? `${formatNumber(unresolvedAmount)}/min still external` : null}
                                    {unresolvedAmount > 0 && shortSourceAmount > 0 ? ' · ' : null}
                                    {shortSourceAmount > 0 ? `${formatNumber(shortSourceAmount)}/min over source capacity` : null}
                                </div>
                            )}
                        </div>
                    </div>
                    {canResolve && (
                        <Tooltip content="Resolve this import from another production line or add a local recipe">
                            <button
                                className="btn btn-sm btn-outline-primary align-self-start"
                                onClick={() => setShowModal(true)}
                                title={`Resolve import for ${itemName}`}
                            >
                                <i className="fa-solid fa-plus"></i>
                            </button>
                        </Tooltip>
                    )}
                </div>
            </div>

            <ImportResolutionModal
                isOpen={showModal}
                onClose={() => setShowModal(false)}
                itemName={itemName}
                amount={amount}
                producingRecipes={producingRecipes}
                sourceCandidates={sourceCandidates}
                sourceSelections={sourceSelections.filter(source => source.itemId === itemId)}
                onChangeSources={onChangeSources}
                onSelectRecipe={handleSelectRecipe}
            />
        </>
    );
}

export default ImportsCard;