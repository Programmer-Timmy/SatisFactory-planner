import React, {useEffect, useRef} from 'react';
import Modal from "../Modal";
// Import the Visualization class
import {createVisualizationFromData} from "../../Utils/VisualizationAdapter";
import Tooltip from "../Tooltip";

interface Props {
    isOpen: boolean;
    onClose: () => void;
    appData: any;
    productionRows: any[];
    importsList: any[];
    recipeMap: Record<number, any>;
}

const VisualizationPanel: React.FC<Props> = ({isOpen, onClose, appData, productionRows, importsList, recipeMap}) => {
    const vizRef = useRef<any | null>(null);

    useEffect(() => {
        if (!isOpen) return;
        if (!appData) return;

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
            try {
                vizRef.current.update();
            } catch (e) {
                console.warn(e);
            }
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
                        <Tooltip
                            content="Toggle visibility of the checklist overlay on the visualization, which shows which items are being produced/consumed at a glance.">
                            <label className="form-check form-check-inline">
                                <input id="showChecklist" className="form-check-input" type="checkbox" defaultChecked/>
                                <span className="form-check-label">Show checklist</span>
                            </label>
                        </Tooltip>
                        <Tooltip
                            content="Toggle visibility of the import flows on the visualization, which indicate how much of each product is being imported from other production lines or miners.">
                            <label className="form-check form-check-inline ms-2">
                                <input id="import" className="form-check-input" type="checkbox" defaultChecked/>
                                <span className="form-check-label">Show imports</span>
                            </label>
                        </Tooltip>
                        <Tooltip
                            content="Toggle visibility of the export flows on the visualization, which indicate how much of each product is available to be exported to other production lines.">
                            <label className="form-check form-check-inline ms-2">
                                <input id="export" className="form-check-input" type="checkbox"/>
                                <span className="form-check-label">Show exports</span>
                            </label>
                        </Tooltip>
                    </div>

                    <div className="ms-auto">
                        <button id="refresh" className="btn btn-outline-primary me-2">Refresh</button>
                        <button className="btn btn-secondary" onClick={onClose}>Close</button>
                    </div>
                </div>
            </Modal.Footer>
        </Modal>
    );
};

export default VisualizationPanel;
