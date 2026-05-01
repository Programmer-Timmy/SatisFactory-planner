import React, {useEffect, useState, useRef} from 'react';
import Tooltip from "../Tooltip";

interface Props {
    isOpen: boolean;
    onClose: () => void;
    appData: any;
    productionRows: any[];
    onSave: (checklist: any[]) => void;
}

const ChecklistModal: React.FC<Props> = ({isOpen, onClose, appData, productionRows, onSave}) => {
    const offcanvasRef = useRef<HTMLDivElement | null>(null);
    const [rendered, setRendered] = useState<boolean>(isOpen);
    const hideTimeoutRef = useRef<number | null>(null);
    const [checks, setChecks] = useState<{production_id:number, been_build:boolean, been_tested:boolean}[]>([]);
    const [filter, setFilter] = useState('');
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!isOpen) return;
        const productionLineId = appData?.productLine?.id || Number(new URL(window.location.href).searchParams.get('id')) || 0;
        const storageKey = `pl-checklist-${productionLineId}`;

        // Prefer persisted localStorage copy (so toggles survive close/reopen), otherwise use appData.checklist
        let persisted: any[] | null = null;
        try {
            const raw = localStorage.getItem(storageKey);
            if (raw) persisted = JSON.parse(raw);
        } catch (e) { /* ignore */ }

        const source = persisted ?? (appData?.checklist || []);

        const existing = (source || []).map((c: any) => ({
            production_id: Number(c.production_id ?? c.productionRow?.row_id ?? 0),
            been_build: !!(c.been_build ?? c.beenBuild ?? c.beenBuild ?? false),
            been_tested: !!(c.been_tested ?? c.beenTested ?? c.beenTested ?? false)
        }));

        const map = new Map<number, {production_id:number, been_build:boolean, been_tested:boolean}>();
        existing.forEach((e: any) => { if (e.production_id) map.set(e.production_id, e); });

        const built: any[] = [];
        productionRows.forEach((r: any) => {
            const pid = Number(r.id ?? r.row_id ?? r.production_id ?? 0);
            if (!pid) return; // skip rows without id
            if (map.has(pid)) {
                built.push(map.get(pid));
            } else {
                built.push({production_id: pid, been_build: false, been_tested: false});
            }
        });

        setChecks(built);
    }, [isOpen, appData, productionRows]);

    // Manage mount state and animate the offcanvas from the right
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
            // match transition (360ms)
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

    const updateCheck = React.useCallback((production_id:number, field: 'been_build'|'been_tested', value:boolean) => {
        setChecks(prev => {
            const updated = prev.map(c => c.production_id === production_id ? {...c, [field]: value} : c);
            // Persist to localStorage so closing + reopening the offcanvas keeps state
            try {
                const productionLineId = appData?.productLine?.id || Number(new URL(window.location.href).searchParams.get('id')) || 0;
                const storageKey = `pl-checklist-${productionLineId}`;
                localStorage.setItem(storageKey, JSON.stringify(updated));
            } catch (e) { /* ignore */ }

            // Immediately reflect changes in parent app state so saveProductionLine will include them
            try { onSave(updated); } catch (e) { /* ignore */ }
            try {
                if (appData) (appData as any).checklist = updated;
                if ((window as any).appData) (window as any).appData.checklist = updated;
            } catch (e) { /* ignore */ }
            return updated;
        });
    }, [appData, onSave]);

    useEffect(() => {
        if (!isOpen) return;
        const selector = '#Checklist input[data-checklist-toggle="1"]';
        const $ = (window as any).$;
        try {
            // Run init after DOM updates to ensure inputs exist (handles filter changes)
            setTimeout(() => {
                try {
                    if ($ && $.fn && $.fn.bootstrapToggle) {
                        // destroy any previous instances to avoid duplicates
                        $(selector).each(function () {
                            // @ts-ignore
                            try { if ($(this).data('bs.toggle')) $(this).bootstrapToggle('destroy'); } catch (e) { /* ignore */ }
                        });
                        $(selector).bootstrapToggle();

                        // Attach legacy jQuery change handler to detect toggle changes
                        $(selector).off('.checklist').on('change.checklist', function () {
                            try {
                                // @ts-ignore
                                const el = this as HTMLInputElement;
                                const m = el.id?.match(/-(\d+)$/);
                                const pid = m ? Number(m[1]) : 0;
                                const field = el.id?.startsWith('build-') ? 'been_build' : 'been_tested';
                                const checked = !!$(el).prop('checked');
                                // small delay to let plugin finish its animation/state
                                setTimeout(() => {
                                    updateCheck(pid, field as any, checked);
                                }, 60);
                            } catch (e) { /* ignore */ }
                        });
                    } else {
                        // Fallback: attach native change listeners
                        const els = Array.from(document.querySelectorAll(selector));
                        els.forEach((el) => {
                            const handler = (ev: Event) => {
                                try {
                                    const input = ev.currentTarget as HTMLInputElement;
                                    const m = input.id.match(/-(\d+)$/);
                                    const pid = m ? Number(m[1]) : 0;
                                    const field = input.id.startsWith('build-') ? 'been_build' : 'been_tested';
                                    // small timeout
                                    setTimeout(() => {
                                        updateCheck(pid, field as any, input.checked);
                                    }, 60);
                                } catch (e) { /* ignore */ }
                            };
                            (el as any).__plChecklistHandler = handler;
                            el.addEventListener('change', handler);
                        });
                    }
                } catch (inner) { /* ignore */ }
            }, 0);
        } catch (e) {
            // ignore
        }

        return () => {
            try {
                if ($ && $.fn) $(selector).off('.checklist');
                else {
                    const els = Array.from(document.querySelectorAll(selector));
                    els.forEach((el) => {
                        const h = (el as any).__plChecklistHandler;
                        if (h) el.removeEventListener('change', h);
                    });
                }
            } catch (e) { /* ignore */ }
        };
    }, [isOpen, checks, filter, updateCheck]);

    const visibleChecks = checks.filter(c => {
        if (!filter) return true;
        const search = filter.toLowerCase();
        // find production row data
        const row = productionRows.find(r => (Number(r.id ?? r.row_id ?? 0)) === c.production_id);
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
                    {visibleChecks.map((c) => {
                        const row = productionRows.find(r => (Number(r.id ?? r.row_id ?? 0)) === c.production_id);
                        const recipeName = row?.recipe_name || row?.recipe?.name || 'Unknown';
                        const qty = row?.product_quantity ?? row?.quantity ?? row?.quantityPerMin ?? 0;
                        const building = (row?.building_name || row?.recipe?.building?.name) || '';
                        const getIcon = (cls?: string) => {
                            if (!cls) return '';
                            return `/image/items/${cls.toLowerCase().replaceAll('_', '-')}_256.png`;
                        };
                        const iconSrc = getIcon(row?.recipe?.products?.[0]?.class_name || row?.item_class_name_1 || row?.item_class_name || '');
                        return (
                            <div key={c.production_id} className="card mb-2" id={`check-${c.production_id}`}>
                                <div className="card-body p-3">
                                    <div className="d-flex align-items-center">
                                        {iconSrc && <img src={iconSrc} alt="" className="pl-item-icon me-2" style={{width:32,height:32}} loading="lazy" />}
                                        <h5 className="card-title recipeName mb-0">{recipeName}</h5>
                                    </div>
                                    <p className="card-text mt-2"><span className="productionAmount">{qty}</span> per min - <span className="buildingAmount">{Math.round((qty/ (row?.recipe?.buildings?.[0]?.power_used || 1))*100)/100}</span> <span className="buildingName">{building}</span></p>
                                    <div style={{display:'flex', justifyContent:'space-between', alignItems:'center'}}>
                                        <div style={{display:'flex', alignItems:'center', gap: '0.5rem'}}>
                                            <input
                                                type="checkbox"
                                                id={`build-${c.production_id}`}
                                                data-toggle="toggle"
                                                data-onstyle="success"
                                                data-offstyle="dark"
                                                data-onlabel="&lt;i class='fa-solid fa-check'&gt;&lt;/i&gt;"
                                                data-offlabel="&lt;i class='fa-solid fa-times'&gt;&lt;/i&gt;"
                                                data-size="sm"
                                                data-style="ios"
                                                data-theme="dark"
                                                data-checklist-toggle="1"
                                                defaultChecked={!!c.been_build}
                                                aria-label="Mark as built"
                                            />
                                            <label htmlFor={`build-${c.production_id}`} className="mb-0">Build</label>
                                            <Tooltip content="Mark this recipe as built. You've constructed this part of the line.">
                                                <i className="fa-solid fa-circle-question ms-1 pl-help-icon" />
                                            </Tooltip>
                                        </div>

                                        <div style={{display:'flex', alignItems:'center', gap: '0.5rem'}}>
                                            <input
                                                type="checkbox"
                                                id={`tested-${c.production_id}`}
                                                data-toggle="toggle"
                                                data-onstyle="success"
                                                data-offstyle="dark"
                                                data-onlabel="&lt;i class='fa-solid fa-check'&gt;&lt;/i&gt;"
                                                data-offlabel="&lt;i class='fa-solid fa-times'&gt;&lt;/i&gt;"
                                                data-size="sm"
                                                data-style="ios"
                                                data-theme="dark"
                                                data-checklist-toggle="1"
                                                defaultChecked={!!c.been_tested}
                                                aria-label="Mark as tested"
                                            />
                                            <label htmlFor={`tested-${c.production_id}`} className="mb-0">Tested</label>
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
}

export default ChecklistModal;
