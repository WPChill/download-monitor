import Select from 'react-select';

import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const DownloadInput = ( { selectedDownloadId, onChange } ) => {
	const [ downloads, setDownloads ] = useState( [] );

	useEffect( () => {
		apiFetch( { url: window.dlmBlocks.ajax_getDownloads } ).then(
			( results ) => {
				setDownloads( results );
			}
		);
	}, [] );

	const valueFromId = ( opts, id ) => opts.find( ( o ) => o.value === id );

	return (
		<div>
			<Select
				value={ valueFromId( downloads, selectedDownloadId ) }
				onChange={ ( selectedOption ) =>
					onChange( selectedOption.value )
				}
				options={ downloads }
				isSearchable="true"
			/>
		</div>
	);
};

export default DownloadInput;
