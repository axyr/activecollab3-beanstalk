<div id="beanstalk_deploy">
{if is_foreachable($servers)}
{form action=$action method=post autofocus=no uni=no id='beanstalk_deploy_form'}
<table class="common_table">
	<tr>
		<td><p>{lang}Environment{/lang}</p></td>
		<td>
			<select name="deploy[environment_id]">
				{foreach from=$servers key=environment_id item=environment_name}
				<option value="{$environment_id}">{$environment_name}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	<tr>
		<td><p>{lang}Revision{/lang}</p></td>
		<td>
			<select name="deploy[revision]">
				{foreach from=$revisions key=count item=revision_id}
				<option value="{$revision_id}">{$revision_id}{if $count==0} &ndash; {lang}latest{/lang}{/if}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	<tr>
		<td><p>{lang}Deploy from scratch{/lang}</p></td>
		<td><p><input type="checkbox" name="deploy[deploy_from_scratch]" value="1" /></p></td>
	</tr>
	<tr>
		<td><p>{lang}Release comment{/lang}</p></td>
		<td><textarea name="deploy[comment]" rows="4" cols="50"></textarea></td>
	</tr>
	<tr>
		<td></td>
		<td><button class="simple" type="submit">{lang}Deploy{/lang}</button></td>
	</tr>
</table>
{/form}
{else}
  <p class="empty_page">{lang}Unable to deploy this repository{/lang}</p>
{/if}
</div>