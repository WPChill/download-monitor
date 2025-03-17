module.exports = {
	presets: [
		'@wordpress/babel-preset-default',
		[
			'@babel/preset-env',
			{
				targets: {
					node: 'current',
				},
			},
		],
		'@babel/preset-react',
	],
};
