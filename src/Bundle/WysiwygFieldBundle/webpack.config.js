
const Encore = require('@symfony/webpack-encore');

const CKEditorWebpackPlugin = require( '@ckeditor/ckeditor5-dev-webpack-plugin' );
const { styles } = require( '@ckeditor/ckeditor5-dev-utils' );

Encore
    // the project directory where all compiled assets will be stored
    .setOutputPath('./Resources/public')

    // the public path used by the web server to access the previous directory
    .setPublicPath('/bundles/unitecmswysiwygfield')

    // Used as a prefix to the *keys* in manifest.json
    .setManifestKeyPrefix('')

    .addEntry('main', './Resources/webpack/main.js')

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
    .disableSingleRuntimeChunk()
    .disableImagesLoader()

    .addPlugin(new CKEditorWebpackPlugin({ language: 'en' }))
    .enablePostCssLoader(function(postCssConfigOptions){
        let options = styles.getPostCssConfig( {
            themeImporter: {
                themePath: require.resolve( '@ckeditor/ckeditor5-theme-lark' )
            },
            minify: Encore.isProduction()
        });
        postCssConfigOptions.plugins = options.plugins;
    })


    .addRule({
        test: /\.(svg|png|jpg|jpeg|gif|ico)/,
        exclude: __dirname + '/node_modules/@ckeditor',
        use: [{
            loader: 'file-loader',
            options: {
                filename: 'images/[name].[hash:8].[ext]',
                publicPath: '/build/'
            }
        }]
    })

    .addRule({
        test: /ckeditor5-[^/\\]+[/\\]theme[/\\]icons[/\\][^/\\]+\.svg$/,
        use: [{
            loader: 'raw-loader'
        }],
    });

// export the final configuration
module.exports = Encore.getWebpackConfig();
