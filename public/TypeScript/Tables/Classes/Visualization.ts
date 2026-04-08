import {TableHandler} from "./TableHandler";
import {ImportNodes} from "./Data/Visualization/ImportNodes";
import {ProductionNodes} from "./Data/Visualization/ProductionNodes";
import {ExportNodes} from "./Data/Visualization/ExportNodes";
import {Connection} from "./Data/Visualization/Connection";
import type {Core, EdgeSingular, LayoutOptions, NodeSingular} from "cytoscape";
import {IChecklist} from "./Checklist";
import {PowerTableFunctions} from "./Functions/PowerTableFunctions";
import {HtmlGeneration} from "./Functions/HtmlGeneration";

let cytoscape: typeof import("cytoscape")


// global variables
// Keep labels compact so we can show machine + product + /min at once
const TEXT_SIZE = 14;
const EDGE_TEXT_SIZE = 13;

/**
 * Visualization class
 *
 * This class is responsible for creating the visualization of the production line
 * @class
 * @property {TableHandler} TableHandler - The table handler object
 * @property {ImportNodes[]} importNodes - The import nodes array
 * @property {ProductionNodes[]} productionNodes - The production nodes array
 * @property {ExportNodes[]} exportNodes - The export nodes array
 *
 * @property {Connection[]} importConnections - The import connections array
 * @property {Connection[]} productionConnections - The production connections array
 * @property {Connection[]} exportConnections - The export connections array
 */
export class Visualization {

    public TableHandler: TableHandler;
    public importNodes: ImportNodes[] = [];
    public productionNodes: ProductionNodes[] = [];
    public exportNodes: ExportNodes[] = [];

    public importConnections: Connection[] = [];
    public productionConnections: Connection[] = [];
    public exportConnections: Connection[] = [];

    public layout: string = 'klay';

    private showExport: boolean = false;
    private showImport: boolean = true;
    private showChecklist: boolean = true;
    private useRoots: boolean = true;

    private cy: Core | null = null;

    /**
     * Constructor for the Visualization class
     * @constructor
     * @param {TableHandler} tableHandler - The table handler object
     */
    constructor(tableHandler: TableHandler) {
        this.TableHandler = tableHandler;

        // Get the data
        this.getterData();

        // Create the visualization
        this.createVisualization();

        // Add event listeners
        this.addEventListeners();
    }

    public update(): void {
        this.getterData();
        this.createVisualization();
    }

    /**
     * Create the visualization of the production line
     */
    public async createVisualization(): Promise<void> {
        // check if the extensions are loaded, if not, load them
        if (!cytoscape) {
            await this.showLoadingScreen();
            await this.loadCytoscapeExtensions();
        }

        let elements = [];
        let roots = [];

        if (this.showImport) {
            elements.push(...this.importNodes.map(node => this.addNode('import', node)));
            roots.push(...this.importNodes.map(node => `import_${node.id}`));
            elements.push(...this.importConnections.map(connection => this.addConnection('import', connection, 'import', 'production')));
        }

        elements.push(...this.productionNodes.map(node => this.addNode('production', node)));
        elements.push(...this.productionConnections.map((connection, i) => ({
            ...this.addConnection('production', connection, 'production', 'production'),
            data: {
                ...this.addConnection('production', connection, 'production', 'production').data,
                id: `productionConnection_${i}`
            }
        })));
        if (this.showExport) {
            elements.push(...this.exportNodes.map(node => this.addNode('export', node)));
            elements.push(...this.exportConnections.map(connection => this.addConnection('export', connection, 'production', 'export')));
        }

        const layout: LayoutOptions = {
            name: "klay",
            // @ts-ignore
            animate: true,
            spacingFactor: 2.0,
            nodeDimensionsIncludeLabels: true,
            avoidOverlap: true,
        }

        const cy = cytoscape({
            container: document.getElementById("graph"), // container to render in
            elements: elements,

            style: [
                {
                    selector: "node",
                    // Cytoscape style values accept numbers/strings; cast to satisfy TS typing
                    style: ({
                        "label": "data(label)",
                        "text-halign": "center",
                        "text-valign": "bottom",
                        "text-margin-y": -12,
                        "color": "#111",
                        "font-size": TEXT_SIZE,
                        "text-wrap": "wrap",
                        "text-max-width": 170,
                        "text-outline-color": "#fff",
                        "text-outline-width": 2,

                        "width": 200,
                        "height": 185,
                        "shape": "roundrectangle",
                        "background-color": "#ffffff",
                        "border-width": 6,
                        "border-color": "data(color)",

                        "background-image": "data(icon)",
                        "background-fit": "contain",
                        "background-repeat": "no-repeat",
                        "background-position-x": "50%",
                        "background-position-y": "26%",
                        "background-width": "66%",
                        "background-height": "66%",
                    } as any)
                },
                {
                    selector: "node.import-node",
                    style: {
                        "border-color": "#0d6efd"
                    }
                },
                {
                    selector: "node.export-node",
                    style: {
                        "border-color": "#dc3545"
                    }
                },
                {
                    // Production, import, and export nodes render their content via cytoscape-node-html-label
                    selector: "node.production-node, node.import-node, node.export-node",
                    style: {
                        "label": "",
                        "background-image": "none",
                        "text-outline-width": 0,
                        // Hide the underlying Cytoscape node shape; the HTML label is the visual card.
                        "background-opacity": 0,
                        "border-width": 0,
                        "width": "data(cardWidth)",
                        "height": "data(cardHeight)",
                        "padding": 0
                    }
                },
                {
                    selector: "edge",
                    style: ({
                        "label": "",
                        "width": 4,
                        "line-color": "data(color)",
                        "target-arrow-color": "data(color)",
                        "target-arrow-shape": "triangle",
                        "target-arrow-fill": "filled",
                        "curve-style": "bezier",
                        "control-point-step-size": 40
                    } as any)
                }
            ],
            layout: layout,
            minZoom: 0.1,
            maxZoom: 2,
            zoom: 1,

            wheelSensitivity: 0.2,
        });

        // Render rich HTML inside production nodes (multiple icons + details)
        try {
            // @ts-ignore
            (cy as any).nodeHtmlLabel([
                {
                    query: 'node.production-node',
                    cssClass: 'cy-production-card',
                    halign: 'center',
                    valign: 'center',
                    halignBox: 'center',
                    valignBox: 'center',
                    tpl: (data: any) => this.buildProductionNodeHtml(data)
                },
                {
                    query: 'node.import-node',
                    cssClass: 'cy-import-card',
                    halign: 'center',
                    valign: 'center',
                    halignBox: 'center',
                    valignBox: 'center',
                    tpl: (data: any) => this.buildImportNodeHtml(data)
                },
                {
                    query: 'node.export-node',
                    cssClass: 'cy-export-card',
                    halign: 'center',
                    valign: 'center',
                    halignBox: 'center',
                    valignBox: 'center',
                    tpl: (data: any) => this.buildExportNodeHtml(data)
                }
            ]);
        } catch (e) {
            // If the plugin is missing for some reason, we still keep the graph usable
            console.warn('nodeHtmlLabel plugin not available', e);
        }

        cy.ready(() => {
            this.renderEdgeLabels(cy);

            cy.on('pan zoom', () => this.renderEdgeLabels(cy));
            cy.on('position', 'node', () => this.renderEdgeLabels(cy));

            // @ts-ignore
            cy.nodes().forEach(node => {
                this.applyQTip(node, node.data('title'));

                node.on('drag', () => {
                    const api = (node as any).qtip('api');
                    if (api?.visible) {
                        api.hide();
                    }
                });

                node.on('mouseout', () => {
                    (node as any).qtip('api')?.hide();
                });
            });

            // @ts-ignore
            cy.edges().forEach(edge => {
                this.applyQTip(edge, edge.data('label'));
            });

            cy.center();
            cy.fit();
        });

        this.cy = cy;
    }

    private applyQTip(element: NodeSingular | EdgeSingular, content: string): void {
        // User preference: no scrollbar; tooltip should become wider instead.
        const wrapped = `
            <div style="width:920px;max-width:95vw;padding-right:6px">
                ${content}
            </div>
        `;

        (element as any).qtip({
            content: wrapped,
            position: {
                my: 'top center',
                at: 'bottom center',
                // Keep tooltip inside viewport
                // @ts-ignore
                viewport: $(window),
                // @ts-ignore
                adjust: { method: 'shift flip' }
            },
            style: {
                classes: 'qtip-bootstrap',
                tip: {
                    width: 16,
                    height: 8
                }
            },
            show: {
                event: 'mouseover',
                delay: 100
            },
            hide: {
                event: 'mouseout'
            }
        });
    }

    // updates the production node border colors based on the checklist
    public updateNodeColors(): void {

        if (!this.cy) { // Ensure `this.cy` exists
            console.error("Cytoscape instance not found!");
            return;
        }

        this.cy.nodes().forEach(node => {
            const data = node.data();
            if (data.id.startsWith('production')) {
                const productionNode = this.productionNodes.find(n => `production_${n.id}` === data.id);
                if (!productionNode) {
                    return;
                }

                node.style('border-color', this.showChecklist ? this.getColor(productionNode.checklist) : '#28A745');
            }
        });
    }

    private getColor(checklist: IChecklist | undefined): string {
        if (!checklist) {
            return '#A0A0A0'; // Not built & not tested (Gray)
        }

        if (checklist.beenBuild && checklist.beenTested) {
            return '#28A745'; // Built & tested (Green)
        } else if (checklist.beenBuild) {
            return '#FFD700'; // Built but not tested (Yellow)
        } else {
            return '#A0A0A0'; // Not built & not tested (Gray)
        }
    }



    /**
     * Add a node to the visualization
     * @param node - The node to add
     * @param type - The type of the node
     * @private
     *
     * @returns {object} - The node object
     */
    private addNode(type: 'import' | 'export' | 'production', node: any): {
        data: { id: string, label: string, color: string, title: string, icon?: string | null },
        classes: string
    } {
        const qty = this.formatNumber(node.quantity);
        const unit = type === 'production' || type === 'import' || type === 'export' ? '/min' : '';

        // Keep a normal label for import/export. Production content is rendered via HTML labels.
        let label = `${node.product}\n${qty}${unit}`;
        if (type === 'production' && node?.building) {
            const machineAmount = this.formatNumber(node?.buildingAmount ?? 0);
            label = `${node.building} x ${machineAmount}\n${node.product}\n${qty}${unit}`;
        }

        const color = type === 'import' ? '#0d6efd' :
            type === 'export' ? '#dc3545' : this.showChecklist ? this.getColor(node.checklist) : '#28A745';

        const title = node.titleHtml
            ? node.titleHtml
            : `${type.charAt(0).toUpperCase() + type.slice(1)}: <b>${node.product}</b><br>Amount: ${qty}${unit}`;

        // Attach extra fields for HTML labels (Cytoscape data can include arbitrary properties)
        const isProduction = type === 'production';
        const hasRecipeName = Boolean(node?.recipeName);
        const hasSomersloop = typeof node?.somersloop === 'boolean';

        const baseWidth = isProduction ? 290 : 200;
        const baseHeight = isProduction ? 185 : 110;
        const cardWidth = baseWidth;
        const cardHeight = isProduction ? (baseHeight + (hasRecipeName ? 12 : 0) + (hasSomersloop ? 12 : 0)) : baseHeight;

        const data: any = {
            id: `${type}_${node.id}`,
            label,
            color,
            title,
            icon: node.icon || null,
            product: node.product,
            quantity: node.quantity,
            unit,
            buildingName: node?.building || null,
            buildingAmount: node?.buildingAmount ?? null,
            buildingIcon: node?.buildingIcon || null,
            byproductIcon: node?.byproductIcon || null,

            recipeName: node?.recipeName || null,
            exportPerMin: node?.exportPerMin ?? null,
            usagePerMin: node?.usagePerMin ?? null,
            clockSpeed: node?.clockSpeed ?? null,
            somersloop: typeof node?.somersloop === 'boolean' ? node.somersloop : null,
            byproductName: node?.byproductName || null,
            byproductExportPerMin: node?.byproductExportPerMin ?? null,

            cardWidth,
            cardHeight
        };

        return {
            data,
            classes: `${type}-node`
        }
    }

    private addConnection(type: 'import' | 'export' | 'production', connection: Connection, sourcePrefix: string, targetPrefix: string) {
        const qty = this.formatNumber(connection.quantity);

        // Resolve icon from the product name or id stored on the connection
        const icon = connection.itemId
            ? HtmlGeneration.getItemIconSrcForId(connection.itemId)
            : null;

        return {
            data: {
                id: `${type}Connection_${connection.id}`,
                source: `${sourcePrefix}_${connection.sourceId}`,
                target: `${targetPrefix}_${connection.targetId}`,
                label: `${connection.product}\n${qty}/min`,  // keep as fallback
                product: connection.product,
                qty,
                icon,
                color: type === 'import' ? '#0d6efd' : type === 'export' ? '#dc3545' : '#28A745'
            }
        };
    }

    /**
     * Get the data from the tables
     * @private
     */
    private getterData(): void {
        this.importNodes = [];
        this.productionNodes = [];
        this.exportNodes = [];
        this.importConnections = [];
        this.productionConnections = [];
        this.exportConnections = [];

        this.getImportNodes();
        this.getProduction();
        this.getExportNodes();
        this.getImportConnection();
    }

    /**
     * Add event listeners to the visualization
     * @method
     * @private
     */
    private addEventListeners(): void {
        // $('#layout').on('change', (e) => {
        //     const select = $(e.target);
        //     this.layout = select.val() as 'breadthfirst' | 'cose' | 'klay' | 'fcose' | 'concentric';
        //     this.createVisualization();
        // });

        $('#showChecklist').on('change', (e) => {
            const select = $(e.target);
            this.showChecklist = select.prop('checked');
            this.updateNodeColors();
        });

        $('#export').on('change', (e) => {
            const select = $(e.target);
            this.showExport = select.prop('checked');
            this.createVisualization();
        });

        $('#import').on('change', (e) => {
            const select = $(e.target);
            this.showImport = select.prop('checked');
            this.createVisualization();
        });

        $('#refresh').on('click', () => {
            this.createVisualization();
        });

        $('#roots').on('change', (e) => {
            const select = $(e.target);
            this.useRoots = select.prop('checked');
            this.createVisualization();
        });
    }


    /**
     * Get all import connections from the production table and add them to the import connections array
     * @private
     */
    private getImportConnection(): void {
        let index = 0;
        for (let i = 0; i < this.TableHandler.productionTableRows.length; i++) {
            const row = this.TableHandler.productionTableRows[i];
            console.log(row);

            for (let j = 0; j < row.imports.length; j++) {
                const importRow = row.imports[j];
                const itemId = row.recipe?.resources?.[j]?.itemId || 0;
                this.importConnections.push(new Connection(index, importRow.index, i, importRow.amount, importRow.product, itemId));
                index++;
            }
        }
    }

    /**
     * Get all production nodes and connections from the production table
     * @private
     */
    private getProduction(): void {
        for (let i = 0; i < this.TableHandler.productionTableRows.length; i++) {
            const row = this.TableHandler.productionTableRows[i];

            const recipe = row.recipe;
            const building = recipe?.building;
            const checklist = this.TableHandler.checklist?.getChecklist().find(check => check.productionRow.row_id == row.row_id);

            if (!recipe) {
                continue;
            }

            const buildingAmount = PowerTableFunctions.calculateBuildingAmount(recipe, row);
            const outputIcon = HtmlGeneration.getItemIconSrcForId(recipe.item_id);
            const byproductIcon = recipe.item_id2 ? HtmlGeneration.getItemIconSrcForId(recipe.item_id2) : null;
            const buildingIcon = recipe?.building?.class_name ? this.getBuildingIconSrc(recipe.building.class_name) : null;

            const node = new ProductionNodes(
                i,
                // Prefer output name; recipe name goes in tooltip
                (recipe.itemName || row.product || recipe.name),
                row.quantity,
                building?.name || '',
                building?.id || 0,
                buildingAmount,
                checklist
            );

            (node as any).icon = outputIcon;
            (node as any).buildingIcon = buildingIcon;
            (node as any).byproductIcon = byproductIcon;

            (node as any).recipeName = recipe?.name || '';
            (node as any).exportPerMin = row?.exportPerMin ?? 0;
            (node as any).usagePerMin = row?.Usage ?? 0;
            (node as any).clockSpeed = row?.recipeSetting?.clockSpeed ?? 100;
            (node as any).somersloop = row?.recipeSetting?.useSomersloop;
            (node as any).byproductName = recipe?.secondItemName || '';
            (node as any).byproductExportPerMin = row?.extraCells?.ExportPerMin ?? 0;

            (node as any).titleHtml = this.buildProductionTitleHtml(i, row, recipe, buildingAmount, outputIcon, byproductIcon, buildingIcon);

            this.productionNodes.push(node);

            for (let j = 0; j < row.productionImports.length; j++) {
                const importRow = row.productionImports[j];
                const itemId = row.recipe?.resources?.[j]?.itemId || 0;
                this.productionConnections.push(new Connection(i, importRow.index, i, importRow.amount, importRow.product, itemId));
            }
        }
    }



    /**
     * Get all import nodes from the import table and add them to the import nodes array
     * @private
     */
    private getImportNodes(): void {
        for (let i = 0; i < this.TableHandler.importsTableRows.length; i++) {
            const row = this.TableHandler.importsTableRows[i];
            if (row.product !== '' && row.quantity > 0) {
                const node = new ImportNodes(i, row.product, row.quantity);
                const icon = HtmlGeneration.getItemIconSrcForId(row.itemId);
                (node as any).icon = icon;
                (node as any).titleHtml = this.buildSimpleTitleHtml('Import', icon, row.product, row.quantity);
                this.importNodes.push(node);
            }
        }
    }

    /**
     * Get all export nodes from the production table and add them to the export nodes array and connections array
     * @private
     */
    private getExportNodes(): void {
        let index = 0;
        for (let i = 0; i < this.TableHandler.productionTableRows.length; i++) {
            const row = this.TableHandler.productionTableRows[i];
            const recipe = row.recipe;

            if (row.exportPerMin > 0) {
                const productName = recipe?.itemName || row.product;
                const icon = recipe ? HtmlGeneration.getItemIconSrcForId(recipe.item_id) : null;
                const node = new ExportNodes(index, productName, row.exportPerMin);
                (node as any).icon = icon;
                (node as any).titleHtml = this.buildSimpleTitleHtml('Export', icon, productName, row.exportPerMin);
                this.exportNodes.push(node);
                this.exportConnections.push(new Connection(index, i, this.exportNodes.length - 1, row.exportPerMin, productName, recipe?.item_id || 0));
                index++;
            }

            // By-product export node (fix: use by-product name + icon)
            // @ts-ignore
            if (row.extraCells?.ExportPerMin > 0) {
                // @ts-ignore
                const byName = row.extraCells?.Product || recipe?.secondItemName || row.product;
                const byIcon = recipe?.item_id2 ? HtmlGeneration.getItemIconSrcForId(recipe.item_id2) : null;
                const qty = Number(row.extraCells?.ExportPerMin || 0);
                const node = new ExportNodes(index, byName, qty);
                (node as any).icon = byIcon;
                (node as any).titleHtml = this.buildSimpleTitleHtml('Export (by-product)', byIcon, byName, qty);
                this.exportNodes.push(node);
                this.exportConnections.push(new Connection(index, i, this.exportNodes.length - 1, qty, byName, recipe?.item_id2 || 0));
                index++;
            }
        }
    }

    private async loadCytoscapeExtensions() {
        const progress = (percent: number) => {
            $('#loadingProgressGraph').css('width', percent + '%');
        };

        progress(10);
        cytoscape = (await import("cytoscape")).default;
        progress(40);

        //@ts-ignore
        const { default: qtip } = await import("cytoscape-qtip");
        progress(65);

        // Advanced HTML node labels (multiple icons + rich layout inside node)
        // @ts-ignore
        const { default: nodeHtmlLabel } = await import('cytoscape-node-html-label');
        progress(80);

        const { default: klay } = await import("cytoscape-klay");
        progress(95);

        cytoscape.use(qtip);
        cytoscape.use(nodeHtmlLabel);
        cytoscape.use(klay);
        progress(100);

        await this.hideLoadingScreen();
    }


    private async hideLoadingScreen() {
        return new Promise<void>((resolve) => {
            const loadingScreen = $('#loadingScreenGraph');
            const graph = $('#graph');

            // Fade out only opacity, keep other styles intact
            loadingScreen.css('transition', 'opacity 0.5s');
            loadingScreen.css('opacity', '0');

            setTimeout(() => {
                loadingScreen.addClass('d-none'); // Hide the loading screen after fade out

                // Show the graph by removing a "hidden" class, instead of using fadeIn
                graph.removeClass('hidden-graph'); // This class can handle opacity/display safely
                resolve();
            }, 500);
        });
    }

    private async showLoadingScreen() {
        return new Promise<void>((resolve) => {
            const loadingScreen = $('#loadingScreenGraph');
            const graph = $('#graph');
            const loadingProgress = $('#loadingProgressGraph');

            // Reset the loading progress bar
            loadingProgress.css('width', '0%');

            // Show the loading screen
            loadingScreen.removeClass('d-none'); // Ensure it's visible
            loadingScreen.css('opacity', '1'); // Reset opacity to 1

            // Hide the graph by adding a "hidden" class, instead of using fadeOut
            graph.addClass('hidden-graph'); // This class can handle opacity/display safely
            resolve();
        });
    }

    private formatNumber(value: any): string {
        const n = Number(value ?? 0);
        if (Number.isNaN(n)) return String(value ?? '');
        if (n % 1 === 0) return n.toFixed(0);
        // Round to 5 decimals, then remove trailing zeros
        const rounded = Math.round(n * 100000) / 100000;
        return rounded.toFixed(5).replace(/0+$/, '').replace(/\.$/, '');
    }

    private escapeHtml(s: string): string {
        return String(s ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/\"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    private iconImg(src: string | null, title: string, size = 22): string {
        if (!src) return '';
        const t = this.escapeHtml(title);
        return `<img src="${src}" title="${t}" alt="${t}" style="width:${size}px;height:${size}px;object-fit:contain;vertical-align:middle;" loading="lazy">`;
    }

    private getBuildingIconSrc(className: string): string {
        return `/image/items/${className
            .replaceAll('_', '-')
            .replace(/build/gi, 'desc')
            .toLowerCase()}_256.png`;
    }

    private buildSimpleTitleHtml(kind: string, icon: string | null, name: string, quantity: number): string {
        return `
            <div style="max-width:100%;font-size:13px;line-height:1.25">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                    ${this.iconImg(icon, name, 26)}
                    <div>
                        <div style="font-weight:600">${this.escapeHtml(kind)}: ${this.escapeHtml(name)}</div>
                        <div style="color:#555">${this.formatNumber(quantity)}/min</div>
                    </div>
                </div>
            </div>
        `;
    }
    private buildImportNodeHtml(data: any): string {
        const icon = data?.icon || null;
        const product = String(data?.product || '');
        const quantity = this.formatNumber(data?.quantity ?? 0);
        const unit = String(data?.unit || '/min');
        const cardW = 200;
        const cardH = 110;

        return `
        <div style="width:${cardW}px;height:${cardH}px;pointer-events:none;box-sizing:border-box;overflow:hidden;display:flex;align-items:stretch;justify-content:stretch">
            <div style="width:${cardW}px;height:${cardH}px;box-sizing:border-box;overflow:hidden;background:#fff;border-radius:12px;padding:8px 10px;display:flex;flex-direction:column">

                <!-- Product section -->
                <div style="display:flex;gap:8px;align-items:flex-start;min-width:0">
                    <div style="flex:0 0 auto;padding-top:2px">
                        ${icon ? `<img src="${icon}" alt="${this.escapeHtml(product)}" style="width:30px;height:30px;object-fit:contain" loading="lazy">` : ''}
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:750;color:#111;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${this.escapeHtml(product)}</div>
                        <div style="display:grid;grid-template-columns:max-content 1fr;column-gap:8px;row-gap:2px;font-size:11px;color:#555;line-height:1.15;margin-top:3px;align-items:baseline">
                            <div>Qty</div><div style="text-align:right"><b>${this.escapeHtml(quantity)}</b>${this.escapeHtml(unit)}</div>
                        </div>
                    </div>
                </div>

                <!-- Label pinned to bottom -->
                <div style="margin-top:auto;padding:5px 8px;border-radius:10px;background:rgba(0,0,0,0.035)">
                    <div style="font-size:10.5px;font-weight:700;color:#0d6efd;text-transform:uppercase;letter-spacing:0.04em">Import</div>
                </div>

            </div>
        </div>
    `;
    }

    private buildExportNodeHtml(data: any): string {
        const icon = data?.icon || null;
        const product = String(data?.product || '');
        const quantity = this.formatNumber(data?.quantity ?? 0);
        const unit = String(data?.unit || '/min');
        const cardW = 200;
        const cardH = 110;

        return `
        <div style="width:${cardW}px;height:${cardH}px;pointer-events:none;box-sizing:border-box;overflow:hidden;display:flex;align-items:stretch;justify-content:stretch">
            <div style="width:${cardW}px;height:${cardH}px;box-sizing:border-box;overflow:hidden;background:#fff;border-radius:12px;padding:8px 10px;display:flex;flex-direction:column">

                <!-- Product section -->
                <div style="display:flex;gap:8px;align-items:flex-start;min-width:0">
                    <div style="flex:0 0 auto;padding-top:2px">
                        ${icon ? `<img src="${icon}" alt="${this.escapeHtml(product)}" style="width:30px;height:30px;object-fit:contain" loading="lazy">` : ''}
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12px;font-weight:750;color:#111;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${this.escapeHtml(product)}</div>
                        <div style="display:grid;grid-template-columns:max-content 1fr;column-gap:8px;row-gap:2px;font-size:11px;color:#555;line-height:1.15;margin-top:3px;align-items:baseline">
                            <div>Qty</div><div style="text-align:right"><b>${this.escapeHtml(quantity)}</b>${this.escapeHtml(unit)}</div>
                        </div>
                    </div>
                </div>

                <!-- Label pinned to bottom -->
                <div style="margin-top:auto;padding:5px 8px;border-radius:10px;background:rgba(0,0,0,0.035)">
                    <div style="font-size:10.5px;font-weight:700;color:#dc3545;text-transform:uppercase;letter-spacing:0.04em">Export</div>
                </div>

            </div>
        </div>
    `;
    }

    private buildProductionNodeHtml(data: any): string {
        const buildingIcon = data?.buildingIcon || null;
        const outputIcon = data?.icon || null;
        const byproductIcon = data?.byproductIcon || null;

        const buildingName = String(data?.buildingName || '');
        const buildingAmountNum = Number(data?.buildingAmount ?? 0);
        const buildingAmount = this.formatNumber(buildingAmountNum);

        const product = String(data?.product || '');
        const recipeName = String(data?.recipeName || '');

        const quantityNum = Number(data?.quantity ?? 0);
        const quantity = this.formatNumber(quantityNum);
        const unit = String(data?.unit || '');

        const exportNum = Number(data?.exportPerMin ?? 0);
        const exportPerMin = this.formatNumber(exportNum);

        const localNum = Number(data?.usagePerMin ?? 0);
        const localUsagePerMin = this.formatNumber(localNum);

        const clockSpeed = this.formatNumber(data?.clockSpeed ?? 100);
        const somersloop = data?.somersloop === true;

        // cytoscape-node-html-label does NOT size the label element to the node size.
        // So we must set explicit px dimensions to match Cytoscape's width/height.
        const cardW = Number(data?.cardWidth ?? 290);
        const cardH = Number(data?.cardHeight ?? 185);

        // Fill unused space with something meaningful: a split bar (Local vs Export vs Free).
        const safeQty = quantityNum > 0 ? quantityNum : 1;
        const localPct = Math.max(0, Math.min(localNum / safeQty, 1));
        const exportPct = Math.max(0, Math.min(exportNum / safeQty, 1 - localPct));
        const freeNum = Math.max(0, quantityNum - localNum - exportNum);
        const freePct = Math.max(0, 1 - localPct - exportPct);

        const perMachineQty = buildingAmountNum > 0 ? (quantityNum / buildingAmountNum) : null;

        const splitHtml = quantityNum > 0
            ? `
                <div style="margin-top:6px">
                    <div style="height:8px;border-radius:999px;overflow:hidden;background:rgba(0,0,0,0.06);display:flex">
                        <div style="width:${(localPct * 100).toFixed(1)}%;background:#198754"></div>
                        <div style="width:${(exportPct * 100).toFixed(1)}%;background:#dc3545"></div>
                        <div style="width:${(freePct * 100).toFixed(1)}%;background:#adb5bd"></div>
                    </div>
                    <div style="display:flex;justify-content:space-between;gap:8px;font-size:10px;color:#666;margin-top:3px;line-height:1">
                        <span style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis">Local ${this.formatNumber(localNum)}/min</span>
                        <span style="white-space:nowrap">Export ${this.formatNumber(exportNum)}/min</span>
                        <span style="white-space:nowrap">Free ${this.formatNumber(freeNum)}${this.escapeHtml(unit)}</span>
                    </div>
                </div>
            `
            : '';

        // pointer-events:none so dragging/selecting the node still works
        return `
            <div style="width:${cardW}px;height:${cardH}px;pointer-events:none;box-sizing:border-box;overflow:hidden;display:flex;align-items:stretch;justify-content:stretch">
                <div style="width:${cardW}px;height:${cardH}px;box-sizing:border-box;overflow:hidden;background:#fff;border-radius:12px;padding:8px 10px;display:flex;flex-direction:column">

                    <!-- Outputs section -->
                    <div style="display:flex;gap:10px;align-items:flex-start;min-width:0">
                        <div style="display:flex;align-items:center;gap:4px;flex:0 0 auto;padding-top:2px">
                            ${outputIcon ? `<img src="${outputIcon}" alt="${this.escapeHtml(product)}" style="width:34px;height:34px;object-fit:contain" loading="lazy">` : ''}
                            ${byproductIcon ? `<img src="${byproductIcon}" alt="by-product" style="width:22px;height:22px;object-fit:contain;opacity:0.95" loading="lazy">` : ''}
                        </div>

                        <div style="flex:1;min-width:0">
                            <div style="font-size:12px;font-weight:750;color:#111;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${this.escapeHtml(product)}</div>

                            <div style="display:grid;grid-template-columns:max-content 1fr;column-gap:8px;row-gap:2px;font-size:11px;color:#555;line-height:1.15;margin-top:3px;align-items:baseline">
                                <div>Qty</div><div style="text-align:right"><b>${this.escapeHtml(quantity)}</b>${this.escapeHtml(unit)}</div>
                                <div>Local usage</div><div style="text-align:right"><b>${this.escapeHtml(localUsagePerMin)}</b>/min</div>
                                <div>Export</div><div style="text-align:right"><b>${this.escapeHtml(exportPerMin)}</b>/min</div>
                            </div>

                            ${recipeName ? `<div style="font-size:10.5px;color:#777;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;margin-top:3px">Recipe: ${this.escapeHtml(recipeName)}</div>` : ''}
                            ${splitHtml}
                        </div>
                    </div>

                    <!-- Machine section (pinned to bottom so the spacing feels intentional) -->
                    <div style="margin-top:auto;padding:8px;border-radius:10px;background:rgba(0,0,0,0.035)">
                        <div style="display:flex;gap:10px;align-items:flex-start;min-width:0">
                            <div style="flex:0 0 auto;padding-top:2px">
                                ${buildingIcon ? `<img src="${buildingIcon}" alt="${this.escapeHtml(buildingName)}" style="width:30px;height:30px;object-fit:contain" loading="lazy">` : ''}
                            </div>

                            <div style="flex:1;min-width:0">
                                <div style="display:flex;gap:8px;align-items:baseline;min-width:0">
                                    <div style="font-size:12px;font-weight:750;color:#111;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;min-width:0">${this.escapeHtml(buildingName)}</div>
                                    <div style="margin-left:auto;font-size:12px;font-weight:750;color:#111;white-space:nowrap">x ${this.escapeHtml(buildingAmount)}</div>
                                </div>

                                <div style="display:grid;grid-template-columns:max-content 1fr;column-gap:8px;row-gap:2px;font-size:11px;color:#555;line-height:1.15;margin-top:3px;align-items:baseline">
                                    <div>Clock speed</div><div style="text-align:right"><b>${this.escapeHtml(clockSpeed)}</b>%</div>
                                    ${data?.somersloop === null ? '' : `<div>Somersloop</div><div style="text-align:right"><b>${somersloop ? 'On' : 'Off'}</b></div>`}
                                    ${perMachineQty === null ? '' : `<div>Per machine</div><div style="text-align:right"><b>${this.escapeHtml(this.formatNumber(perMachineQty))}</b>${this.escapeHtml(unit)}</div>`}
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        `;
    }

    private buildProductionTitleHtml(
        index: number,
        row: any,
        recipe: any,
        buildingAmount: number,
        outputIcon: string | null,
        byproductIcon: string | null,
        buildingIcon: string | null
    ): string {
        const recipeName = recipe?.name || '';
        const outputName = recipe?.itemName || row.product || '';
        const byName = recipe?.secondItemName || '';

        const buildingName = recipe?.building?.name || '';
        const buildingBlock = buildingName
            ? `
                <div style="display:flex;align-items:center;gap:8px;margin:6px 0">
                    ${buildingIcon ? this.iconImg(buildingIcon, buildingName, 24) : ''}
                    <div><b>${this.escapeHtml(buildingName)}</b> x ${this.formatNumber(buildingAmount)}</div>
                </div>
            `
            : '';

        const clockSpeed = row?.recipeSetting?.clockSpeed;
        const somersloop = row?.recipeSetting?.useSomersloop;

        const resources = Array.isArray(recipe?.resources) ? recipe.resources : [];
        const resourcesHtml = resources.length
            ? resources.map((r: any) => {
                const icon = HtmlGeneration.getItemIconSrcForId(r.itemId);
                return `<div style="display:flex;align-items:center;gap:6px;margin:2px 0">
                    ${this.iconImg(icon, r.name)}
                    <span>${this.escapeHtml(r.name)}</span>
                    <span style="margin-left:auto;color:#555">${this.formatNumber(r.importAmount)}/min</span>
                </div>`;
            }).join('')
            : `<div style="color:#777">(no inputs)</div>`;

        const externalImports = Array.isArray(row?.imports) ? row.imports : [];
        const externalHtml = externalImports.length
            ? externalImports.map((imp: any) => {
                const importRow = this.TableHandler.importsTableRows?.[imp.index];
                const icon = HtmlGeneration.getItemIconSrcForId(importRow?.itemId);
                const name = importRow?.product || imp.product;
                return `<div style="display:flex;align-items:center;gap:6px;margin:2px 0">
                    ${this.iconImg(icon, name)}
                    <span>${this.escapeHtml(name)}</span>
                    <span style="margin-left:auto;color:#555">${this.formatNumber(imp.amount)}/min</span>
                </div>`;
            }).join('')
            : `<div style="color:#777">(none)</div>`;

        const prodImports = Array.isArray(row?.productionImports) ? row.productionImports : [];
        const prodHtml = prodImports.length
            ? prodImports.map((imp: any) => {
                const sourceRow = this.TableHandler.productionTableRows?.[imp.index];
                const icon = imp.doubleExport
                    ? (sourceRow?.recipe?.item_id2 ? HtmlGeneration.getItemIconSrcForId(sourceRow.recipe.item_id2) : null)
                    : (sourceRow?.recipe?.item_id ? HtmlGeneration.getItemIconSrcForId(sourceRow.recipe.item_id) : null);
                const name = imp.product;
                const from = sourceRow?.recipe?.itemName || sourceRow?.product || sourceRow?.recipe?.name;
                return `<div style="display:flex;align-items:center;gap:6px;margin:2px 0">
                    ${this.iconImg(icon, name)}
                    <span>${this.escapeHtml(name)}</span>
                    <span style="margin-left:auto;color:#555">${this.formatNumber(imp.amount)}/min</span>
                    <span style="color:#999;margin-left:6px">from ${this.escapeHtml(from || '')}</span>
                </div>`;
            }).join('')
            : `<div style="color:#777">(none)</div>`;

        const byProductExport = row?.extraCells?.ExportPerMin;

        return `
            <div style="min-width:300px;max-width:460px;font-size:13px;line-height:1.25">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                    ${this.iconImg(outputIcon, outputName, 28)}
                    <div>
                        <div style="font-weight:700">${this.escapeHtml(outputName)}</div>
                        <div style="color:#555">Recipe: ${this.escapeHtml(recipeName)}</div>
                    </div>
                </div>

                ${buildingBlock}

                <div style="display:grid;grid-template-columns:max-content 1fr;column-gap:10px;row-gap:4px;margin:8px 0;align-items:baseline">
                    <div><b>Qty</b></div><div>${this.formatNumber(row.quantity)}/min</div>
                    <div><b>Export</b></div><div>${this.formatNumber(row.exportPerMin)}/min</div>
                    <div><b>Local usage</b></div><div>${this.formatNumber(row.Usage)}/min</div>
                    <div><b>Clock</b></div><div>${this.formatNumber(clockSpeed ?? 100)}%</div>
                    ${typeof somersloop === 'boolean' ? `<div><b>Somersloop</b></div><div>${somersloop ? 'On' : 'Off'}</div>` : ''}
                </div>

                <hr style="margin:8px 0">
                <div style="font-weight:600;margin-bottom:4px">Inputs (recipe resources)</div>
                ${resourcesHtml}

                <hr style="margin:8px 0">
                <div style="font-weight:600;margin-bottom:4px">Pulled from other recipes</div>
                ${prodHtml}

                <div style="font-weight:600;margin:8px 0 4px">Pulled from Imports table</div>
                ${externalHtml}

                ${byName && byProductExport ? `
                    <hr style="margin:8px 0">
                    <div style="font-weight:600;margin-bottom:4px">By-product export</div>
                    <div style="display:flex;align-items:center;gap:8px">
                        ${this.iconImg(byproductIcon, byName, 24)}
                        <div>${this.escapeHtml(byName)} — ${this.formatNumber(byProductExport)}/min</div>
                    </div>
                ` : ''}
            </div>
        `;
    }

    private buildEdgeLabelHtml(color: string, icon: string | null, product: string, qty: string): string {
        const dot = `<span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:${color};flex-shrink:0"></span>`;
        const img = icon
            ? `<img src="${icon}" alt="${this.escapeHtml(product)}" style="width:18px;height:18px;object-fit:contain;flex-shrink:0" loading="lazy">`
            : dot;

        return `
        <div style="
            display:inline-flex;
            align-items:center;
            gap:5px;
            background:#fff;
            border:1.5px solid ${color}22;
            border-radius:999px;
            padding:3px 8px 3px 5px;
            box-shadow:0 1px 4px rgba(0,0,0,0.10);
            font-size:11px;
            font-family:sans-serif;
            color:#222;
            white-space:nowrap;
            pointer-events:none;
            user-select:none;
        ">
            ${img}
            <span style="font-weight:600;color:#111;max-width:90px;overflow:hidden;text-overflow:ellipsis">${this.escapeHtml(product)}</span>
            <span style="color:${color};font-weight:700">${this.escapeHtml(qty)}</span>
            <span style="color:#888;font-size:10px">/min</span>
        </div>
    `;
    }

    private renderEdgeLabels(cy: Core): void {
        const container = document.getElementById('graph');
        if (!container) return;

        // Remove any previous overlays
        container.querySelectorAll('.cy-edge-label-overlay').forEach(el => el.remove());

        const pan = cy.pan();
        const zoom = cy.zoom();

        cy.edges().forEach(edge => {
            const data = edge.data();
            const icon = data.icon || null;
            const product = data.product || '';
            const qty = data.qty || '';
            const color = data.color || '#888';

            // Midpoint in rendered coordinates
            const midpoint = edge.midpoint();
            const x = midpoint.x * zoom + pan.x;
            const y = midpoint.y * zoom + pan.y;

            const wrapper = document.createElement('div');
            wrapper.className = 'cy-edge-label-overlay';
            wrapper.style.cssText = `
            position:absolute;
            left:${x}px;
            top:${y}px;
            transform:translate(-50%,-50%);
            pointer-events:none;
            z-index:10;
        `;
            wrapper.innerHTML = this.buildEdgeLabelHtml(color, icon, product, qty);
            container.appendChild(wrapper);
        });
    }

}