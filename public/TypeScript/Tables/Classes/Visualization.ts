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
const TEXT_SIZE = 16;
const EDGE_TEXT_SIZE = 14;

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

                        "width": 190,
                        "height": 170,
                        "shape": "roundrectangle",
                        "background-color": "#ffffff",
                        "border-width": 6,
                        "border-color": "data(color)",

                        "background-image": "data(icon)",
                        "background-fit": "contain",
                        "background-repeat": "no-repeat",
                        "background-position-x": "50%",
                        "background-position-y": "28%",
                        "background-width": "70%",
                        "background-height": "70%",
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
                    selector: "edge",
                    style: ({
                        "label": "data(label)",
                        "font-size": EDGE_TEXT_SIZE,
                        "text-background-color": "white",
                        "text-background-opacity": 0.7,
                        "text-background-padding": "4px",
                        "text-background-shape": "roundrectangle",
                        "color": "#111",
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
            layout: layout
        });

        cy.ready(() => {
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
        (element as any).qtip({
            content: content,
            position: {
                my: 'top center',
                at: 'bottom center'
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

        const label = `${node.product}\n${qty}${unit}`;
        const color = type === 'import' ? '#0d6efd' :
            type === 'export' ? '#dc3545' : this.showChecklist ? this.getColor(node.checklist) : '#28A745';

        const title = node.titleHtml
            ? node.titleHtml
            : `${type.charAt(0).toUpperCase() + type.slice(1)}: <b>${node.product}</b><br>Amount: ${qty}${unit}`;

        return {
            data: {
                id: `${type}_${node.id}`,
                label,
                color,
                title,
                icon: node.icon || null
            },
            classes: `${type}-node`
        }
    }

    private addConnection(type: 'import' | 'export' | 'production', connection: any, sourcePrefix: string, targetPrefix: string) {
        const qty = this.formatNumber(connection.quantity);
        const label = `${connection.product}\n${qty}/min`;
        return {
            data: {
                id: `${type}Connection_${connection.id}`,
                source: `${sourcePrefix}_${connection.sourceId}`,
                target: `${targetPrefix}_${connection.targetId}`,
                label,
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

            for (let j = 0; j < row.imports.length; j++) {
                const importRow = row.imports[j];
                this.importConnections.push(new Connection(index, importRow.index, i, +importRow.amount.toFixed(3), importRow.product));
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
            (node as any).titleHtml = this.buildProductionTitleHtml(i, row, recipe, buildingAmount, outputIcon, byproductIcon, buildingIcon);

            this.productionNodes.push(node);

            for (let j = 0; j < row.productionImports.length; j++) {
                const importRow = row.productionImports[j];
                this.productionConnections.push(new Connection(i, importRow.index, i, +importRow.amount.toFixed(3), importRow.product));
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
                this.exportConnections.push(new Connection(index, i, this.exportNodes.length - 1, row.exportPerMin, productName));
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
                this.exportConnections.push(new Connection(index, i, this.exportNodes.length - 1, qty, byName));
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
        progress(70);

        const { default: klay } = await import("cytoscape-klay");
        progress(90);

        cytoscape.use(qtip);
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
        return n.toFixed(5).replace(/0+$/, '').replace(/\.$/, '');
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
            <div style="min-width:260px">
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
            <div style="min-width:340px">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                    ${this.iconImg(outputIcon, outputName, 28)}
                    <div>
                        <div style="font-weight:700">${this.escapeHtml(outputName)}</div>
                        <div style="color:#555">Recipe: ${this.escapeHtml(recipeName)}</div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:6px;margin:8px 0">
                    <div><b>Qty</b>: ${this.formatNumber(row.quantity)}/min</div>
                    <div><b>Export</b>: ${this.formatNumber(row.exportPerMin)}/min</div>
                    <div><b>Usage</b>: ${this.formatNumber(row.Usage)}/min</div>
                    <div><b>Clock</b>: ${this.formatNumber(clockSpeed ?? 100)}%</div>
                </div>

                ${typeof somersloop === 'boolean' ? `<div style="margin-bottom:6px"><b>Somersloop</b>: ${somersloop ? 'On' : 'Off'}</div>` : ''}

                ${buildingIcon ? `
                    <div style="display:flex;align-items:center;gap:8px;margin:8px 0">
                        ${this.iconImg(buildingIcon, recipe?.building?.name || 'Building', 26)}
                        <div><b>${this.escapeHtml(recipe?.building?.name || '')}</b> × ${this.formatNumber(buildingAmount)}</div>
                    </div>
                ` : ''}

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
                    <div style="display:flex;align-items:center;gap:8px">
                        ${this.iconImg(byproductIcon, byName, 24)}
                        <div><b>By-product export</b>: ${this.escapeHtml(byName)} — ${this.formatNumber(byProductExport)}/min</div>
                    </div>
                ` : ''}
            </div>
        `;
    }

}