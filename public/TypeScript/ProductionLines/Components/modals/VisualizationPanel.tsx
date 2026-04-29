import React, {useEffect, useRef} from 'react';
import Modal from "../Modal";

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
    const [loading, setLoading] = React.useState<boolean>(false);
    const [progress, setProgress] = React.useState<number>(0);

    useEffect(() => {
        if (!isOpen) return;
        if (!appData) return;

        let cancelled = false;
        setLoading(true);
        setProgress(0);
        (async () => {
            try {
                const mod = await import('../../Utils/VisualizationAdapter');
                if (cancelled) return;
                if (mod && typeof mod.createVisualizationFromData === 'function') {
                    const viz = (mod.createVisualizationFromData as any)(appData, productionRows, importsList, recipeMap, { onProgress: (p:number) => { if (!cancelled) setProgress(Math.max(0, Math.min(100, Math.round(p)))) } });
                    vizRef.current = viz;
                    if (viz && typeof viz.ready === 'object' && typeof (viz.ready as Promise<void>).then === 'function') {
                        try {
                            await (viz.ready as Promise<void>);
                            try { if (typeof viz.update === 'function') viz.update(); } catch (e) { /* ignore */ }
                            setTimeout(() => {
                                try { if (!cancelled && typeof viz.update === 'function') viz.update(); } catch (e) { /* ignore */ }
                            }, 250);
                        } catch (e) {
                            console.warn('Visualization ready promise rejected', e);
                        }
                    }
                }
            } catch (e) {
                console.error('Failed to create Visualization', e);
            } finally {
                if (!cancelled) setLoading(false);
            }
        })();

        return () => {
            cancelled = true;
            try {
                const graph = document.getElementById('graph');
                if (graph) graph.innerHTML = '';
            } catch {}
            vizRef.current = null;
            setLoading(false);
            setProgress(0);
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
                <div style={{position: 'relative', width: '100%', height: '100%'}}>
                    {/* Graph container always present to ensure cytoscape can mount */}
                    <div id="graph" style={{width: '100%', height: '100%', overflow: 'hidden', minHeight: 300}} className={loading ? 'd-none' : ''}/>

                    <div className={`d-flex justify-content-center align-items-center flex-column px-5 ${loading ? '' : 'd-none'}`}
                         style={{height: '100%'}}>
                        <div className="spinner-border text-primary" role="status">
                            <span className="visually-hidden">Loading...</span>
                        </div>
                        <div className="progress w-100 mt-3">
                            <div className="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                 role="progressbar" id="loadingProgressGraph" style={{width: `${progress}%`}} aria-valuenow={progress} aria-valuemin={0} aria-valuemax={100} />
                        </div>
                    </div>
                </div>
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
