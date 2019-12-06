const HtmlWebpackPlugin = require('html-webpack-plugin');
// const MiniCssExtractPlugin = require("mini-css-extract-plugin");
// const path = require("path");

module.exports = {
  publicPath: '/bundles/billing/frontend/',
  outputDir: '../public/frontend',
  filenameHashing: false,
  productionSourceMap: false,
  pages: {
    vue_my_app: {
      inject: false,
      entry: 'src/main.js',
      template: 'public/my_app.html.twig',
      filename: '../../templates/frontend/my_app.html.twig',
      chunks: ['vue_create_nodes', 'chunk-vendors', 'chunk-common', 'index']
    },
  },
  configureWebpack: {
    output: {
      filename: "../../public/frontend/js/[name].js",
    }
  }
}
