import React, {FC} from 'react';
import { Item } from '../ProductionLineApp';

interface ImportsCardProps {
    itemName: string;
    itemClass: string;
    amount: number;
}

const ImportsCard: FC<ImportsCardProps> = ({ itemName, itemClass, amount }) => {
    return (
        <div className="pl-row pl-import-row is-collapsed" data-row-index="0">
            <div className="pl-import-collapsed" aria-hidden="true">
                <img className="pl-item-icon" data-role="import-icon-collapsed" loading="lazy"
                     src={`/image/items/${itemClass.toLowerCase().replaceAll("_", "-")}_256.png`} alt=""/>
                <div className="pl-import-collapsed-main">
                    <div className="pl-import-collapsed-name" data-role="import-name-collapsed">{itemName}</div>
                    <div className="pl-import-collapsed-qty">
                        <span className="pl-number" data-role="import-qty-collapsed">{amount}</span>
                        <span className="text-muted">/min</span>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default ImportsCard;
