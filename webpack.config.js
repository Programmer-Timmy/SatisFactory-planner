const path = require('path');

module.exports = {
    entry: {
        tables: './public/TypeScript/Tables/index.ts',          // Entry point for Tables
        powerProduction: './public/TypeScript/PowerProduction/index.ts',  // Entry point for Power Production
    },
    output: {
        filename: '[name].js',  // Output filenames will match the entry keys (e.g., tables.js, dedicatedServer.js, powerProduction.js)
        path: path.resolve(__dirname, 'public/js'),
    },
    resolve: {
        extensions: ['.ts', '.js'],
        fallback: {
            "https": false,   // Added to handle the fallback requirement for Dedicated Server
        }
    },
    module: {
        rules: [
            {
                test: /\.ts$/,
                use: 'ts-loader',
                exclude: /node_modules/,
            },
        ],
    },
};
