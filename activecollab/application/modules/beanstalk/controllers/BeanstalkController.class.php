<?php

use_controller('project', SYSTEM_MODULE);

class BeanstalkController extends ProjectController
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
	
	/**
 	* Array of controller actions that can be accessed through API
 	*
 	* @var array
 	*/
	var $api_actions = array('commit', 'pre_deploy', 'post_deploy');
	
 
	function commit()
	{
		$varValue = $this->request->post('commit');
		
		if ($varValue == '')
		{
			exit;
		}
		
		$objData = json_decode($varValue);
		
		if (!is_object($objData))
		{
			header('HTTP/1.1 400 Bad Request');
			die('HTTP/1.1 400 Bad Request');
		}
		
		// GIT commit
		if (is_array($objData->commits))
		{
			foreach( $objData->commits as $objCommit )
			{
				$strMessage = $this->generate_comment((string) $objCommit->message, (string) $objCommit->url, (string) $objCommit->id);
				$this->handle_commit((string) $objCommit->timestamp, $strMessage, (string) $objCommit->author->email, (string) $objCommit->author->name);
			}
		}
		
		// SVN commit
		else
		{
			$strMessage = $this->generate_comment((string) $objData->message, (string) $objData->changeset_url, (string) $objData->revision);
			$this->handle_commit((string) $objData->time, $strMessage, (string) $objData->author_email, (string) $objData->author_full_name);
		}
		
		event_trigger('on_beanstalk_commit', array($objData));
		
		exit;
	}
	
	
	function pre_deploy()
	{
		$varValue = @file_get_contents('php://input');
		
		if ($varValue == '')
		{
			exit;
		}
		
		$objData = json_decode($varValue);
		
		if (!is_object($objData))
		{
			header('HTTP/1.1 400 Bad Request');
			die('HTTP/1.1 400 Bad Request');
		}
		
		event_trigger('on_beanstalk_pre_deploy', array($objData));
		
		exit;
	}
	
	
	function post_deploy()
	{
		$varValue = @file_get_contents('php://input');
		
		if ($varValue == '')
		{
			exit;
		}
		
		$objData = json_decode($varValue);
		
		if (!is_object($objData))
		{
			header('HTTP/1.1 400 Bad Request');
			die('HTTP/1.1 400 Bad Request');
		}
		
		event_trigger('on_beanstalk_post_deploy', array($objData));
		
		exit;
	}
	
	
	function handle_commit($strDate, $strMessage, $strAuthorEmail='', $strAuthorName='')
	{
		$objUser = Users::findByEmail($strAuthorEmail);
		
		// Commit message [#15 tagged:committed responsible:chris milestone:"Beta Release" state:resolved time:2.00]
		if (preg_match('/\[(#([\d]+))?([^\]]*)\]/is', $strMessage, $arrMatches))
		{
			$strMessage = trim(str_replace($arrMatches[0], '', $strMessage));
			$fltTime = $this->parse_time($arrMatches[3]);
			
			$objTicket = null;
			$intTicket = (int) $arrMatches[2];

			if ($intTicket > 0)
			{
				$objTicket = Tickets::findByTicketId($this->active_project, $intTicket);
			}
			
			if (instance_of($objTicket, 'Ticket'))
			{
				$this->addComment($objTicket, $strDate, $strMessage, $objUser, $strAuthorName, $strAuthorEmail);
				
				if ($fltTime !== false && instance_of($objUser, 'User'))
				{
					$this->addTime($objUser, $fltTime, $strDate, $strMessage, $objTicket);
				}
				
				return;
			}
			
			if ($fltTime !== false && instance_of($objUser, 'User'))
			{
				$this->addTime($objUser, $fltTime, $strDate, $strMessage);
			}
			
			return;
		}
		
		
		// Commit Message (Ticket #15)
		if (preg_match('/\(?((complete)[d|s]?[\s]+)?ticket[\s]+[#]?(\d+):?\)?/is', $strMessage, $arrMatches))
		{
			$strMessage = trim(str_replace($arrMatches[0], '', $strMessage));
			
			$objTicket = null;
			$intTicket = (int) $arrMatches[3];

			if ($intTicket > 0)
			{
				$objTicket = Tickets::findByTicketId($this->active_project, $intTicket);
			}
			
			if (instance_of($objTicket, 'Ticket'))
			{
				$this->addComment($objTicket, $strDate, $strMessage, $objUser, $strAuthorName, $strAuthorEmail);
				
				// Complete ticket
        		if (strtolower($arrMatches[2]) == 'complete' && instance_of($objUser, 'User') && $objTicket->isOpen() && $objTicket->canChangeCompleteStatus($objUser))
        		{
					$objTicket->complete($objUser);
        		}
			}
			
			return;
		}
	}
	
	
	function parse_time($strMessage)
	{
		// Find time in message like "#15 time:2.00"
		$arrTokens = array_map('trim', explode(' ', strtolower($strMessage)));
		if (!empty($arrTokens))
		{
			foreach( $arrTokens as $strToken )
			{
				list($strKey, $strValue) = array_map('trim', explode(':', $strToken));
				
				if ($strKey == 'time')
				{
					return round($strValue, 2);
				}
			}
		}
		
		// Find time amount in brackets
		if (preg_match('/\(([\d]+[\.|,][\d]+?)\)/is', $strMessage, $arrMatches))
		{
			return round(str_replace(',', '.', $arrMatches[1]), 2);
		}
		
		return false;
	}
	
	
	function generate_comment($strMessage, $strUrl, $strId)
	{
		// Shorten GIT commit id
		$strId = substr($strId, 0, 8);
		
		return '
<p><strong>'.$strId.':</strong> '.$strMessage.'</p>
<p><a href="'.$strUrl.'" target="_blank" title="See changeset '.$strId.'">See changeset on Beanstalk</a></p>';
	}
	
	
	function addComment($objParent, $strDate, $strMessage, $objUser=null, $strUserName='', $strUserEmail='')
	{
		$objComment = new Comment();
		$objComment->log_activities = false;
		$objComment->setParent($objParent);
		
		$objComment->setBody($strMessage);
		$objComment->setCreatedOn($strDate);
		$objComment->setProjectId($this->active_project->getId());
		$objComment->setState(STATE_VISIBLE);
		$objComment->setVisibility($objParent->getVisibility());
		
		if (instance_of($objUser, 'User'))
		{
			$objComment->setCreatedBy($objUser);
		}
		else
		{
			$objComment->setCreatedById(0);
			$objComment->setCreatedByName($strUserName);
			$objComment->setCreatedByEmail($strUserEmail);
		}
		
		$save = $objComment->save();

		if ($save && !is_error($save))
		{
			$objComment->ready();
		}
		
		return $save;
	}
	
	
	function addTime($objUser, $fltTime, $strDate, $strMessage, $objParent=null)
	{
		if (!instance_of($objUser, 'User'))
		{
			return false;
		}
					
		$objTimeRecord = new TimeRecord();
		
		if ($objParent !== null)
		{
   			$objTimeRecord->setParent($objParent);
		}
		
		$timetracking_data = array
		(
			'user_id'			=> $objUser->getId(),
			'record_user'		=> $objUser,
			'record_date'		=> $strDate,
			'value'				=> $fltTime,
			'billable_status'	=> BILLABLE_STATUS_BILLABLE,
			'body'				=> $strMessage,
		);
		
		$objTimeRecord->setAttributes($timetracking_data);
		$objTimeRecord->setProjectId($this->active_project->getId());
		$objTimeRecord->setCreatedBy($objUser);
		$objTimeRecord->setState(STATE_VISIBLE);
		$objTimeRecord->setVisibility(VISIBILITY_NORMAL);
		$objTimeRecord->setUser($objUser);
		
		return $objTimeRecord->save();
	}
}

