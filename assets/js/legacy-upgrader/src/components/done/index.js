import { h, Component } from 'preact';
import { Link } from 'preact-router';
import style from './style.less';

export default class Done extends Component {
	render() {
		return (
			<div class={style.welcome}>
				<h2>Upgrade Done</h2>
				<p><strong>{this.props.download_amount}</strong> downloads have been upgraded.</p>
				<p><strong>{this.props.content_amount}</strong> posts/pages have been upgraded.</p>
			</div>
		);
	}
}
