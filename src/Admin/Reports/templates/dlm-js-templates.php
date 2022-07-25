<script type="text/html" id="tmpl-dlm-top-downloads-row">
	<tr class="dlm-reports-table__line">
		<td class="id">{{data.id}}</td>
		<td class="title">{{data.title}}</td>
		<td class="total_downloads">{{data.total_downloads}}</td>
		<div class="addons_top_downloads_entries"></div>
	</tr>
</script>

<script type="text/html" id="tmpl-dlm-user-logs-row">
	<tr class="dlm-reports-table__line">

		<td class="user"><p>{{data.user}}</p></td>
		<td class="ip"><p>{{data.ip}}</p></td>
		<td class="role"><p><# if(data.role){ #> {{data.role}} <# } else { #> -- <#  } #></p></td>
		<td class="download"><p>{{data.download}}</p></td>
		<td class="status"><p><span class="dlm-reports-table__download_status {{data.status}}">{{data.status}}</span>
			</p></td>
		<td class="download_date"><p>{{data.download_date}}</p></td>
		<td class="action"><p><# if(data.valid_user){ #> <a href="{{data.edit_link}}" target="_blank">Edit user</a><# }
				else { #> --- <# } #></p></td>
		<td class="addons_user_logs_entries"></td>
	</tr>
</script>

