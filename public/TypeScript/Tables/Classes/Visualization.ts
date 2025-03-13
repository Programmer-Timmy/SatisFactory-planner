import {TableHandler} from "./TableHandler";
import {ImportNodes} from "./Data/Visualization/ImportNodes";
import {ProductionNodes} from "./Data/Visualization/ProductionNodes";
import {ExportNodes} from "./Data/Visualization/ExportNodes";
import {Connection} from "./Data/Visualization/Connection";
import {Ext, LayoutOptions} from "cytoscape";

let cytoscape: typeof import("cytoscape")


// global variables
const TEXT_SIZE = 25;

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
    private useRoots: boolean = true;


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
        await this.loadCytoscapeExtensions();

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
                    style: {
                        "label": "data(label)",
                        'background-color': 'data(color)', // Middle label
                        "text-halign": "center",
                        "text-valign": "center",
                        'width': 200,
                        'height': 200,// Align middle
                        "color": "black",
                        "font-size": TEXT_SIZE,
                        "text-wrap": "wrap",
                    }
                },
                {
                    selector: "node.top-text",
                    style: {
                        'text-margin-y': -60,           // Adjusts position to appear above center
                    }
                },
                {
                    selector: "edge",
                    style: {
                        "label": "data(label)",         // Display label from data
                        "font-size": TEXT_SIZE,            // Customize font size if needed
                        "text-background-color": "white",
                        "text-background-opacity": 0.5,
                        "text-background-padding": '3px',
                        "text-background-shape": "roundrectangle",
                        "color": "black",               // Text color
                        "width": 5,                     // Line width
                        "line-color": "data(color)",    // Line color
                        "target-arrow-color": "#333",
                        "target-arrow-shape": "triangle",
                        "target-arrow-fill": "filled",
                        "curve-style": "bezier",
                        "control-point-step-size": 40,

                    }
                }
            ],
            layout: layout
        });

        cy.ready(() => {
            // @ts-ignore
            cy.nodes().forEach(node => {
                this.applyQTip(node, node.data('title'));

                node.on('drag', () => {
                    if (node.qtip('api').visible) {
                        node.qtip('api').hide();
                    }
                });

                node.on('mouseout', () => {
                    node.qtip('api').hide();
                });
            });

            // @ts-ignore
            cy.edges().forEach(edge => {
                this.applyQTip(edge, edge.data('label'));
            });

            cy.center();
            cy.fit();
        });


    }

    private applyQTip(element: cytoscape.NodeSingular | cytoscape.EdgeSingular, content: string): void {
        element.qtip({
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


    /**
     * Add a node to the visualization
     * @param node - The node to add
     * @param type - The type of the node
     * @private
     *
     * @returns {object} - The node object
     */
    private addNode(type: 'import' | 'export' | 'production', node: any): {
        data: { id: string, label: string, color: string, title: string },
        classes: string
    } {
        return {
            data: {
                id: `${type}_${node.id}`,
                label: `${node.product}\n${node.quantity}${node.building ? `\n\n\n\n${node.building}\n${node.buildingAmount}` : ''}`,
                color: type === 'import' ? 'blue' : type === 'export' ? 'red' : 'green',
                title: `${type.charAt(0).toUpperCase() + type.slice(1)}: ${node.product}<br>Amount: ${node.quantity}${node.building ? `<br><hr>Building: ${node.building}<br>Amount of building: ${node.buildingAmount}` : ''}`
            },
            classes: type === 'production' ? 'top-text' : ''
        }
    }

    private addConnection(type: 'import' | 'export' | 'production', connection: any, sourcePrefix: string, targetPrefix: string) {
        return {
            data: {
                id: `${type}Connection_${connection.id}`,
                source: `${sourcePrefix}_${connection.sourceId}`,
                target: `${targetPrefix}_${connection.targetId}`,
                label: `${connection.product} ${connection.quantity}`,
                color: type === 'import' ? 'blue' : type === 'export' ? 'red' : 'green'
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

            const building = row.recipe?.building;
            const amount = row.quantity;
            const recipe = row.recipe;

            if (!recipe) {
                continue;
            }

            let amountOfBuilding = (amount / recipe.export_amount_per_min).toFixed(3);

            if (building && recipe) {
                this.productionNodes.push(new ProductionNodes(i, recipe.name, row.quantity, building.name, building.id, +amountOfBuilding));

            }

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
            this.importNodes.push(new ImportNodes(i, row.product, row.quantity));
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

            if (row.exportPerMin > 0) {
                this.exportNodes.push(new ExportNodes(index, row.product, row.exportPerMin));
                this.exportConnections.push(new Connection(index, i, this.exportNodes.length - 1, row.exportPerMin, row.product));
                index++;
            }

            // @ts-ignore
            if (row.extraCells?.ExportPerMin > 0) {
                // @ts-ignore
                this.exportNodes.push(new ExportNodes(index, row.product, row.extraCells?.ExportPerMin));
                // @ts-ignore
                this.exportConnections.push(new Connection(index, i, this.exportNodes.length - 1, row.extraCells?.ExportPerMin, row.product));
                index++;
            }

        }
    }

    private async loadCytoscapeExtensions() {
        cytoscape = (await import("cytoscape")).default;

        //@ts-ignore
        const { default: qtip } = await import("cytoscape-qtip");
        const { default: klay } = await import("cytoscape-klay");

        cytoscape.use(qtip);
        cytoscape.use(klay);
    }


}