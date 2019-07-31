
const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('./Resources/public')
    .setPublicPath('/bundles/unitecmscore')
    .setManifestKeyPrefix('')

    .addEntry('main', './Resources/webpack/main.js')
    .addEntry('email', './Resources/webpack/email.scss')

    .enableSassLoader()
    .enableVueLoader()
    .enableSourceMaps(!Encore.isProduction())
    .cleanupOutputBeforeBuild()
    .enableVersioning(Encore.isProduction())
    .configureBabel(() => {}, {
        useBuiltIns: 'usage',
        corejs: 3
    })
    .disableSingleRuntimeChunk();

// export the final configuration
module.exports = Encore.getWebpackConfig();
/*let config = Encore.getWebpackConfig();
config.resolve.alias = {
    'uikit-util': path.resolve(__dirname, 'node_modules/uikit/src/js/util')
};
module.exports = config;
*/