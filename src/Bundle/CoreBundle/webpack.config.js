
const Encore = require('@symfony/webpack-encore');
const webpack = require('webpack');
const { VueLoaderPlugin } = require('vue-loader');
const UglifyJsPlugin = require('uglifyjs-webpack-plugin');

Encore
    // the project directory where all compiled assets will be stored
    .setOutputPath('./Resources/public')

    // the public path used by the web server to access the previous directory
    .setPublicPath('/bundles/unitecmscore')

    // Used as a prefix to the *keys* in manifest.json
    .setManifestKeyPrefix('')

    .addEntry('main', './Resources/webpack/main.js')
    .addEntry('email', './Resources/webpack/email.scss')

    // allow sass/scss files to be processed
    .enableSassLoader(function(sassConfigOptions){
        sassConfigOptions.includePaths = ['./node_modules'];
    })

    // https://github.com/symfony/webpack-encore/issues/311#issuecomment-411787830
    .addLoader({ test: /\.vue$/, loader: 'vue-loader' })
    .addPlugin(new VueLoaderPlugin())
    .addAliases({ vue: 'vue/dist/vue.js' })

    // allow legacy applications to use $/jQuery as a global variable
    //.autoProvidejQuery()

    .enableSourceMaps(!Encore.isProduction())

    // empty the outputPath dir before each build
    .cleanupOutputBeforeBuild()

    // versioning to avoid browser cache loading old assets
    .enableVersioning(Encore.isProduction());

    // show OS notifications when builds finish/fail
    //.enableBuildNotifications();

const webpackConfig = Encore.getWebpackConfig();

// Remove the old version first
webpackConfig.plugins = webpackConfig.plugins.filter(
    plugin => !(plugin instanceof webpack.optimize.UglifyJsPlugin)
);

// Add the new one
webpackConfig.plugins.push(new UglifyJsPlugin());

// export the final configuration
module.exports = webpackConfig;
