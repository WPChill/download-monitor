import { useEffect, useState } from '@wordpress/element';
import { createPortal } from 'react-dom';
import { getFills } from '../utils/slotFillRegistry';

export default function Slot( { name, containerId, ...props } ) {
	const [ container, setContainer ] = useState( null );

	useEffect( () => {
		const el = document.getElementById( containerId );
		setContainer( el );
	}, [ containerId ] );

	if ( ! container ) {
		return null;
	}

	const fills = getFills( name );
	if ( ! fills.length ) {
		return null;
	}

	return createPortal(
		<>
			{ fills.map( ( Fill, i ) => (
				<Fill key={ i } { ...props } />
			) ) }
		</>,
		container,
	);
}
