const path = require('path');
const webpack = require('webpack');
const VueLoaderPlugin = require('vue-loader/lib/plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const OptimizeCSSAssetsPlugin = require('optimize-css-assets-webpack-plugin');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const { CleanWebpackPlugin } = require('clean-webpack-plugin');


module.exports = env => {
  config = {
    entry: path.resolve(__dirname, './src/ts/index.ts'),
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
          test: /\.(png|jpg|gif|svg)$/,
          loader: 'file-loader',
          options: {
            name: '[name].[ext]?[hash]'
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
      })
    ],
    resolve: {
      extensions: ['.ts', '.js', '.vue', '.json'],
      alias: {
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
    console.log('Building with production config.');

    config.mode = 'production';
    config.devtool = '';
    config.optimization = {
      minimizer: [
        new TerserPlugin({}),
        new OptimizeCSSAssetsPlugin({})
      ],
    };

    config.plugins.push(new webpack.DefinePlugin({
      'process.env.apiUrl': JSON.stringify('/api'),
    }));

  /**
   * Development config
   */
  } else {
    console.log('Building with development config.');

    config.mode = 'development';
    config.devtool = '#eval-source-map';
    config.devServer = {
      contentBase: [path.join(__dirname, 'dist'), path.join(__dirname, 'static')],
      historyApiFallback: true,
      noInfo: true,
      proxy: {
        '/api': {
          target: {
            host: "0.0.0.0",
            protocol: 'http:',
            port: 80
          },
          pathRewrite: {
            '^/api': ''
          }
        }
      }
    };

    config.plugins.push(new webpack.DefinePlugin({
      'process.env.apiUrl': JSON.stringify('/api'),
    }));
  }

  return config;
}
