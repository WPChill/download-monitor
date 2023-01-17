import Select from 'react-select';

import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const VersionInput = ({ downloadId, selectedVersionId, onChange }) => {
	const [versions, setVersions] = useState([]);
	const [currentDownloadId, setCurrentDownloadId] = useState(downloadId);

	useEffect(() => fetchVersions(downloadId), []);

	useEffect(() => fetchVersions(downloadId), [downloadId]);

	const fetchVersions = (downloadId) => {
		if (
			typeof downloadId !== 'undefined' &&
			downloadId !== currentDownloadId
		) {
			// prettier-ignore
			apiFetch({url:window.dlmBlocks.ajax_getVersions +'&download_id=' +downloadId,})
			.then((results) => {
				results.unshift({
					value: 0,
					label: __('Latest version', 'download-monitor'),
				});
				results = results.map( (version) => ( version.label == '' ? { value: version.value, label: `Unnamed ${version.value}` } : version ) ); //prettier-ignore
				setVersions(results);
				setCurrentDownloadId(downloadId);
			});
		}
	};

	const valueFromId = (opts, id) => opts.find((o) => o.value === id);

	return (
		<div>
			<Select
				value={valueFromId(versions, selectedVersionId)}
				onChange={(selectedOption) => onChange(selectedOption.value)}
				options={versions}
				isSearchable="true"
				isDisabled={typeof downloadId === 'undefined'}
			/>
		</div>
	);
};

export default VersionInput;
