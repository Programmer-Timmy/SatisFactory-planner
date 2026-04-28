import React from 'react';
import { RecipeProduct } from '../../Types/global';
import { formatNumber } from '../../Utils/format';
import Tooltip from "../Tooltip";

interface Props {
  product?: RecipeProduct;
  localUsage: number;
  exportPerMin: number;
}

const getIcon = (className?: string) => {
  if (!className) return '';
  return `/image/items/${className.toLowerCase().replaceAll('_', '-')}_256.png`;
};

const ExtraOutput: React.FC<Props> = ({ product, localUsage, exportPerMin }) => {
  return (
    <>
      <div className="pl-field">
        <div className="pl-label">
            <i className="fa-solid fa-arrow-right text-muted me-1 pl-flow-arrow" aria-hidden="true"></i>
            By-product
        </div>

        <div className="pl-field-with-icon">
          {product && (
            <img className="pl-item-icon" src={getIcon(product.class_name)} loading="lazy" />
          )}
          <div className="pl-value">{product?.name}</div>
        </div>
      </div>

      <div className="pl-field">
        <div className="pl-label">
            Local usage / min
            <Tooltip content="Amount consumed locally by other recipes in this production line."
                     className="ms-1 pl-info-icon">
                <i className="fa-regular fa-circle-question" aria-hidden="true"></i>
            </Tooltip>
        </div>
        <div className="pl-value pl-number">{formatNumber(localUsage)}</div>
      </div>

      <div className="pl-field">
        <div className="pl-label">
            Export / min
            <Tooltip content={"Amount available to export to other production lines (Qty − Local usage)."}
                     className="ms-1 pl-info-icon">
                <i className="fa-regular fa-circle-question" aria-hidden="true"></i>
            </Tooltip>
        </div>
        <div className="pl-value pl-number">{formatNumber(exportPerMin)}</div>
      </div>
    </>
  );
};

export default ExtraOutput;
