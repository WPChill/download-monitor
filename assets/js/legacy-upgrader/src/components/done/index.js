import { h, Component } from 'preact';
import { Link } from 'preact-router';
import style from './style.less';

export default class Done extends Component {
	render( {download_amount, content_amount}) {
		return (
			<div class={style.welcome}>
				<h2>Upgrade Done</h2>
				<p><strong>{download_amount}</strong> downloads have been upgraded.</p>
				<p><strong>{content_amount}</strong> posts/pages have been upgraded.</p>
			</div>
		);
	}
}
