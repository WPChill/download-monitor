import { h, Component } from 'preact';
import { Link } from 'preact-router';
import style from './style.less';

export default class Welcome extends Component {
	render() {
		return (
			<div class={style.welcome}>
				<h2>Welcome</h2>
				<p>Before we can upgrade your downloads, we're first going to search for your old ones. We put all found downloads in a queue which you can view before the actual upgrading begins.</p>
				<p><strong>PLEASE NOTE: Although thoroughly tested, this process will modify and move your download data.  Backup your database before you continue.</strong></p>
				<p><Link href="/downloads" class="button button-primary button-large">I have backed up my database, let's go</Link></p>
			</div>
		);
	}
}
