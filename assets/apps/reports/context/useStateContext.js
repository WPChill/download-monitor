import { useContext } from '@wordpress/element';
import { StateContext } from './state-context';

const useStateContext = () => {
	const context = useContext( StateContext );

	if ( context === undefined ) {
		throw new Error(
			'useStateContext must be used within a SettingsProvider',
		);
	}

	return context;
};

export default useStateContext;
