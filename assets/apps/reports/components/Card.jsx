import styles from './Card.module.scss';
import { Dashicon } from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';

export default function Card( { label = '', value = '', color = '#8280FF', icon = 'download', type = 'default', cards = {} } ) {
	return (
		<div className={ `${ styles.dlmReportsCard } ${ styles[ type ] }` }>
			<div style={ { color } } className={ styles.dlmReportsCardContent }>
				<h3 className={ styles.dlmReportsCardTitle }>
					{ label }
				</h3>
				<p className={ styles.dlmReportsCardValue }>
					{ value }
				</p>
				{ applyFilters( `dlm.card.${ type }.after`, '', { type, cards } ) }
			</div>
			<div style={ { color } } className={ styles.dlmReportsCardIconWrap }>
				<div
					className={ styles.dlmReportsCardIconBg }
					style={ { backgroundColor: color, opacity: 0.21 } }
				/>
				<Dashicon
					icon={ icon }
					className={ styles.dlmReportsCardIcon }
				/>
			</div>
		</div>
	);
}
