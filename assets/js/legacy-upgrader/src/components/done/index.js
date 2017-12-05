import { h, Component } from 'preact';
import { Link } from 'preact-router';
import style from './style.less';

export default class Done extends Component {
	render( {amount}) {
		return (
			<div class={style.welcome}>
				<h2>Upgrade Done</h2>
				<p><strong>{amount}</strong> downloads have been upgraded.</p>
			</div>
		);
	}
}
