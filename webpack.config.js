const UniteCMSAdminEncore = require('./src/Bundle/AdminBundle/Resources/assets/webpack.config');
module.exports = UniteCMSAdminEncore.build([
    './src/Bundle/AdminBundle/Resources/assets/app.js',
    './src/Bundle/AdminBundle/Resources/assets/tiptap.js',
    './src/Bundle/MediaBundle/Resources/assets/app.js',
    './src/Bundle/AdminBundle/Resources/assets/vue/translations/en.js',
    './src/Bundle/AdminBundle/Resources/assets/mount.js',
]).getWebpackConfig();
