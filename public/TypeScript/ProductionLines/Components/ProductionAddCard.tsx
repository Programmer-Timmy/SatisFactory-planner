import {FC} from "react";
import Tooltip from "./Tooltip";

interface ProductionAddCardProps {
    onAdd: () => void;
}

const ProductionAddCard: FC<ProductionAddCardProps> = ({onAdd}) => {
    return (
        <div className="pl-add-recipe-card" data-role="add-recipe-card">
            <Tooltip content={"Add a new recipe row"}>
                <button type="button" className="pl-add-recipe-btn" id="pl-add-recipe" data-bs-toggle="tooltip"
                        onClick={onAdd}
                >
                    <i className="fa-solid fa-plus" aria-hidden="true"></i>
                    <span>Add recipe</span>
                </button>
            </Tooltip>
        </div>
    );
}

export default ProductionAddCard;