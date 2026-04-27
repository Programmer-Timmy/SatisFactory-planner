import React, {FC, useEffect, useMemo, useRef, useState} from 'react';
import {Recipe} from './ProductionLineApp';

type SearchByMenuSettings = {
    show: boolean;
    searchByProducts: boolean;
    searchByIngredients: boolean;
};

type Props = {
    recipes: Recipe[];
    value: number;
    onChange: (recipeId: number) => void;
};
const RecipeSelect: FC<Props> = ({recipes, value, onChange}) => {
    const [search, setSearch] = useState<string>('');
    const [open, setOpen] = useState<boolean>(false);
    const [showVisuals, setShowVisuals] = useState<boolean>(() => {
        const v = localStorage.getItem('showVisuals');
        return v === 'true' || v === null;
    });

    const [searchByMenu, setSearchByMenu] = useState<SearchByMenuSettings>(() => {
        return localStorage.getItem('searchByMenuSettings') ? JSON.parse(localStorage.getItem('searchByMenuSettings') as string) : {
            show: false,
            searchByProducts: true,
            searchByIngredients: false
        };
    });

    const containerRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        const selected = recipes.find(r => r.id === value);
        setSearch(selected ? selected.name : '');
    }, [value, recipes]);

    useEffect(() => {
        const onDocClick = (e: MouseEvent) => {
            if (!containerRef.current) return;
            if (!containerRef.current.contains(e.target as Node)) {
                setOpen(false);
            }
        };
        document.addEventListener('mousedown', onDocClick);
        return () => document.removeEventListener('mousedown', onDocClick);
    }, []);

    useEffect(() => {
        localStorage.setItem('showVisuals', showVisuals.toString());
    }, [showVisuals]);

    useEffect(() => {
        localStorage.setItem('searchByMenuSettings', JSON.stringify(searchByMenu));
    }, [searchByMenu]);

    const formatNumber = (val: number | string | undefined) => {
        const n = Number(val ?? 0);
        if (Number.isInteger(n)) return n.toString();
        const rounded = Number(n.toFixed(5));
        let s = rounded.toFixed(5);
        s = s.replace(/0+$/g, '').replace(/\.$/, '');
        return s;
    };

    const normalized = (s: string) => s.trim().toLowerCase();

    const filtered = useMemo(() => {
        const q = normalized(search);
        if (q === '') return recipes.slice().sort((a, b) => a.name.localeCompare(b.name));

        const fullMatches: Recipe[] = [];
        const others: Recipe[] = [];

        for (const r of recipes) {
            const name = normalized(r.name ?? '');
            const productNames = (r.products || []).map(p => normalized(p.name || ''));
            const ingredientNames = (r.ingredients || []).map(i => normalized(i.name || ''));

            const searchByProduct = searchByMenu.searchByProducts && productNames.length > 0;
            const searchByIngredient = searchByMenu.searchByIngredients && ingredientNames.length > 0;

            const matches = name.includes(q) || (searchByProduct && productNames.some(nm => nm.includes(q))) || (searchByIngredient && ingredientNames.some(nm => nm.includes(q)));
            if (!matches) continue;

            if (name === q || (searchByProduct && productNames.includes(q)) || (searchByIngredient && ingredientNames.includes(q))) {
                fullMatches.push(r);
            } else {
                others.push(r);
            }
        }

        fullMatches.sort((a, b) => a.name.localeCompare(b.name));
        others.sort((a, b) => a.name.localeCompare(b.name));
        return [...fullMatches, ...others];
    }, [recipes, search, searchByMenu]);

    const handleSelect = (r: Recipe) => {
        setSearch(r.name);
        onChange(r.id);
        setOpen(false);
    };

    return (
        <div className="bg-white recipe-select position-relative" ref={containerRef}>
            <input
                data-sp-skip="true"
                className="form-control rounded-0 search-input"
                name="recipeSearch"
                placeholder="Search by product or recipe"
                value={search}
                onChange={e => {
                    setSearch(e.target.value);
                    setOpen(true);
                }}
                onFocus={() => {
                    setOpen(true);
                }}
                autoComplete="off"
                type="text"
            />
            <input type="hidden" name="recipeId" className="recipe-id" data-field="recipeId" value={value ?? ''}/>

            <div
                className={`select-items-menu collapse position-absolute child bg-white z-2 rounded ${open ? 'show' : ''}`}
                style={{minWidth: 300, maxHeight: 300, left: 0, width: '100%'}}>
                <div className="d-flex justify-content-between align-items-center position-absolute end-0 icon-group">
                    <button name="searchByMenu"
                            className="btn btn-sm bg-transparent border-0 text-dark search-by-menu-button p-1 rounded-0"
                            type="button" onClick={() => setSearchByMenu(s => ({...s, show: !s.show}))}>
                        <i className="fa-solid fa-filter"/>
                    </button>
                    <button name="showHideRecipesVisuals"
                            className="btn btn-sm bg-transparent border-0 text-dark search-by-menu-button p-1 rounded-0"
                            type="button" onClick={() => setShowVisuals(v => !v)}>
                        <i className={showVisuals ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'}/>
                    </button>
                </div>

                <div
                    className={`search-by-menu collapse position-absolute bg-white rounded-end z-1 ${searchByMenu.show ? 'show' : ''}`}>
                    <div className="d-flex flex-column justify-content-start align-items-start p-1 gap-2"
                         style={{minWidth: 200}}>
                        <label
                            className="form-check mb-0 w-100 d-sm-inline-flex align-items-center justify-content-start">
                            <input type="checkbox" className="form-check-input search-by-products me-2"
                                   name="searchByProduct" data-sp-skip="true" checked={searchByMenu.searchByProducts}
                                   onChange={() => setSearchByMenu(s => ({
                                       ...s,
                                       searchByProducts: !s.searchByProducts
                                   }))}/>
                            <span className="form-check-label">By product</span>
                        </label>
                        <label
                            className="form-check mb-0 w-100 d-sm-inline-flex align-items-center justify-content-start">
                            <input type="checkbox" className="form-check-input search-by-ingredients me-2"
                                   name="searchByIngredients" data-sp-skip="true"
                                   checked={searchByMenu.searchByIngredients} onChange={() => setSearchByMenu(s => ({
                                ...s,
                                searchByIngredients: !s.searchByIngredients
                            }))}/>
                            <span className="form-check-label">By ingredients</span>
                        </label>
                    </div>
                </div>

                <div className="select-items overflow-y-auto z-2"
                     style={{maxHeight: 300, minWidth: 300, width: '100%'}}>
                    {filtered.length === 0 ? (
                        <div className="no-results text-muted text-center p-2">No results found</div>
                    ) : filtered.map(r => (
                        <div key={r.id} className={`p-1 select-item z-2 ${r.id === value ? 'active' : ''}`}
                             data-recipe-id={r.id} data-recipe-name={r.name} onClick={() => handleSelect(r)}>
                            <h6 className="m-0 text-center small recipe-name">{r.name}</h6>

                            {showVisuals && (
                                <div
                                    className="d-flex justify-content-center align-items-center mt-1 flex-wrap recipe-visuals"
                                    style={{gap: 4}}>
                                    {r.ingredients && r.ingredients.map((ing: any) => (
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

                                    {(r.ingredients && r.ingredients.length) ?
                                        <i className="fa-solid fa-arrow-right" style={{fontSize: 12}}/> : null}

                                    {r.building && r.building[0] ? (
                                        <img
                                            src={`/image/items/${String(r.building[0].class_name).toLowerCase().replace(/_/g, '-').replace(/build/i, 'desc')}_256.png`}
                                            title={r.building[0].name} className="img-fluid"
                                            style={{width: 26, height: 26}} loading="lazy"/>
                                    ) : null}

                                    {(r.products && r.products.length) ?
                                        <i className="fa-solid fa-arrow-right" style={{fontSize: 12}}/> : null}

                                    {r.products && r.products.map((prod: any) => (
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
                            )}

                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

export default RecipeSelect;