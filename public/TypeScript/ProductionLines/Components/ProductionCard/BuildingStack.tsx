import React from 'react';
import { RecipeBuilding } from '../../Types/global';
import { formatNumber } from '../../Utils/format';

interface Props {
  building?: RecipeBuilding[];
  buildingAmount: number;
}

const getBuildingIcon = (className?: string) => {
  if (!className) return '';
  return `/image/items/${className.toLowerCase().replaceAll('_', '-').replace(/build/gi, 'desc')}_256.png`;
};

const BuildingStack: React.FC<Props> = ({ building, buildingAmount }) => {
  return (
    <div className="pl-building-stack">
      {building && building[0] && (
        <img className="pl-building-icon" data-role="building" src={getBuildingIcon(building[0].class_name)} loading="lazy" />
      )}

      <div className="pl-building-amount pl-number" data-role="building-amount">
        {formatNumber(buildingAmount)}
      </div>
    </div>
  );
};

export default BuildingStack;
