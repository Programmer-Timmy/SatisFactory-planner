const path = require('path');

module.exports = {
    entry: './public/TypeScript/Tables/index.ts', // Updated entry point to TypeScript folder
    output: {
        filename: 'bundle.js',
        path: path.resolve(__dirname, 'public/js'),
    },
    resolve: {
        extensions: ['.ts', '.js'],
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

module.exports = {
    entry: './public/TypeScript/PowerProduction/index.ts', // Updated entry point to TypeScript folder
    output: {
        filename: 'powerProduction.js',
        path: path.resolve(__dirname, 'public/js'),
    },
    resolve: {
        extensions: ['.ts', '.js'],
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
