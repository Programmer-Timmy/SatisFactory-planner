<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Network Graph</title>
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }

        svg {
            border: 1px solid #ccc;
            width: 100%;
            height: 600px;
        }

        .node circle {
            stroke: #fff;
            stroke-width: 1.5px;
        }

        .link {
            stroke: #999;
            stroke-opacity: 0.6;
            stroke-width: 2; /* Make the link thicker */
        }

        .node text {
            font-size: 10px;
            fill: white;
        }
    </style>
</head>
<body>

<h1>Production Network Graph</h1>
<svg id="graph"></svg>

<script>
    // Sample Data
    const nodes = [
        {id: 'Copper Ingot', x: 100, y: 100},
        {id: 'Wire', x: 300, y: 150},
        {id: 'Cable', x: 500, y: 150},
        {id: 'Copper Sheet', x: 200, y: 250},
        {id: 'Iron Ingot', x: 100, y: 400},
        {id: 'Iron Plate', x: 300, y: 350},
        {id: 'Iron Rod', x: 400, y: 500},
        {id: 'Screw', x: 500, y: 450},
        {id: 'Concrete', x: 650, y: 300},
    ];

    // Links (Dependencies)
    const links = [
        {source: 'Copper Ingot', target: 'Wire'},  // Wire produced from Copper Ingot
        {source: 'Wire', target: 'Cable'},          // Cable produced from Wire
        {source: 'Iron Ingot', target: 'Iron Plate'},// Iron Plate produced from Iron Ingot
        {source: 'Iron Ingot', target: 'Iron Rod'},// Iron Plate produced from Iron Ingot
        {source: 'Iron Rod', target: 'Screw'},    // Screw produced from Iron Plate
        {source: 'Copper Ingot', target: 'Copper Sheet'}, // Copper Sheet produced from Copper Ingot
    ];

    // Set dimensions for SVG
    const svg = d3.select('#graph')
        .attr('width', window.innerWidth - 40) // Full width minus padding
        .attr('height', 600);

    // Create links
    const link = svg.append('g')
        .attr('class', 'links')
        .selectAll('line')
        .data(links)
        .enter().append('line')
        .attr('class', 'link')
        .attr('stroke-width', 2) // Make links thicker for visibility
        .attr('marker-end', 'url(#arrow)'); // Add an arrowhead to links

    // Create arrow marker for links
    svg.append('defs').append('marker')
        .attr('id', 'arrow')
        .attr('viewBox', '0 0 10 10')
        .attr('refX', 15)
        .attr('refY', 5)
        .attr('markerWidth', 6)
        .attr('markerHeight', 6)
        .attr('orient', 'auto')
        .append('polygon')
        .attr('points', '0 0, 10 5, 0 10')
        .attr('fill', '#999');

    // Create nodes
    const node = svg.append('g')
        .attr('class', 'nodes')
        .selectAll('g')
        .data(nodes)
        .enter().append('g')
        .attr('class', 'node')
        .attr('transform', d => `translate(${d.x}, ${d.y})`); // Use predefined positions

    node.append('circle')
        .attr('r', 8)
        .attr('fill', '#69b3a2');

    node.append('text')
        .attr('dx', 12)
        .attr('dy', '.35em')
        .text(d => d.id);

    // Positioning links based on static node positions
    link.attr('x1', d => {
        const sourceNode = nodes.find(node => node.id === d.source);
        return sourceNode.x;
    })
        .attr('y1', d => {
            const sourceNode = nodes.find(node => node.id === d.source);
            return sourceNode.y;
        })
        .attr('x2', d => {
            const targetNode = nodes.find(node => node.id === d.target);
            return targetNode.x;
        })
        .attr('y2', d => {
            const targetNode = nodes.find(node => node.id === d.target);
            return targetNode.y;
        });

    // Log nodes and links to ensure they are being created
    console.log("Nodes:", nodes);
    console.log("Links:", links);

</script>

</body>
</html>
