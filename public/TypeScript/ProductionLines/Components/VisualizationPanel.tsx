import React, { useEffect, useRef } from 'react';
import Modal from "./Modal";
// Import the Visualization class
import { createVisualizationFromData } from "../Utils/VisualizationAdapter";

interface Props {
    isOpen: boolean;
    onClose: () => void;
    appData: any;
    productionRows: any[];
    importsList: any[];
    recipeMap: Record<number, any>;
}

const VisualizationPanel: React.FC<Props> = ({ isOpen, onClose, appData, productionRows, importsList, recipeMap }) => {
    const vizRef = useRef<any | null>(null);

    useEffect(() => {
        if (!isOpen) return;
        if (!appData) return;

        // Build a lightweight TableHandler-like adapter expected by Visualization
        const tableHandler: any = {
            productionTableRows: productionRows.map((r: any, i: number) => {
                const recipe = recipeMap[r.recipe_id] || null;
                return {
                    recipe,
                    quantity: Number(r.product_quantity) || 0,
                    product: (recipe && (recipe.itemName || recipe.name)) || r.item_name_1 || '',
                    recipeSetting: { clockSpeed: r.clock_speed === '' ? 100 : Number(r.clock_speed), useSomersloop: !!r.use_somersloop },
                    productionImports: [],
                    imports: [],
                    exportPerMin: 0,
                    extraCells: {}
                };
            }),
            importsTableRows: (importsList || []).map((imp: any) => ({ product: imp.name, quantity: imp.ammount, itemId: imp.items_id })),
            checklist: { getChecklist: () => [] },
            // Minimal API surface used by Visualization
        };

        // instantiate visualization
        try {
            vizRef.current = createVisualizationFromData(appData, productionRows, importsList, recipeMap);
        } catch (e) {
            console.error('Failed to create Visualization', e);
        }

        return () => {
            try {
                // Try to cleanup DOM overlays if any
                const graph = document.getElementById('graph');
                if (graph) {
                    graph.innerHTML = '';
                }
            } catch (e) {
                // ignore
            }
            vizRef.current = null;
        };
    }, [isOpen, appData, productionRows, importsList, recipeMap]);

    useEffect(() => {
        if (!isOpen) return;
        if (vizRef.current && typeof vizRef.current.update === 'function') {
            try { vizRef.current.update(); } catch (e) { console.warn(e); }
        }
    }, [productionRows, importsList, recipeMap, isOpen]);

    return (
        <Modal isOpen={isOpen} onClose={onClose} title="Visualization" size="xl" fullscreen>
            <Modal.Body className="p-0">
                <div id="graph" style={{width: '100%', height: '100%', overflow: 'hidden'}}/>
            </Modal.Body>
            <Modal.Footer>
                <div className="d-flex gap-3 mb-2 align-items-center">
                    <div>
                        <label className="form-check form-check-inline">
                            <input id="showChecklist" className="form-check-input" type="checkbox" defaultChecked />
                            <span className="form-check-label">Show checklist</span>
                        </label>
                        <label className="form-check form-check-inline ms-2">
                            <input id="import" className="form-check-input" type="checkbox" defaultChecked />
                            <span className="form-check-label">Show imports</span>
                        </label>
                        <label className="form-check form-check-inline ms-2">
                            <input id="export" className="form-check-input" type="checkbox" />
                            <span className="form-check-label">Show exports</span>
                        </label>
                    </div>

                    <div className="ms-auto">
                        <button id="refresh" className="btn btn-outline-primary me-2">Refresh</button>
                    </div>
                </div>
            </Modal.Footer>
        </Modal>
    );
};

export default VisualizationPanel;
