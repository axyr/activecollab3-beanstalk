<?php


class BeanstalkModule extends Module
{
    
    /**
     * Plain module name
     *
     * @var string
     */
    var $name = 'beanstalk';
    
    /**
     * Is system module flag
     *
     * @var boolean
     */
    var $is_system = false;
    
    /**
     * Module version
     *
     * @var string
     */
    var $version = '1.0';
    
    // ---------------------------------------------------
    //  Events and Routes
    // ---------------------------------------------------
    
    
    /**
     * Define module routes
     *
     * @param Router $r
     * @return null
     */
    function defineRoutes(&$router)
    {
    	$router->map('project_repositories', '/projects/:project_id/repositories', array('controller'=>'beanstalk', 'action'=>'index'), array('project_id'=>'\d+'));
    	$router->map('beanstalk_deploy_widget', '/projects/:project_id/repositories/:repository_id/beanstalk-deploy', array('controller'=>'beanstalk', 'action'=>'deploy_widget'), array('project_id'=>'\d+', 'repository_id'=>'\d+'));
    } 
    
    
    /**
     * Can this module be installed or not
     *
     * @param array $log
     * @return boolean
     */
	function canBeInstalled(&$log)
	{
		if(extension_loaded('SimpleXML') && function_exists('simplexml_load_string'))
		{
			$log[] = lang('OK: SimpleXML extension loaded');
			
			if(extension_loaded('curl') && function_exists('curl_init'))
			{
				$log[] = lang('OK: CURL extension loaded');
			}
			else
			{
				$log[] = lang('This module requires CURL PHP extension to be installed. Read more about CURL extension in PHP documentation: http://www.php.net/manual/en/book.curl.php');
			
				return false;
			}
			
			return true;
		}
		else
		{
			$log[] = lang('This module requires SimpleXML PHP extension to be installed. Read more about SimpleXML extension in PHP documentation: http://www.php.net/manual/en/book.simplexml.php');
			
			return false;
		}
	}
    
    
    /**
     * Install this module
     *
     * @param void
     * @return boolean
     */
    function install()
    {
     	if (parent::install())
     	{
     		$objModule = Modules::findById('beanstalk');
     		$objModule->setPosition(1000);
     		$objModule->save();
     		return true;
     	}
     	
     	return false;
    }
    
    
    /**
     * Get module display name
     *
     * @return string
     */
    function getDisplayName()
    {
		return lang('Beanstalk');
    }
    
    
    /**
     * Return module description
     *
     * @param void
     * @return string
     */
    function getDescription()
    {
		return lang('Extend source module with beanstalk features.');
    }
    
    
    /**
     * Return module uninstallation message
     *
     * @param void
     * @return string
     */
    function getUninstallMessage()
    {
    	return lang('Module will be deactivated. You will no longer be able to deploy.');
    }
}

