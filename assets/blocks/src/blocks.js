//import 'whatwg-fetch';

const {__, setLocaleData} = wp.i18n;
const {registerBlockType} = wp.blocks;
const {Fragment} = wp.element;
const {PanelBody, Autocomplete} = wp.components;
const {InspectorControls, AlignmentToolbar} = wp.editor;

import DownloadButton from './components/DownloadButton';
import DownloadInput from './components/DownloadInput';
import VersionInput from './components/VersionInput';

//setLocaleData( window.gutenberg_dlm_blocks.localeData, 'download-monitor' );

/**
 *        download_data: {
			download_id: 'number',
			version_id: 'number',
			version: 'string',
			template: 'string'
		}
 */

registerBlockType( 'download-monitor/download-button', {
	title: __( 'Download Button', 'download-monitor' ),
	icon: 'download',
	keywords: [__( 'download', 'download-monitor' ), 'download monitor', __( 'file', 'download-monitor' )],
	category: 'common',
	attributes: {
		content: {
			type: 'string',
			source: 'html',
			selector: 'p',
		},
		download_id: {
			type: 'number'
		},
		version_id: {
			type: 'number'
		},
		template: {
			type: 'string'
		},
	}
	,
	edit: ( props ) => {
		const {attributes: {content, download_id, version_id, template}, setAttributes, className} = props;

		return (
			<Fragment>
				<InspectorControls>
					<PanelBody title={__( 'Download Information', 'download-monitor' )}>
						<div class="components-base-control">
							<span class="components-base-control__label">{__( 'Download', 'download-monitor' )}</span>
							<DownloadInput onChange={(v)=> setAttributes( {download_id: v} )} selectedDownloadId={download_id} />
						</div>

						<div class="components-base-control">
							<span class="components-base-control__label">{__( 'Version', 'download-monitor' )}</span>
							<VersionInput onChange={(v)=> setAttributes( {version_id: v} )} selectedVersionId={version_id} downloadId={download_id} />
						</div>

					</PanelBody>
					<PanelBody title={__( 'Template', 'download-monitor' )}>
						<label>{__( 'Template', 'download-monitor' )}</label>
						<input type="text" value={template}
						       onChange={( v ) => setAttributes( {template: v.target.value} )}/>
					</PanelBody>
				</InspectorControls>
				<DownloadButton
					value={content}
				/>
			</Fragment>
		);
	},
	save: ( props ) => {
		return <a>{props.attributes.content}</a>;
	},
} );
