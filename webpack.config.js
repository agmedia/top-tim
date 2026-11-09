const path = require('path');
const fs = require('fs');
const webpack = require('webpack');
const { VueLoaderPlugin } = require('vue-loader');

class VendorAssetsPlugin {
    apply(compiler) {
        compiler.hooks.thisCompilation.tap('VendorAssetsPlugin', compilation => {
            compilation.hooks.processAssets.tap({
                name: 'VendorAssetsPlugin',
                stage: webpack.Compilation.PROCESS_ASSETS_STAGE_ADDITIONAL,
            }, () => {
                compilation.emitAsset('vendor/jquery.min.js', new webpack.sources.RawSource(
                    fs.readFileSync(require.resolve('jquery/dist/jquery.min.js')),
                ));
            });
        });
    }
}

module.exports = {
    entry: {
        app: './resources/js/app.js',
        'laravel.app': './resources/js/app.js',
        cart: './resources/js/front/cart/app.js',
        filter: './resources/js/front/filter/app.js',
        'dashmix.app': './resources/js/dashmix/app.js',
        'ag-input-field': './resources/js/back/ag-input-field.js',
        'ag-star-rating': './resources/js/back/ag-star-rating.js',
        'ag-autocomplete': './resources/js/back/ag-autocomplete.js',
        'ag-autosuggestion': './resources/js/back/ag-autosuggestion.js',
        'components/ag-order-products': './resources/js/back/ag-order-products.js',
    },
    output: {
        path: path.resolve(__dirname, 'public/js'),
        filename: '[name].js',
        clean: false,
    },
    resolve: {
        extensions: ['.js', '.vue', '.json'],
        alias: { vue$: 'vue/dist/vue.esm-bundler.js' },
    },
    externals: { jquery: 'jQuery' },
    module: {
        rules: [
            { test: /\.vue$/, loader: 'vue-loader' },
            {
                test: /\.js$/, exclude: /node_modules/,
                use: { loader: 'babel-loader', options: { presets: [['@babel/preset-env', { targets: 'defaults' }]] } },
            },
            { test: /\.css$/, use: ['style-loader', 'css-loader'] },
            { test: /\.s[ac]ss$/, use: ['style-loader', 'css-loader', 'sass-loader'] },
            { test: /\.(woff2?|ttf|eot|svg|png|jpg|gif)$/, type: 'asset/resource', generator: { filename: 'build-assets/[hash][ext]' } },
        ],
    },
    plugins: [
        new VueLoaderPlugin(),
        new VendorAssetsPlugin(),
        new webpack.ProvidePlugin({ $: 'jquery', jQuery: 'jquery' }),
        new webpack.DefinePlugin({
            __VUE_OPTIONS_API__: true,
            __VUE_PROD_DEVTOOLS__: false,
            __VUE_PROD_HYDRATION_MISMATCH_DETAILS__: false,
        }),
    ],
    optimization: { splitChunks: false, runtimeChunk: false },
    devtool: false,
    stats: 'errors-warnings',
};
