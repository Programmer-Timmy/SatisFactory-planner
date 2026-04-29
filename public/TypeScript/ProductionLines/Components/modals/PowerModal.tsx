import React, {FC} from 'react';
import Modal from "../Modal";
import getBuildingIcon from "../../Utils/getBuildingIcon";
import {PowerItem} from "../ProductionLineApp";
import {formatNumber} from "../../Utils/format";
import Tooltip from "../Tooltip";

interface Props {
    isOpen: boolean;
    onClose: () => void;
    rows: PowerItem[];
    appData: any | null;
    onChangeRow: (index: number, field: keyof PowerItem, value: any) => void;
    onAddRow: () => void;
    onDeleteRow: (index: number) => void;
    onSave: () => void;
    computeConsumption: (row: any) => number;
    totalConsumption: number;
}

const PowerModal: FC<Props> = ({
                                   isOpen,
                                   onClose,
                                   rows,
                                   appData,
                                   onChangeRow,
                                   onAddRow,
                                   onDeleteRow,
                                   computeConsumption,
                                   totalConsumption
                               }) => {
    if (!appData) return null;
    // @ts-ignore
    return (
        <Modal isOpen={isOpen} onClose={onClose} title="Power" className="modal-lg">
            <Modal.Body>
                <div className="pl-list">
                    {rows.map((r, idx) => (
                        <div key={r.idpower || idx} className="power-card" data-row-index={idx}>
                            <div className="power-card-body d-flex align-items-start gap-3">
                                <img className="power-icon" loading="lazy" src={getBuildingIcon(r.building?.class_name)}
                                     alt=""/>

                                <div className="power-fields flex-grow-1">
                                    <div className="power-field-row d-flex flex-wrap gap-2">
                                        <div className="power-field">
                                            <div className="power-label">Building</div>
                                            <select className="form-select form-select-sm" value={r.buildings_id || 0}
                                                    onChange={(e) => onChangeRow(idx, 'buildings_id', Number((e.target as HTMLSelectElement).value))}
                                                    disabled={r.user === 0}>
                                                <option value={0} disabled>Select a building</option>
                                                {/*@ts-ignore */}
                                                {appData.buildings.map(b => (
                                                    <option key={b.id} value={b.id}>{b.name}</option>))}
                                            </select>
                                        </div>

                                        <div className="power-field">
                                            <div className="power-label">Amount</div>
                                            <input min={0} type="number"
                                                   className="form-control form-control-sm power-input"
                                                   value={r.building_ammount}
                                                   onChange={(e) => onChangeRow(idx, 'building_ammount', Number((e.target as HTMLInputElement).value))}
                                                   disabled={r.user === 0}/>
                                        </div>

                                        <div className="power-field">
                                            <div className="power-label">Clock Speed</div>
                                            <input min={0} max={250} type="number"
                                                   className="form-control form-control-sm power-input"
                                                   value={formatNumber(r.clock_speed)}
                                                   onChange={(e) => onChangeRow(idx, 'clock_speed', Number((e.target as HTMLInputElement).value))}
                                                   disabled={r.user === 0}/>
                                        </div>

                                        <div className="power-meta ms-auto text-end">
                                            <div className="text-muted small">Consumption</div>
                                            <div><strong>{computeConsumption(r).toFixed(2)}</strong></div>
                                        </div>
                                    </div>
                                </div>

                                {r.user ? (
                                    <div className="pl-side-actions">
                                        <button className="btn btn-sm delete-production-row"
                                                onClick={() => onDeleteRow(idx)} aria-label="Delete">
                                            <i className="fa-solid fa-trash"/>
                                        </button>
                                    </div>
                                ) : null}
                            </div>
                        </div>
                    ))}

                    <div className="my-2 d-flex justify-content-between align-items-center">
                        <div></div>
                        <div className="text-end">

                        </div>
                    </div>
                </div>
            </Modal.Body>
            <Modal.Footer>
                <div className="d-flex justify-content-between w-100 align-items-center">
                    <div>
                        <div className="text-muted small">Total</div>
                        <div><strong>{formatNumber(totalConsumption)}</strong></div>
                    </div>
                    <div>
                        <Tooltip
                            content="Add a new power consumer, such as miners, pumps, or other buildings that use power. This helps you get a clearer overview of your total power consumption."
                            placement="top">
                            <button className="btn btn-outline-primary" onClick={onAddRow}>Add row</button>
                        </Tooltip>
                        <button className="btn btn-secondary ms-2" onClick={onClose}>Close</button>
                    </div>
                </div>
            </Modal.Footer>
        </Modal>
    );
}

export default PowerModal;
