import React from 'react';

interface Props {
    checked: boolean;
    onChange: (checked: boolean) => void;
    onLabel?: React.ReactNode;
    offLabel?: React.ReactNode;
    size?: 'sm' | 'md';
    className?: string;
    ariaLabel?: string;
}

const ToggleSwitch: React.FC<Props> = ({checked, onChange, onLabel, offLabel, size = 'sm', className = '', ariaLabel}) => {
    const sizeClass = size === 'sm' ? 'pl-toggle-sm' : 'pl-toggle-md';

    return (
        <div
            role="switch"
            aria-checked={checked}
            tabIndex={0}
            onClick={() => onChange(!checked)}
            onKeyDown={(e) => { if (e.key === ' ' || e.key === 'Enter') { e.preventDefault(); onChange(!checked); } }}
            className={`pl-toggle ${checked ? 'on' : 'off'} ${sizeClass} ${className}`}
            aria-label={ariaLabel}
            style={{display: 'inline-block'}}
        >
            <style>{`
                .pl-toggle { position: relative; display: inline-flex; align-items: center; border-radius: 8px; overflow: hidden; cursor: pointer; user-select:none; }
                .pl-toggle .toggle-on, .pl-toggle .toggle-off { display:flex; align-items:center; justify-content:center; padding:0 6px; height:100%; }
                .pl-toggle .toggle-on { background: var(--bs-success); color: white; }
                .pl-toggle .toggle-off { background: #343a40; color: white; }
                .pl-toggle .toggle-handle { position: absolute; top: 2px; bottom: 2px; width: calc(50% - 4px); background: rgba(255,255,255,0.9); border-radius: 6px; transition: transform 160ms ease; }
                .pl-toggle.on .toggle-handle { transform: translateX(calc(50%)); }
                .pl-toggle.off .toggle-handle { transform: translateX(2px); }
                .pl-toggle-sm { width: 64px; height: 30px; }
                .pl-toggle-md { width: 84px; height: 34px; }
                .pl-toggle .toggle-on i, .pl-toggle .toggle-off i { font-size: 0.9rem; }
            `}</style>

            <div className="toggle-on" aria-hidden>{onLabel || <i className="fa-solid fa-check"/>}</div>
            <div className="toggle-off" aria-hidden>{offLabel || <i className="fa-solid fa-times"/>}</div>
            <div className="toggle-handle" aria-hidden />
        </div>
    );
}

export default ToggleSwitch;
