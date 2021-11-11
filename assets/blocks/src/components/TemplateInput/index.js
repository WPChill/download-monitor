import Select from 'react-select';

const TemplateInput = ( { templatesStr, selectedTemplate, onChange } ) => {
	const templates = JSON.parse( templatesStr );

	const valueFromId = ( opts, id ) => opts.find( ( o ) => o.value === id );

	return (
		<div>
			<Select
				value={ valueFromId( templates, selectedTemplate ) }
				onChange={ ( selectedOption ) =>
					onChange( selectedOption.value )
				}
				options={ templates }
				isSearchable="true"
			/>
		</div>
	);
};

export default TemplateInput;
