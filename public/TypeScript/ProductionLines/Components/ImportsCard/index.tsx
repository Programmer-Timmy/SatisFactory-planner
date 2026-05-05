import React, {FC, useState} from 'react';
import RecipeSelectionModal from './RecipeSelectionModal';
import Tooltip from "../Tooltip";

interface ImportsCardProps {
    itemName: string;
    itemClass: string;
    amount: number;
    itemId: number;
    producingRecipes: any[];
    onAddRecipe: (recipeId: number, itemId: number) => void;
}

const ImportsCard: FC<ImportsCardProps> = ({itemName, itemClass, amount, itemId, producingRecipes, onAddRecipe}) => {
    const [showModal, setShowModal] = useState(false);

    const handleAddClick = () => {
        if (producingRecipes.length === 0) return;

        if (producingRecipes.length === 1) {
            onAddRecipe(producingRecipes[0].id, itemId);
        } else {
            setShowModal(true);
        }
    };

    const handleSelectRecipe = (recipe: any) => {
        onAddRecipe(recipe.id, itemId);
    };

    return (
        <>
            <div className="pl-row pl-import-row is-collapsed" data-row-index="0">
                <div className="d-flex justify-content-between" aria-hidden="true">
                    <div className={"pl-import-collapsed"}>
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
                    {producingRecipes.length > 0 && (
                        <Tooltip content="Add a production recipe for this item">
                            <button
                                className="btn btn-sm btn-outline-primary"
                                onClick={handleAddClick}
                                title={`Add production recipe for ${itemName}`}
                            >
                                <i className="fa-solid fa-plus"></i>
                            </button>
                        </Tooltip>
                    )}
                </div>
            </div>

            <RecipeSelectionModal
                isOpen={showModal}
                onClose={() => setShowModal(false)}
                itemName={itemName}
                amount={amount}
                producingRecipes={producingRecipes}
                onSelectRecipe={handleSelectRecipe}
            />
        </>
    );
}

export default ImportsCard;
