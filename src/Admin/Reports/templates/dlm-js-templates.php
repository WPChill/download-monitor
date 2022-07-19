<script type="text/html" id="tmpl-dlm-top-downloads-row">
	<div class="dlm-reports-table__line">
		<div class='dlm-reports-table__entries'>
			<div class="id">{{data.id}}</div>
			<div class="title">{{data.title}}</div>
			<div class="completed_downloads">{{data.completed_downloads}}</div>
			<div class="failed_downloads">{{data.failed_downloads}}</div>
			<div class="redirected_downloads">{{data.redirected_downloads}}</div>
			<div class="total_downloads">{{data.total_downloads}}</div>
			<div class="logged_in_downloads">{{data.logged_in_downloads}}</div>
			<div class="non_logged_in_downloads">{{data.non_logged_in_downloads}}</div>
			<div class="percent_downloads">{{data.percent_downloads}}</div>
			<div class="content_locking_downloads">{{data.content_locking_downloads}}</div>
			<div class="addons_top_downloads_entries"></div>
		</div>
	</div>
</script>

<script type="text/html" id="tmpl-dlm-user-logs-row">
	<div class="dlm-reports-table__line">
		<div class='dlm-reports-table__entries'>
			<div class="user"><p>{{data.user}}</p></div>
			<div class="ip"><p>{{data.ip}}</p></div>
			<div class="role"><p>{{data.role}}</p></div>
			<div class="status"><p><span class="dlm-reports-table__download_status {{data.status}}">{{data.status}}</span></p></div>
			<div class="download_date"><p>{{data.download_date}}</p></div>
			<div class="action"><p><# if(data.valid_user){ #> <a href="{{data.edit_link}}" target="_blank">Edit user</a><# } else { #> --- <# } #></p></div>
			<div class="addons_user_logs_entries"></div>
		</div>
	</div>
</script>

