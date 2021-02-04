const path = require('path');
const webpack = require('webpack');
const os = require('os')
const VueLoaderPlugin = require('vue-loader/lib/plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const OptimizeCSSAssetsPlugin = require('optimize-css-assets-webpack-plugin');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');

/**
 * Settings
 */

let localhostUrl = 'localhost'
if (process.argv.some(arg => arg === '0.0.0.0')) { // localhost over wifi
  Object.values(os.networkInterfaces()).forEach(interface => {
    const ip = interface.find(eth => /^192/.test(eth.address));
    if (ip) {
      localhostUrl = ip.address;
    }
  })
}

// localhost
let ariadneApiPath  = `http://${ localhostUrl }:8080/api`;

// staging
//let ariadneApiPath  = 'xxx';

// public
//let ariadneApiPath  = 'xxx';

let ariadneAssetPath = '/static/assets';

module.exports = env => {

  config = {
    entry: path.resolve(__dirname, './src/index.ts'),
    output: {
      path: path.resolve(__dirname, './dist'),
      publicPath: '/',
      filename: env.production ? '[name].[chunkhash].js' : '[name].[hash].js',
    },
    module: {
      rules: [
        {
          test: /\.vue$/,
          loader: 'vue-loader',
          options: {
            loaders: {
              'css': 'vue-style-loader!css-loader',
            }
          }
        },
        {
          test: /\.tsx?$/,
          loader: 'ts-loader',
          exclude: /node_modules/,
          options: {
            appendTsSuffixTo: [/\.vue$/],
            transpileOnly: true,
            experimentalWatchApi: true,
          }
        },
        {
          test: /\.(png|jpg|gif|svg|ttf|woff|woff2|eot)$/,
          loader: 'file-loader',
          options: {
            name: 'static/[name].[ext]?[hash]'
          }
        },
        {
          test: /\.css$/,
          use: [
            {
              loader: env.production ? MiniCssExtractPlugin.loader : 'vue-style-loader',
              options: {
                sourceMap: !env.production
              }
            },
            {
              loader: 'css-loader',
              options: {
                sourceMap: !env.production
              }
            },
            {
              loader: 'postcss-loader',
              options: {
                sourceMap: !env.production
              }
            }
          ]
        }
      ]
    },
    plugins: [

      new CleanWebpackPlugin(),
      new VueLoaderPlugin(),

      new MiniCssExtractPlugin({
        filename: "style.[contenthash].css"
      }),

      new HtmlWebpackPlugin({
        inject: false,
        hash: true,
        template: path.resolve(__dirname, './src/index.html'),
        filename: 'index.html',
        minify: env.production ? {
          collapseWhitespace: true,
          removeComments: true,
          minifyCSS: true,
          minifyJS: true
        } : false
      }),

      // Copy static content from static folder to dist
      new CopyWebpackPlugin({
        patterns: [
            { from: 'static', to: 'static' },
        ]
      })

    ],
    resolve: {
      extensions: ['.ts', '.js', '.vue', '.json'],
      alias: {
        '@': path.resolve(__dirname, 'src'),
        'vue$': 'vue/dist/vue.esm.js'
      }
    },
    performance: {
      hints: false
    },
    target: 'web'
  }

  /**
   * Production config
   */
  if (env.production) {
    console.log('ARIADNE Portal - Building with production config...');

    process.env.NODE_ENV = 'production';

    config.mode = 'production';
    config.devtool = '';
    config.optimization = {
      minimizer: [
        new TerserPlugin({}),
        new OptimizeCSSAssetsPlugin({})
      ],
    };

  /**
   * Development config
   */
  } else {
    console.log('ARIADNE Portal - Building with development config...');

    process.env.NODE_ENV = 'development';

    config.mode = 'development';
    config.devtool = '#eval-source-map';

    config.devServer = {
      contentBase: [path.join(__dirname, 'dist'), path.join(__dirname, 'static')],
      historyApiFallback: true,
      noInfo: true,
      host: 'localhost',
      port: 8081 // SND - If you need to run on port 80 you must run as root - (sudo npm run dev)
    };

  }

  ariadneApiPath = JSON.stringify(env.ariadneApiPath ? env.ariadneApiPath : ariadneApiPath);
  ariadneAssetPath = JSON.stringify(env.ariadneAssetPath ? env.ariadneAssetPath : ariadneAssetPath);
  ariadnePublicPath = JSON.stringify('/');

  let settings =  new webpack.DefinePlugin({
    'process.env.apiUrl': ariadneApiPath,
    'process.env.ARIADNE_PUBLIC_PATH': ariadnePublicPath,
    'process.env.ARIADNE_ASSET_PATH': ariadneAssetPath,
  });

  config.plugins.push(settings);
  return config;
}
