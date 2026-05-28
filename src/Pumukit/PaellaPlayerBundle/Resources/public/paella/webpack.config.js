const path = require('path');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');

const today = new Date();
const year = today.getFullYear();
const month = String(today.getMonth() + 1).padStart(2, '0');
const day = String(today.getDate()).padStart(2, '0');
const dateString = `${year}-${month}-${day}`;

module.exports = {
    entry: './src/index.js',
    output: {
        path: path.join(__dirname, "dist"),
        filename: `teltek-paella-player-${dateString}.js`,
        sourceMapFilename: `teltek-paella-player-${dateString}.js.map`
    },
    devtool: 'source-map',
    devServer: {
        port: 8080,
        allowedHosts: 'all',
        headers: {
            "Access-Control-Allow-Origin": "*",
            "Access-Control-Allow-Methods": "GET, POST, PUT, DELETE, PATCH, OPTIONS",
            "Access-Control-Allow-Headers": "X-Requested-With, content-type, Authorization"
        }
    },

    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /(node_modules)/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: ['@babel/preset-env']
                    }
                }
            },

            {
                test: /\.js$/,
                enforce: 'pre',
                use: ['source-map-loader']
            },

            {
                test: /\.css$/,
                use:  [
                    'style-loader',
                    'css-loader'
                ]
            },

            {
                test: /\.svg$/i,
                use: {
                    loader: 'svg-inline-loader'
                }
            },
            {
                test: /\.css$/i,
                use: ['style-loader', 'css-loader']
            }
        ]
    },

    plugins: [
        new CleanWebpackPlugin(),
        new CopyWebpackPlugin({
            patterns: [
                { from: './config', to: 'config' },
                { from: './src/index.html', to: 'index.html' },
                { from: './src/style.css', to: 'style.css' }
            ]
        })
    ],

    performance: {
        hints: false,
        maxEntrypointSize: 1048576,
        maxAssetSize: 1048576
    }
}
