
const Encore = require('@symfony/webpack-encore');

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

    // load vue components
    .enableVueLoader()

    // Enable sourcemaps in production mode
    .enableSourceMaps(!Encore.isProduction())

    // empty the outputPath dir before each build
    .cleanupOutputBeforeBuild()

    // versioning to avoid browser cache loading old assets
    .enableVersioning(Encore.isProduction())

    // We don't need a runtime.js for unite cms at the moment
    .disableSingleRuntimeChunk();

// export the final configuration
module.exports = Encore.getWebpackConfig();
