import React, {FC, useEffect, useMemo, useRef, useState} from 'react';
import {Recipe} from './ProductionLineApp';

// Global cache to avoid expensive DOM cloning on many instances
let cachedMenuHeight: number | null = null;
let menuHeightMeasured = false;

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
    const menuRef = useRef<HTMLDivElement | null>(null);
    const [anchorAbove, setAnchorAbove] = useState<boolean>(false);

    const computeAnchor = () => {
        if (!containerRef.current) return;
        const rect = containerRef.current.getBoundingClientRect();
        let menuHeight = 300;

        if (cachedMenuHeight != null) {
            menuHeight = cachedMenuHeight;
        } else {
            // Fast synchronous estimate based on number of items to avoid heavy DOM ops
            const itemHeight = showVisuals ? 70 : 40;
            const est = (filtered ? filtered.length : 5) * itemHeight + 16;
            menuHeight = Math.min(300, est);

            // Kick off one asynchronous measurement to cache exact size for future opens
            if (!menuHeightMeasured && menuRef.current && open) {
                // Only measure once and only when menu is actually open to avoid work during background operations
                menuHeightMeasured = true;
                requestAnimationFrame(() => {
                    try {
                        const clone = menuRef.current!.cloneNode(true) as HTMLElement;
                        clone.style.visibility = 'hidden';
                        clone.style.display = 'block';
                        clone.style.position = 'absolute';
                        clone.style.left = '-9999px';
                        clone.style.top = '-9999px';
                        document.body.appendChild(clone);
                        const measured = Math.min(600, clone.scrollHeight || clone.offsetHeight || 300);
                        document.body.removeChild(clone);
                        cachedMenuHeight = measured;
                        // Recompute anchor with accurate measurement if component still mounted
                        if (containerRef.current) {
                            const rect2 = containerRef.current.getBoundingClientRect();
                            const availableBelow2 = window.innerHeight - rect2.bottom;
                            const availableAbove2 = rect2.top;
                            setAnchorAbove(availableBelow2 < measured && availableAbove2 > availableBelow2);
                        }
                    } catch (e) {
                        // ignore measurement errors
                    }
                });
            }
        }

        const availableBelow = window.innerHeight - rect.bottom;
        const availableAbove = rect.top;
        // prefer below unless it doesn't fit and above has more space
        setAnchorAbove(availableBelow < menuHeight && availableAbove > availableBelow);
    };

    useEffect(() => {
        if (!open) return;
        computeAnchor();
        window.addEventListener('resize', computeAnchor);
        window.addEventListener('scroll', computeAnchor, true);
        return () => {
            window.removeEventListener('resize', computeAnchor);
            window.removeEventListener('scroll', computeAnchor, true);
        };
    }, [open, search]); // recompute when open or search changes

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

    // Virtualization helpers: only render visible slice to avoid large DOM when open
    const listRef = useRef<HTMLDivElement | null>(null);
    const [scrollTop, setScrollTop] = useState(0);
    const [listHeight, setListHeight] = useState(300);

    useEffect(() => {
        const el = listRef.current;
        if (!el) return;
        const onScroll = () => setScrollTop(el.scrollTop);
        const onResize = () => setListHeight(el.clientHeight || 300);
        el.addEventListener('scroll', onScroll);
        window.addEventListener('resize', onResize);
        // initialize
        setListHeight(el.clientHeight || 300);
        setScrollTop(el.scrollTop || 0);
        return () => {
            el.removeEventListener('scroll', onScroll);
            window.removeEventListener('resize', onResize);
        };
    }, [open, showVisuals, filtered.length]);

    const itemHeight = showVisuals ? 70 : 40;
    const buffer = 6; // items before/after visible window
    const totalItems = filtered.length;
    const totalHeight = totalItems * itemHeight;
    const startIndex = Math.max(0, Math.floor(scrollTop / itemHeight) - buffer);
    const visibleCount = Math.min(totalItems, Math.ceil(listHeight / itemHeight) + buffer * 2);
    const endIndex = Math.min(totalItems, startIndex + visibleCount);
    const visibleItems = filtered.slice(startIndex, endIndex);
    const paddingTop = startIndex * itemHeight;
    const paddingBottom = Math.max(0, (totalItems - endIndex) * itemHeight);


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
                    computeAnchor();
                    setOpen(true);
                }}
                onFocus={() => {
                    computeAnchor();
                    setOpen(true);
                }}
                autoComplete="off"
                type="text"
            />
            <input type="hidden" name="recipeId" className="recipe-id" data-field="recipeId" value={value ?? ''}/>

            <div
                ref={menuRef}
                className={`select-items-menu collapse position-absolute child bg-white z-2 rounded ${open ? 'show' : ''}`}
                style={{
                    minWidth: 300,
                    maxHeight: 300,
                    left: 0,
                    width: '100%',
                    top: anchorAbove ? 'auto' : '100%',
                    bottom: anchorAbove ? '100%' : 'auto'
                }}>
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

                <div className="select-items overflow-y-auto z-2" ref={listRef}
                     style={{maxHeight: 300, minWidth: 300, width: '100%'}}>
                    {open ? (
                        filtered.length === 0 ? (
                            <div className="no-results text-muted text-center p-2">No results found</div>
                        ) : (
                            // Virtualized list container with spacers
                            <div>
                                <div style={{height: paddingTop}} />

                                {visibleItems.map(r => (
                                    <div key={r.id} className={`p-1 select-item z-2 ${r.id === value ? 'active' : ''}`}
                                         data-recipe-id={r.id} data-recipe-name={r.name}
                                         onClick={() => handleSelect(r)}
                                         style={{boxSizing: 'border-box', overflow: 'hidden'}}>
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

                                <div style={{height: paddingBottom}} />
                            </div>
                        )
                    ) : null}
                </div>
            </div>
        </div>
    );
}

export default RecipeSelect;