const path = require('path');

module.exports = {
    entry: {
        tables: './public/TypeScript/Tables/index.ts',          // Entry point for Tables
        powerProduction: './public/TypeScript/PowerProduction/index.ts',  // Entry point for Power Production
        dedicatedServer: './public/TypeScript/DedicatedServer/index.ts',  // Entry point for Dedicated Server
    },
    output: {
        filename: '[name].js',  // Output filenames will match the entry keys (e.g., tables.js, dedicatedServer.js, powerProduction.js)
        path: path.resolve(__dirname, 'public/js'),
        library: '[name]',      // This makes the "dedicatedServer" entry accessible as a global variable when necessary
        libraryTarget: 'umd',   // Makes it compatible for browser and Node environments
        globalObject: 'this',   // Ensures compatibility in different environments
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
    // Only expose DedicatedServer globally
    optimization: {
        splitChunks: {
            cacheGroups: {
                dedicatedServer: {
                    name: 'dedicatedServer',
                    test: /DedicatedServer/,  // Target the DedicatedServer entry only
                    chunks: 'all',
                    enforce: true,
                },
            },
        },
    }
};
