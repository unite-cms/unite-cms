var Encore = require('@symfony/webpack-encore');
var MonacoEditorPlugin = require('monaco-editor-webpack-plugin');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('public/build/')
    .setPublicPath('/build')

    .addEntry('unite', [
        './src/Bundle/AdminBundle/Resources/assets/app.js'
    ])

    .splitEntryChunks()
    .enableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())

    .configureBabel(() => {}, {
        useBuiltIns: 'usage',
        corejs: 3
    })

    .enableSassLoader()
    .enableVueLoader()

    .addPlugin(new MonacoEditorPlugin({
        languages: ['graphql']
    }))
;

module.exports = Encore.getWebpackConfig();
