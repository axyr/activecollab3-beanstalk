<?php

function smarty_function_beanstalk_deploy($params, &$smarty)
{
	$repository = array_var($params, 'repository');
	
    if(!instance_of($repository, 'Repository'))
    {
		return new InvalidParamError('$repository', $repository, '$repository is expected to be a valid instance of Repository class', true);
    }
    
    $user = array_var($params, 'user');
    if(!instance_of($user, 'User'))
    {
		return new InvalidParamError('$user', $user, '$user is expected to be a valid instance of User class', true);
    }
  
	if (strpos($repository->getUrl(), 'beanstalkapp.com') !== false)
	{
		$buffer = '<a id="beanstalk_deploy_' . $repository->getId() . '" href="#" title="' . lang('Click to deploy') . '"><img src="' . get_image_url('deploy.png', 'beanstalk') . '" alt="" /></a>';
		
		$buffer .= "<script type=\"text/javascript\">
$('#beanstalk_deploy_" . $repository->getId() . "').click(function() {
	App.ModalDialog.show('beanstalk_deploy', '" . lang('Deploy repository') . "', $('<p><img src=\"' + App.data.assets_url + '/images/indicator.gif\" alt=\"\" /> ' + App.lang('Loading...') + '</p>').load('" . assemble_url('beanstalk_deploy_widget', array('project_id'=>$repository->getProjectId(), 'repository_id'=>$repository->getId())) . "'), {});
	return false;
});
</script>";
		
		return  $buffer;
	}

	return '';
}

