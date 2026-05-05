import React, {FC} from 'react';
import Modal from '../Modal';

interface RecipeSelectionModalProps {
    isOpen: boolean;
    onClose: () => void;
    itemName: string;
    amount: number;
    producingRecipes: any[];
    onSelectRecipe: (recipe: any) => void;
}

const RecipeSelectionModal: FC<RecipeSelectionModalProps> = ({
    isOpen,
    onClose,
    itemName,
    amount,
    producingRecipes,
    onSelectRecipe
}) => {
    const formatNumber = (val: number | string | undefined) => {
        const n = Number(val ?? 0);
        if (Number.isInteger(n)) return n.toString();
        const rounded = Number(n.toFixed(5));
        let s = rounded.toFixed(5);
        s = s.replace(/0+$/g, '').replace(/\.$/, '');
        return s;
    };

    const calculateBuildingsNeeded = (recipe: any, targetAmount: number) => {
        const exportPerMin = recipe.export_amount_per_min || 0;
        if (exportPerMin <= 0) return 0;
        return Math.ceil((targetAmount / exportPerMin) * 100) / 100;
    };

    const handleSelectRecipe = (recipe: any) => {
        onSelectRecipe(recipe);
        onClose();
    };

    return (
        <Modal
            isOpen={isOpen}
            onClose={onClose}
            title={`Select Recipe to Produce ${itemName}`}
        >
            <Modal.Body>
                <p className="text-muted mb-3">Multiple recipes can produce {itemName}. Choose one:</p>
                <style>{`
                    .recipe-card-button {
                        transition: background-color 150ms ease-in-out, border-color 150ms ease-in-out;
                    }
                    .recipe-card-button:hover {
                        background-color: rgba(255, 138, 71, 0.25);
                        border-color: #ff8a47 !important;
                    }
                `}</style>
                <div className="d-flex flex-column gap-2">
                    {producingRecipes.map((recipe) => {
                        const buildingsNeeded = calculateBuildingsNeeded(recipe, amount);
                        return (
                            <div
                                key={recipe.id}
                                className="recipe-card-button text-start d-flex align-items-center justify-content-between border border-primary rounded-3 p-2"
                                onClick={() => handleSelectRecipe(recipe)}
                                style={{ padding: '12px 16px', cursor: 'pointer' }}
                                role="button"
                            >
                                {/* Left side: Recipe name and flow */}
                                <div className="d-flex flex-column">
                                    <div className="mb-2 fw-semibold">{recipe.name}</div>
                                    
                                    {/* Production flow */}
                                    <div
                                        className="d-flex align-items-center mt-1 flex-wrap recipe-visuals"
                                        style={{gap: 4}}>
                                        {recipe.ingredients && recipe.ingredients.map((ing: any) => (
                                            <div key={ing.id} className="d-flex align-items-center recipe-ingredient"
                                                 style={{gap: 2}} data-ingredient-id={ing.id}
                                                 data-ingredient-name={ing.name}>
                                                <img
                                                    src={`/image/items/${String(ing.class_name).toLowerCase().replace(/_/g, '-')}_256.png`}
                                                    title={ing.name} className="img-fluid" style={{width: 26, height: 26}}
                                                    loading="lazy"/>
                                                <small className="text-muted">{formatNumber(ing.quantity)}</small>
                                            </div>
                                        ))}

                                        {(recipe.ingredients && recipe.ingredients.length) ?
                                            <i className="fa-solid fa-arrow-right" style={{fontSize: 12}}/> : null}

                                        {recipe.building && recipe.building[0] ? (
                                            <img
                                                src={`/image/items/${String(recipe.building[0].class_name).toLowerCase().replace(/_/g, '-').replace(/build/i, 'desc')}_256.png`}
                                                title={recipe.building[0].name} className="img-fluid"
                                                style={{width: 26, height: 26}} loading="lazy"/>
                                        ) : null}

                                        {(recipe.products && recipe.products.length) ?
                                            <i className="fa-solid fa-arrow-right" style={{fontSize: 12}}/> : null}

                                        {recipe.products && recipe.products.map((prod: any) => (
                                            <div key={prod.id} className="d-flex align-items-center recipe-product"
                                                 style={{gap: 2}} data-product-id={prod.id} data-product-name={prod.name}>
                                                <img
                                                    src={`/image/items/${String(prod.class_name).toLowerCase().replace(/_/g, '-')}_256.png`}
                                                    title={prod.name} className="img-fluid" style={{width: 26, height: 26}}
                                                    loading="lazy"/>
                                                <small className="text-muted">{formatNumber(prod.quantity)}</small>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                {/* Right side: Building icon and amount */}
                                <div className="d-flex flex-column align-items-center gap-1" style={{marginLeft: '16px', minWidth: '60px'}}>
                                    {recipe.building && recipe.building[0] && (
                                        <>
                                            <img
                                                src={`/image/items/${String(recipe.building[0].class_name).toLowerCase().replace(/_/g, '-').replace(/build/i, 'desc')}_256.png`}
                                                title={recipe.building[0].name}
                                                style={{width: 32, height: 32}}
                                                loading="lazy"
                                                alt=""
                                            />
                                            <small className="text-muted text-center fw-bold">{formatNumber(buildingsNeeded)}</small>
                                        </>
                                    )}
                                </div>
                            </div>
                        );
                    })}
                </div>
            </Modal.Body>
        </Modal>
    );
};

export default RecipeSelectionModal;
