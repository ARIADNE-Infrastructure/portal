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

let ariadneApiPath = `http://${ localhostUrl }:8080/api`;
let ariadneAssetPath = '/static/assets';

module.exports = env => {

  config = {
    entry: path.resolve(__dirname, './src/index.ts'),
    output: {
      path: path.resolve(__dirname, './dist'),
      publicPath: '/',
      filename: env.development ? '[name].[hash].js' : '[name].[chunkhash].js',
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
              loader: env.development ? 'vue-style-loader' : MiniCssExtractPlugin.loader,
              options: {
                sourceMap: env.development
              }
            },
            {
              loader: 'css-loader',
              options: {
                sourceMap: env.development
              }
            },
            {
              loader: 'postcss-loader',
              options: {
                sourceMap: env.development
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
        minify: !env.development ? {
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
   * Development config
   */
  if (env.development) {
    console.log('ARIADNE Portal - Building with development config...');

    process.env.NODE_ENV = 'development';

    ariadneApiPath = `http://${ localhostUrl }:8080/api`;

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

  /**
   * Public STAGING config
   */
  else if (env.staging) {
    console.log('ARIADNE Portal - Building with public STAGING config...');

    process.env.NODE_ENV = 'staging';

    config.mode = 'dev';
    config.devtool = '';
    config.optimization = {
      minimizer: [
        new TerserPlugin({}),
        new OptimizeCSSAssetsPlugin({})
      ],
    };
  }

  /**
   * Public DEV config
   */
  else if (env.dev) {
    console.log('ARIADNE Portal - Building with public DEV config...');

    process.env.NODE_ENV = 'staging';

    config.mode = 'dev';
    config.devtool = '';
    config.optimization = {
      minimizer: [
        new TerserPlugin({}),
        new OptimizeCSSAssetsPlugin({})
      ],
    };
  }

  /**
   * Public PRODUCTION config
   */
  else if (env.production) {
    console.log('ARIADNE Portal - Building with public PROD config...');

    process.env.NODE_ENV = 'production';

    config.mode = 'production';
    config.devtool = '';
    config.optimization = {
      minimizer: [
        new TerserPlugin({}),
        new OptimizeCSSAssetsPlugin({})
      ],
    };
  }

  ariadneAssetPath = JSON.stringify(env.ariadneAssetPath ? env.ariadneAssetPath : ariadneAssetPath);
  ariadnePublicPath = JSON.stringify(config.output.publicPath);

  console.log( 'Building with public path: ' );
  console.log(ariadnePublicPath);

  console.log( 'Building with asset path: ' );
  console.log(ariadneAssetPath);

  let settings =  new webpack.DefinePlugin({
    'process.env.apiUrl': JSON.stringify(ariadneApiPath),
    'process.env.ARIADNE_PUBLIC_PATH': ariadnePublicPath,
    'process.env.ARIADNE_ASSET_PATH': ariadneAssetPath,
  });

  config.plugins.push(settings);
  return config;
}
