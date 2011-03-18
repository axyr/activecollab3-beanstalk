<?php

use_controller('repository', SOURCE_MODULE);


class BeanstalkController extends RepositoryController
{

	/**
	* Controller name
	*
	* @var string
	*/
	var $controller_name = 'beanstalk';
	
	/**
	* Active module
	*
	* @var constant
	*/
	var $active_module = BEANSTALK_MODULE;
	
 
	function deploy_widget()
	{
		$this->skip_layout = true;
		
		preg_match('@(http[s]?)://([a-z]+)\.svn\.beanstalkapp.com/(.+)@', $this->active_repository->getUrl(), $arrMatches);
		
		$mode = $arrMatches[1];
		$domain = $arrMatches[2];
		$repository_url = explode('/', $arrMatches[3]);
		$repository_name = $repository_url[0];
		$repository_id = 0;


		if ($this->request->isSubmitted())
		{
			$deploy = $this->request->post('deploy');
			
			$postData = 
'<release>
  <revision type="integer">' . $deploy['revision'] . '</revision>
  <comment>' . $deploy['comment'] . '</comment>' . ($deploy['deploy_from_scratch'] ? '
  <deploy_from_scratch>1</deploy_from_scratch>' : '') . '
</release>';

			if ($this->http_request_xml($mode, $domain, ($repository_name.'/releases.xml?environment_id='.$deploy['environment_id']), $postData))
			{
				flash_success("Deployment of repository " . $this->active_repository->getName() . " has been initiated.");
			}
			else
			{
				flash_error("Deployment of repository " . $this->active_repository->getName() . " failed.");
			}
			
			$this->redirectTo('project_repositories', array('project_id'=>$this->active_project->getId()));
		}
		
		$xml = $this->http_request_xml($mode, $domain, 'repositories.xml');
		
		foreach( $xml->repository as $repository )
		{
			if ($repository->name == $repository_name)
			{
				$repository_id = $repository->id;
				break;
			}
		}
		
		if ($repository_id == 0)
		{
			// Repository not found
		}
		
		$servers = array();
		
		$xml = $this->http_request_xml($mode, $domain, $repository_id.'/server_environments.xml');
		
		foreach( $xml->{'server-environment'} as $server )
		{
			$server_name = strval($server->name);
			
			if ($server->{'current-version'})
			{
				$server_name .= ' - Revision ' . $server->{'current-version'} . ' deployed on ' . date('d.m.Y H:i', strtotime($server->{'updated-at'}));
			}
			
			$servers[intval($server->id)] = $server_name;
		}
		
		$commit = Commits::findLastCommit($this->active_repository);
		
		$latest_revision = $commit->values[$commit->field_map['revision']];
		
		$this->smarty->assign(array(
			'action' => '/'.ANGIE_PATH_INFO,
			'servers' => $servers,
			'revisions' => range($latest_revision, ($latest_revision-10)),
		));
	}
  
  
  
    function http_request_xml($mode, $domain, $file, $data=null)
    {
    	$ch = curl_init($mode . '://' . $domain . '.beanstalkapp.com/api/' . $file);
		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($ch, CURLOPT_USERPWD, $this->active_repository->getUsername() . ':' . $this->active_repository->getPassword());
		
		if ($data)
		{
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_exec($ch);
			
			$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			
			if ($code != 201)
				return false;
			
			return true;
		}
		
		$data = curl_exec($ch);
		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		curl_close($ch);

		switch( $code )
		{
			case '200':
				$obj = simplexml_load_string($data);
				return $obj;
				break;
		}
		
		return false;
    }
}

