const defaultConfig = require('@wordpress/scripts/config/webpack.config');

module.exports = {
    ...defaultConfig,
    entry: {
        admin: './assets/js/admin.js'
    },
    output: {
        ...defaultConfig.output,
        path: __dirname + '/assets/build'
    }
}; 