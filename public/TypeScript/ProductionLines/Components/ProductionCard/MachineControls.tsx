import React from 'react';

interface Props {
  clockValue: number | '';
  onClockChange: (v: number | '') => void;
  useSomersloop: boolean;
  onSomersloopChange: (checked: boolean) => void;
}

const MachineControls: React.FC<Props> = ({ clockValue, onClockChange, useSomersloop, onSomersloopChange }) => {
  return (
    <div className="pl-machine-controls">
      <div className="pl-field">
        <div className="pl-label">Clock %</div>

        <input
          type="number"
          min={0}
          max={250}
          step="any"
          className="form-control rounded-0"
          value={clockValue}
          onChange={(e) => onClockChange(e.target.value === '' ? '' : Number(e.target.value))}
          onBlur={(e) => {
            if (e.currentTarget.value === '') onClockChange(100);
          }}
        />
      </div>

      <div className="form-check mt-1">
        <input
          className="form-check-input"
          type="checkbox"
          checked={useSomersloop}
          onChange={(e) => onSomersloopChange(e.target.checked)}
        />

        <label className="form-check-label small">Somersloop</label>
      </div>
    </div>
  );
};

export default MachineControls;
