const Encore = require('@symfony/webpack-encore');

Encore
    .setOutputPath('public/static')
    .setPublicPath('/static')
    .addEntry('withholding_tax_certificates', absolutePath('/assets/js/withholding_tax_certificates.js'))
    .addEntry('withholding_tax_rule_js', absolutePath('/assets/v2/js/withholding_tax_rule.js'))
    .addEntry('withholding_tax_switch_js', absolutePath('/assets/v2/js/withholding_tax_switch.js'))
    .addEntry('masonry_js', absolutePath('/assets/v2/js/bootstrap3.masonry.min.js'))
    .addStyleEntry('withholding_tax_rule_css', absolutePath('/assets/v2/css/withholding_tax_rule.css'))
;

module.exports = Encore.getWebpackConfig();

function absolutePath(file) {
    return __dirname + '/Resources' + file;
}
