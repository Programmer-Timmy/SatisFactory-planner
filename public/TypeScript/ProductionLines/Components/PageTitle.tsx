import React, {FC} from 'react';

const PageTitle = ({GameSaveId, ProductionLineTitle}: { GameSaveId: number, ProductionLineTitle: string }) => {
    return (
        <div className="row justify-content-end align-items-center">
            <div className="col-lg-3"></div>
            <div className="col-lg-6 text-center">
                <h1 id="productionLineName">Production Line - {ProductionLineTitle}</h1>
            </div>
            <div className="col-lg-3">
                <div className="d-flex justify-content-lg-end justify-content-center flex-wrap gap-1">
                    <ActionButton
                        type="submit"
                        id="save_button"
                        variant="primary"
                        icon="fa-solid fa-save"
                        tooltip="Save production line.<br> <small>Hold <b>Shift</b> to save without returning to the save game.</small>"
                        htmlTooltip
                    />
                    <ActionButton
                        type="button"
                        id="edit_product_line"
                        variant="warning"
                        icon="fa-solid fa-pencil"
                        tooltip="Edit the production line"
                    />
                    <ActionButton
                        type="button"
                        id="showPower"
                        variant="info"
                        icon="fa-solid fa-bolt"
                        tooltip="Show power consumption"
                    />
                    <ActionButton
                        type="button"
                        id="showVisualizationButton"
                        variant="info"
                        icon="fa-solid fa-project-diagram"
                        tooltip="Show visualization"
                    />
                    <ActionButton
                        type="button"
                        id="showCheckList"
                        variant="info"
                        icon="fa-solid fa-list-check"
                        tooltip="Checklist"
                    />
                    <ActionButton
                        type="button"
                        id="showHelp"
                        variant="info"
                        icon="fa-regular fa-question-circle"
                        tooltip="Need help? Click here!"
                    />
                    <ActionButton
                        href={`/game_save/${GameSaveId}/`}
                        variant="secondary"
                        icon="fa-solid fa-arrow-left"
                        tooltip="Back to game save"
                    />
                </div>
            </div>
        </div>
    )
}
type SmartButtonProps = {
    id?: string;
    type?: "button" | "submit";
    variant?: "primary" | "secondary" | "warning" | "info";
    icon: string;
    tooltip: string;
    onClick?: () => void;
    href?: string;
    className?: string;
    htmlTooltip?: boolean;
};

export const ActionButton: FC<SmartButtonProps> = ({
                                                       id,
                                                       type = "button",
                                                       variant = "primary",
                                                       icon,
                                                       tooltip,
                                                       onClick,
                                                       href,
                                                       className = "",
                                                       htmlTooltip = false
                                                   }) => {
    const baseClass = `btn btn-${variant} mb-1 ${className}`;

    const commonProps = {
        id,
        className: baseClass,
        "data-bs-toggle": "tooltip",
        "data-bs-placement": "top",
        ...(htmlTooltip && {"data-bs-html": "true"}),
        "data-bs-title": tooltip
    };

    const iconEl = <i className={icon} aria-hidden="true"></i>;

    if (href) {
        return (
            <a href={href} {...commonProps}>
                {iconEl}
            </a>
        );
    }

    return (
        <button type={type} onClick={onClick} {...commonProps}>
            {iconEl}
        </button>
    );
};

export default PageTitle;
