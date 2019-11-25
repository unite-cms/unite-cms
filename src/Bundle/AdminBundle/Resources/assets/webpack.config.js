const Encore = require('@symfony/webpack-encore');
const MonacoEditorPlugin = require('monaco-editor-webpack-plugin');

// Manually configure the runtime environment if not already configured yet by the "encore" command.
// It's useful when you use tools that rely on webpack.config.js file.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore.reset();

module.exports = {
    build(lang = "en") {

        Encore
            .setOutputPath('public/build/')
            .setPublicPath('/build')

            .addEntry('unite', [
                __dirname + '/app.js',
                __dirname + `/vue/translations/${ lang }.js`,
                __dirname + '/mount.js'
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

            .copyFiles({
                from: __dirname + '/favicons',
                to: `favicons/[path][name].${ Encore.isProduction() ? '[hash:8].' : '' }[ext]`
            })
        ;

        return Encore;
    }
};
