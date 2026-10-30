const path = require('path');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');

module.exports = {
    entry: {
        'teltek-paella-player': './src/index.js'
    },
    output: {
        path: path.join(__dirname, "dist"),
        filename: '[name].[contenthash].js',
        clean: true
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
        new CopyWebpackPlugin({
            patterns: [
                { from: './config', to: 'config' },
                { from: './src/style.css', to: 'style.css' }
            ]
        }),
        new HtmlWebpackPlugin({
            template: './src/index.html',
            inject: 'head',
            scriptLoading: 'blocking'
        }),
        new WebpackManifestPlugin({
            publicPath: ''
        })
    ],

    performance: {
        hints: false,
        maxEntrypointSize: 1048576,
        maxAssetSize: 1048576
    }
}
