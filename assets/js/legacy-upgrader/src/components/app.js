import { h, Component } from 'preact';
import { Router } from 'preact-router';

import Welcome from './welcome';
import Downloads from './downloads';
import Content from './content';
import Done from './done';

export default class App extends Component {

	constructor(props) {
		super(props);

		this.state = {
			queue: []
		};
	}

	/** Gets fired when the route changes.
	 *	@param {Object} event		"change" event from [preact-router](http://git.io/preact-router)
	 *	@param {string} event.url	The newly routed URL
	 */
	handleRoute = e => {
		this.currentUrl = e.url;
	};

	render() {
		return (
			<div id="dlm_legacy_upgrader_app">
				<Router onChange={this.handleRoute}>
					<Welcome path="" />
					<Downloads path="/downloads" />
					<Content path="/content/:download_amount" />
					<Done path="/done/:download_amount/:content_amount" />
				</Router>

			</div>
		);
	}
}
