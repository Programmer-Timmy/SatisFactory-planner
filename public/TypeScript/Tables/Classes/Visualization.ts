import {TableHandler} from "./TableHandler";
import {ImportNodes} from "./Data/Visualization/ImportNodes";
import {ProductionNodes} from "./Data/Visualization/ProductionNodes";
import {ExportNodes} from "./Data/Visualization/ExportNodes";
import {Connection} from "./Data/Visualization/Connection";
import * as d3 from 'd3';

// global variables
const NODE_SIZE = 50;
const INNER_NODE_SIZE = 40;
const ROW_SPACING = 200;
const COLUMN_SPACING = 400;
const IMPORT_ROW_SPACING = 200;
const INSIDE_IMPORT_COLUMN_SPACING = 100;
const START_X = 200;
const START_Y = 100;
const TEXT_SIZE = 20;
const DOUBLE_OFFSET = 50;
const ARROW_SIZE = 10;

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

    /**
     * Constructor for the Visualization class
     * @constructor
     * @param {TableHandler} tableHandler - The table handler object
     */
    constructor(tableHandler: TableHandler) {
        this.TableHandler = tableHandler;
        console.log('Visualization constructor');
        this.getImportNodes();
        this.getProduction();
        this.getExportNodes();

        this.getImportConnection();

        console.log(this);

        this.getPosition().then(() => {
            this.positionImportNodes()
            // this.positionExportNodes()
            this.createVisualization()
        });

    }

    /**
     * Create the visualization of the production line
     */
    public createVisualization(): void {
        const svg = d3.select('#graph');
        svg.selectAll('*').remove();

        const width = +svg.attr('width');
        const height = +svg.attr('height');

        // Create a group to hold all visualization elements (nodes and links)
        const container = svg.append('g');

        // Add zoom and pan behavior
        const zoom = d3.zoom()
            .scaleExtent([0.5, 5]) // Restrict zooming scale between 0.5x and 5x
            .on('zoom', (event) => {
                container.attr('transform', event.transform); // Apply zooming and panning to the group
            });

        // Apply the zoom behavior to the SVG
        // @ts-ignore
        svg.call(zoom);

        // Import Links
// Add the arrow to the SVG
        svg.append('defs').append('marker')
            .attr('id', 'arrow')
            .attr('viewBox', '0 -5 10 10')
            .attr('refX', INNER_NODE_SIZE / 2 + ARROW_SIZE)
            .attr('refY', 0)
            .attr('markerWidth', ARROW_SIZE)
            .attr('markerHeight', ARROW_SIZE)
            .attr('orient', 'auto')
            .append('path')
            .attr('d', 'M0,-5L10,0L0,5')
            .attr('fill', '#999');

// Import Links
        const importLinks = container.append('g')
            .attr('class', 'links')
            .selectAll('line')
            .data(this.importConnections)
            .enter().append('line')
            .attr('stroke', 'blue')
            .attr('stroke-width', 2)
            .attr('marker-end', 'url(#arrow)'); // Ensure this is correct

// Production Links
        const productionLinks = container.append('g')
            .attr('class', 'links')
            .selectAll('line')
            .data(this.productionConnections)
            .enter().append('line')
            .attr('stroke', 'green')
            .attr('stroke-width', 2)
            .attr('marker-end', 'url(#arrow)'); // Ensure this is correct


        // Export Links (if needed, you can uncomment and use this)
        const exportLinks = container.append('g')
            .attr('class', 'links')
            .selectAll('line')
            .data(this.exportConnections)
            .enter().append('line')
            .attr('stroke', 'red')
            .attr('stroke-width', 2)
            .attr('marker-end', 'url(#arrow)'); // Ensure this is correct

        // Import Nodes
        const importNodes = container.append('g')
            .attr('class', 'nodes')
            .selectAll('g')
            .data(this.importNodes)
            .enter().append('g')
            .attr('transform', (d: any) => `translate(${d.X}, ${d.Y})`);

        // Production Nodes
        const productionNodes = container.append('g')
            .attr('class', 'nodes')
            .selectAll('g')
            .data(this.productionNodes)
            .enter().append('g')
            .attr('transform', (d: any) => `translate(${d.X}, ${d.Y})`);

        const exportNodes = container.append('g')
            .attr('class', 'nodes')
            .selectAll('g')
            .data(this.exportNodes)
            .enter().append('g')
            .attr('transform', (d: any) => `translate(${d.X}, ${d.Y})`);

        // Draw circles for the nodes
        importNodes.append('circle')
            .attr('r', INNER_NODE_SIZE)
            .attr('fill', 'blue');

        productionNodes.append('circle')
            .attr('r', INNER_NODE_SIZE)
            .attr('fill', 'green');

        exportNodes.append('circle')
            .attr('r', INNER_NODE_SIZE)
            .attr('fill', 'red');

        // Add text labels
        importNodes.append('text')
            .attr('x', INNER_NODE_SIZE + 5)
            .attr('y', -20)
            .attr('font-size', TEXT_SIZE)
            .text((d: any) => d.product);

        productionNodes.append('text')
            .attr('x', INNER_NODE_SIZE + 5)
            .attr('y', -20)
            .attr('font-size', TEXT_SIZE)
            .text((d: any) => d.product);

        exportNodes.append('text')
            .attr('x', INNER_NODE_SIZE + 5)
            .attr('y', -20)
            .attr('font-size', TEXT_SIZE)
            .text((d: any) => d.product);

        // Export Nodes


        // Set link positions
        importLinks
            .attr('x1', (d: any) => this.importNodes[d.sourceId].X)
            .attr('y1', (d: any) => this.importNodes[d.sourceId].Y)
            .attr('x2', (d: any) => this.productionNodes[d.targetId].X)
            .attr('y2', (d: any) => this.productionNodes[d.targetId].Y);

        productionLinks
            .attr('x1', (d: any) => this.productionNodes[d.sourceId].X)
            .attr('y1', (d: any) => this.productionNodes[d.sourceId].Y)
            .attr('x2', (d: any) => this.productionNodes[d.targetId].X)
            .attr('y2', (d: any) => this.productionNodes[d.targetId].Y);

        exportLinks
            .attr('x1', (d: any) => this.productionNodes[d.sourceId].X)
            .attr('y1', (d: any) => this.productionNodes[d.sourceId].Y)
            .attr('x2', (d: any) => this.exportNodes[d.targetId].X)
            .attr('y2', (d: any) => this.exportNodes[d.targetId].Y);

        const nodes = container.selectAll('.nodes g');
        const bounds = nodes.nodes().reduce((acc, node) => {
            // @ts-ignore
            const bbox = node.getBBox();
            return {
                x: Math.min(acc.x, bbox.x),
                y: Math.min(acc.y, bbox.y),
                width: Math.max(acc.width, bbox.width + (bbox.x - acc.x)),
                height: Math.max(acc.height, bbox.height + (bbox.y - acc.y)),
            };
        }, {x: Infinity, y: Infinity, width: 0, height: 0});
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
                this.importConnections.push(new Connection(index, importRow.index, i, importRow.amount, importRow.product));
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

            if (building) {
                this.productionNodes.push(new ProductionNodes(i, row.product, row.quantity, building.name, building.id));
            }

            for (let j = 0; j < row.productionImports.length; j++) {
                const importRow = row.productionImports[j];
                this.productionConnections.push(new Connection(i, importRow.index, i, importRow.amount, importRow.product));
            }
        }
    }

    async getPosition(): Promise<void> {
        const levels: Map<number, number[]> = new Map(); // Level -> List of Y positions

        // Helper function to position nodes recursively (now async)
        const positionNode = async (nodeId: number, level: number): Promise<void> => {
            if (!levels.has(level)) {
                levels.set(level, []);
            }

            const currentNode = this.productionNodes[nodeId];

            // If the node has already been positioned, return early
            if (currentNode.Y) {
                return;
            }

            const yPosition = (levels.get(level)?.length || 0) * ROW_SPACING + START_Y;
            levels.get(level)?.push(yPosition);

            // Position the current node
            currentNode.X = START_X + level * COLUMN_SPACING;
            currentNode.Y = yPosition;

            // Get child nodes and position them recursively
            const childConnections = this.productionConnections.filter(conn => conn.sourceId === nodeId);
            await Promise.all(childConnections.map(conn => positionNode(conn.targetId, level + 1)));
        };

        // Get root nodes (nodes with no incoming connections)
        const rootNodes = this.productionNodes.filter(node =>
            !this.productionConnections.some(conn => conn.targetId === node.id));

        // Position root nodes asynchronously
        await Promise.all(rootNodes.map(rootNode => positionNode(rootNode.id, 0)));
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

    private positionImportNodes(): void {
        for (let i = 0; i < this.importNodes.length; i++) {
            // if it hase a connection to a production node move it closer to the production node
            const connection = this.importConnections.filter(conn => conn.sourceId === i);

            if (connection && connection.length > 1) {
                const ConnectionAmount = connection.length;

                // grab the middle connection if not even move it to the middle of the connections
                const middleConnection = connection[Math.floor(ConnectionAmount / 2)];
                const X = this.productionNodes[middleConnection.targetId].X - IMPORT_ROW_SPACING;
                let Y = this.productionNodes[middleConnection.targetId].Y;

                if (X > START_X) {
                    Y -= INSIDE_IMPORT_COLUMN_SPACING;
                }

                this.importNodes[i].X = X;
                this.importNodes[i].Y = Y;
            } else if (connection && connection.length === 1) {
                const samePosition = this.importNodes.filter(node => node.X === this.productionNodes[connection[0].targetId].X - IMPORT_ROW_SPACING && node.Y === this.productionNodes[connection[0].targetId].Y);
                const X = this.productionNodes[connection[0].targetId].X - IMPORT_ROW_SPACING;
                let Y = this.productionNodes[connection[0].targetId].Y;

                if (X > START_X) {
                    Y -= INSIDE_IMPORT_COLUMN_SPACING;
                }

                if (samePosition.length > 0) {
                    this.importNodes[i].X = X;
                    this.importNodes[i].Y = Y + DOUBLE_OFFSET;
                    samePosition[0].Y = Y - DOUBLE_OFFSET;
                } else {
                    this.importNodes[i].X = X;
                    this.importNodes[i].Y = Y;
                }
            }
        }
    }

    private positionExportNodes(): void {
        for (let i = 0; i < this.exportNodes.length; i++) {
            const connection = this.exportConnections.filter(conn => conn.sourceId === i);
            console.log(connection);
            if (connection && connection.length > 1) {
                const ConnectionAmount = connection.length;

                // grab the middle connection if not even move it to the middle of the connections
                const middleConnection = connection[Math.floor(ConnectionAmount / 2)];
                const X = this.productionNodes[middleConnection.sourceId].X;
                let Y = this.productionNodes[middleConnection.sourceId].Y;

                if (X > START_X) {
                    Y -= INSIDE_IMPORT_COLUMN_SPACING;
                }

                this.exportNodes[i].X = X;
                this.exportNodes[i].Y = Y;
            } else if (connection && connection.length === 1) {
                const samePosition = this.exportNodes.filter(node => node.X === this.productionNodes[connection[0].sourceId].X + IMPORT_ROW_SPACING && node.Y === this.productionNodes[connection[0].sourceId].Y);
                const X = this.productionNodes[connection[0].sourceId].X;
                let Y = this.productionNodes[connection[0].sourceId].Y;

                if (X >= START_X) {
                    Y -= INSIDE_IMPORT_COLUMN_SPACING;
                }

                if (samePosition.length > 0) {
                    this.exportNodes[i].X = X + IMPORT_ROW_SPACING;
                    this.exportNodes[i].Y = Y;
                    samePosition[0].X = X - IMPORT_ROW_SPACING;
                } else {
                    this.exportNodes[i].X = X;
                    this.exportNodes[i].Y = Y;
                }
            }
        }
    }
}