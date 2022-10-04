<script type="text/html" id="tmpl-dlm-top-downloads-row">
	<tr class="dlm-reports-table__line" data-id="{{data.id}}">
		<td class="id">{{data.id}}</td>
		<td class="title"><a href="{{data.edit_link}}" target="_blank">{{data.title}}</td>
		<td class="total_downloads">{{data.total_downloads}}</td>
	</tr>
</script>

<script type="text/html" id="tmpl-dlm-user-logs-row">
	<tr class="dlm-reports-table__line" data-id="{{data.key}}">
		<td class="user"><p><# if( '#' !==  data.edit_link){ #><a href="{{data.edit_link}}" target="_blank"> <# } #>{{data.user}}<# if( '#' !==  data.edit_link){ #></a><# } #></p></td>
		<td class="ip"><p>{{data.ip}}</p></td>
		<td class="role"><p><# if(data.role){ #> {{data.role}} <# } else { #> -- <#  } #></p></td>
		<td class="download"><p><a href="{{data.edit_download_link}}" target="_blank">{{data.download}}</a></p></td>
		<td class="status"><p><span class="dlm-reports-table__download_status {{data.status}}">{{data.status}}</span>
			</p></td>
		<td class="download_date"><p>{{data.download_date}}</p></td>
	</tr>
</script>

