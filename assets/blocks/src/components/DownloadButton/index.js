import { useState } from '@wordpress/element';

const DownloadButton = ( {
	download_id,
	version_id,
	template,
	custom_template,
} ) => {
	const [ calculatedHeight, setCalculatedHeight ] = useState( {
		cacheKey: '',
		height: 100,
	} );

	const getIframeUrl = () => {
		let iframeURL = window.dlmBlocks.urlButtonPreview;

		if ( download_id !== 0 ) {
			iframeURL += '&download_id=' + download_id;
		}

		if ( version_id !== 0 ) {
			iframeURL += '&version_id=' + version_id;
		}

		if ( template !== '' ) {
			iframeURL += '&template=' + template;
		}

		if ( custom_template !== '' ) {
			iframeURL += '&custom_template=' + custom_template;
		}

		return iframeURL;
	};

	const updateHeight = ( target ) => {
		const cacheKey = encodeURI( getIframeUrl() );

		// check if we need to reset height to new URL
		if ( calculatedHeight.chacheKey !== cacheKey ) {
			setCalculatedHeight( {
				cacheKey,
				height: target.contentDocument.getElementById(
					'dlmPreviewContainer'
				).scrollHeight,
			} );
		}
	};

	const iframeURL = getIframeUrl();
	const frameHeight = calculatedHeight.height + 'px';

	return (
		<div className="dlmPreviewButton">
			<iframe
				src={ iframeURL }
				width="100%"
				height={ frameHeight }
				onLoad={ ( e ) => {
					updateHeight( e.target );
				} }
			></iframe>
		</div>
	);
};

export default DownloadButton;
