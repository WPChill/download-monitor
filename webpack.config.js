const path = require( 'path' );

let webpack = require( 'webpack' ),
	NODE_ENV = process.env.NODE_ENV || 'development',

	webpackConfig = {
		entry: './assets/blocks/src/blocks.js',
		output: {
			path: path.resolve( __dirname, 'assets/blocks/dist' ),
			filename: 'blocks.build.js',
		},
		module: {
			loaders: [
				{
					test: /.js$/,
					loader: 'babel-loader',
					exclude: /node_modules/,
				},

			],
		},
		plugins: [
			new webpack.DefinePlugin( {
				'process.env.NODE_ENV': JSON.stringify( NODE_ENV ),
			} ),
		],
	};

if ( 'production' === NODE_ENV ) {
	webpackConfig.plugins.push( new webpack.optimize.UglifyJsPlugin() );
}

module.exports = webpackConfig;

/*
if (!isProduction) {
	module.exports.output.publicPath = 'http://localhost:8080/';

	module.exports.serve = {
		hot: true,
		dev: {
			publicPath: 'http://localhost:8080/',
			historyApiFallback: true,
			headers: {
				'Access-Control-Allow-Origin': '*'
			}
		}
	};
}*/

/*

	devServer: {
		port: 1337,
		host: '0.0.0.0',
		color: true,
		publicPath: '/',
		contentBase: path.resolve( __dirname, 'assets/blocks/src' ),
		historyApiFallback: true
	}
 */