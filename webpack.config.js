const path = require('path');

module.exports = {
    mode: 'development', // Set the mode to 'development' (or 'production' as needed)
    entry: './public/TypeScript/index.ts', // Updated entry point to TypeScript folder
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
