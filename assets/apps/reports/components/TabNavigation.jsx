import { useMemo } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import useStateContext from '../context/useStateContext';
import styles from './TabNavigation.module.scss';
import { setActiveTab } from '../context/actions';

export default function TabNavigation() {
	const { state, dispatch } = useStateContext();

	const tabs = useMemo( () => {
		const baseTabs = [
			{ title: __( 'Overview', 'download-monitor' ), slug: 'overview' },
			{ title: __( 'Detailed Reports', 'download-monitor' ), slug: 'detailed' },
		];
		return applyFilters( 'dlm.reports.tabs', baseTabs );
	}, [] );

	const handleClick = ( slug ) => {
		dispatch( setActiveTab( slug ) );
	};

	return (
		<nav className={ styles.tabNavigation }>
			<ul className={ styles.tabList }>
				{ tabs.map( ( tab ) => {
					const isActive = state.activeTab === tab.slug;
					const buttonClass =
						styles.tabButton + ( isActive ? ` ${ styles.active }` : '' );

					return (
						<li key={ tab.slug }>
							<button
								className={ buttonClass }
								onClick={ () => handleClick( tab.slug ) }
								type="button"
							>
								{ tab.title }
							</button>
						</li>
					);
				} ) }
			</ul>
		</nav>
	);
}
