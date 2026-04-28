import React, {createContext, useContext, useEffect, useState, Dispatch, SetStateAction} from 'react';

export type SearchByMenuSettings = {
    show: boolean;
    searchByProducts: boolean;
    searchByIngredients: boolean;
};

type ProductionSettings = {
    showVisuals: boolean;
    setShowVisuals: Dispatch<SetStateAction<boolean>>;
    searchByMenu: SearchByMenuSettings;
    setSearchByMenu: Dispatch<SetStateAction<SearchByMenuSettings>>;
};

const STORAGE_SHOW_VISUALS = 'showVisuals';
const STORAGE_SEARCH_BY_MENU = 'searchByMenuSettings';

const defaultSearchByMenu: SearchByMenuSettings = {
    show: false,
    searchByProducts: true,
    searchByIngredients: false
};

const DefaultContext: ProductionSettings = {
    showVisuals: true,
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    setShowVisuals: () => {},
    searchByMenu: defaultSearchByMenu,
    // eslint-disable-next-line @typescript-eslint/no-empty-function
    setSearchByMenu: () => {}
};

const ProductionSettingsContext = createContext<ProductionSettings>(DefaultContext);

export const ProductionSettingsProvider: React.FC<React.PropsWithChildren<{}>> = ({children}) => {
    const [showVisuals, setShowVisuals] = useState<boolean>(() => {
        const v = localStorage.getItem(STORAGE_SHOW_VISUALS);
        return v === 'true' || v === null;
    });

    const [searchByMenu, setSearchByMenu] = useState<SearchByMenuSettings>(() => {
        const raw = localStorage.getItem(STORAGE_SEARCH_BY_MENU);
        return raw ? JSON.parse(raw) as SearchByMenuSettings : defaultSearchByMenu;
    });

    useEffect(() => {
        try {
            localStorage.setItem(STORAGE_SHOW_VISUALS, showVisuals.toString());
        } catch (e) {
            // localStorage may be unavailable in some contexts
        }
    }, [showVisuals]);

    useEffect(() => {
        try {
            localStorage.setItem(STORAGE_SEARCH_BY_MENU, JSON.stringify(searchByMenu));
        } catch (e) {
        }
    }, [searchByMenu]);

    return (
        <ProductionSettingsContext.Provider value={{showVisuals, setShowVisuals, searchByMenu, setSearchByMenu}}>
            {children}
        </ProductionSettingsContext.Provider>
    );
};

export const useProductionSettings = () => useContext(ProductionSettingsContext);

export default ProductionSettingsContext;
