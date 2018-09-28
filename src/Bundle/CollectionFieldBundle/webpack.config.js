
var Encore = require('@symfony/webpack-encore');
const { VueLoaderPlugin } = require('vue-loader');

Encore
    // the project directory where all compiled assets will be stored
    .setOutputPath('./Resources/public')

    // the public path used by the web server to access the previous directory
    .setPublicPath('/bundles/unitecmscollectionfield')

    // Used as a prefix to the *keys* in manifest.json
    .setManifestKeyPrefix('')

    .addEntry('main', './Resources/webpack/main.js')

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

// export the final configuration
module.exports = Encore.getWebpackConfig();
