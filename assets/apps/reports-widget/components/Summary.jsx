import styles from './Summary.module.scss';
import { applyFilters } from '@wordpress/hooks';

export default function Summary( { label = '', value = '', type = 'default', cards = {} } ) {
	return (
		<div className={ `${ styles.item } ${ styles[ type ] }` }>
			<div className={ styles.content }>
				<div className={ styles.label }>
					{ label }
					{ applyFilters( `dlm.widget.card.${ type }.label.after`, '', { type, cards } ) }
				</div>
				<div className={ styles.value }>
					{ value }
					{ applyFilters( `dlm.widget.card.${ type }.value.after`, '', { type, cards } ) }
				</div>
				{ applyFilters( `dlm.widget.card.${ type }.after`, '', { type, cards } ) }
			</div>
		</div>
	);
}
