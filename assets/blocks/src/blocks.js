//import 'whatwg-fetch';

const {__, setLocaleData} = wp.i18n;
const {registerBlockType} = wp.blocks;
const {Fragment} = wp.element;
const {PanelBody, Autocomplete} = wp.components;
const {InspectorControls, AlignmentToolbar} = wp.editor;

import DownloadButton from './components/DownloadButton';
import DownloadInput from './components/DownloadInput';
import VersionInput from './components/VersionInput';
import TemplateInput from './components/TemplateInput';

//setLocaleData( window.gutenberg_dlm_blocks.localeData, 'download-monitor' );

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
		custom_template: {
			type: 'string'
		},
	}
	,
	edit: ( props ) => {
		const {attributes: {content, download_id, version_id, template, custom_template}, setAttributes, className} = props;

		const valueFromId = (opts, id) => opts.find(o => o.value === id);

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
						<div class="components-base-control">
							<span class="components-base-control__label">{__( 'Template', 'download-monitor' )}</span>
							<TemplateInput onChange={( v ) => setAttributes( {template: v} )} selectedTemplate={template} templatesStr={dlmBlocks.templates} />
						</div>
						{ template === "custom" &&
						<div class="components-base-control">
							<span class="components-base-control__label">{__( 'Custom Template', 'download-monitor' )}</span>
							<input class="components-text-control__input" onChange={( e ) => setAttributes( {custom_template: e.target.value} ) } value={custom_template} />
						</div>
						}
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
