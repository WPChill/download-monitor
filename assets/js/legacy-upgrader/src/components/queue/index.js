import {h, Component} from 'preact';
import style from './style.less';
import QueueItem from './QueueItem';

export default class Queue extends Component {

	state = {
		checked: false,
		items: [],
		upgrading: false
	};

	constructor(props) {
		super(props);

		this.startUpgrade = this.startUpgrade.bind(this);
	}

	// gets called when this route is navigated to
	componentDidMount() {

		fetch( ajaxurl + "?action=dlm_lu_get_queue", {
			method: 'GET',
			credentials: 'include'
		} ).then( ( r ) => {
			if ( r.status == 200 ) {
				return r.json();
			}

			throw "AJAX API OFFLINE";
		} ).then( ( j ) => {
			var items = [];
			for ( var i = 0; i < j.length; i ++ ) {
				items.push( {id: j[i], done: false} );
			}
			this.setState( {checked: true, items: items} );
			return;
		} ).catch( ( e ) => {
			console.log( e );
			return;
		} );
	}

	// gets called just before navigating away from the route
	componentWillUnmount() {
		// todo clear queue
	}

	startUpgrade() {
		if(this.state.upgrading) {
			console.log("already upgrading");
			return;
		}
		this.setState({upgrading: true});
		console.log("starting upgrade");
	}

	render() {

		if ( this.state.checked == false ) {
			return (
				<div class={style.queue}>
					<h2>Queue</h2>
					<p>We're currently building the queue, please wait.</p>
				</div>
			);
		}

		if ( this.state.items.length == 0 ) {
			return (
				<p>No Downloads found that require migrating</p>
			);
		}

		return (
			<div class={style.queue}>
				<h2>Queue</h2>
				<p>The following legacy download ID's have been found that need upgrading:</p>

				{this.state.items.length > 0 &&
				 <ul>
					 {this.state.items.map( ( o, i ) => <QueueItem item={o}/> )}
				 </ul>
				}

				<a href="javascript:;" class="button button-primary button-large" onClick={() => this.startUpgrade()}>Upgrade Downloads</a>

			</div>
		);
	}
}
