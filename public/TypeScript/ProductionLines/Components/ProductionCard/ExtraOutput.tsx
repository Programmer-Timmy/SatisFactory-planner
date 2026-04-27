import React from 'react';
import { RecipeProduct } from '../../Types/global';
import { formatNumber } from '../../Utils/format';

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
        <div className="pl-label">By-product</div>

        <div className="pl-field-with-icon">
          {product && (
            <img className="pl-item-icon" src={getIcon(product.class_name)} loading="lazy" />
          )}
          <div className="pl-value">{product?.name}</div>
        </div>
      </div>

      <div className="pl-field">
        <div className="pl-label">Local usage / min</div>
        <div className="pl-value pl-number">{formatNumber(localUsage)}</div>
      </div>

      <div className="pl-field">
        <div className="pl-label">Export / min</div>
        <div className="pl-value pl-number">{formatNumber(exportPerMin)}</div>
      </div>
    </>
  );
};

export default ExtraOutput;
