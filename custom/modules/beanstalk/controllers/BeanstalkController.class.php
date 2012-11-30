<?php

// We need project controller
AngieApplication::useController('project');

/**
 *
 *
 */
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


    /**
     *
     */
    function commit()
    {


        //  $this->miuralog('POST : '.print_r($_POST,1));
        //  $this->miuralog('request : '.print_r($this->request,1));
        //  $varValue = $this->request->post('commit');
        $varValue = $_POST['payload'];


        if ($varValue == '') {
            exit;
        }

        $objData = json_decode($varValue);
        if (!is_object($objData)) {
            header('HTTP/1.1 400 Bad Request');
            die('HTTP/1.1 400 Bad Request');
        }

        // GIT commit
        if (is_array($objData->commits)) {
            foreach ($objData->commits as $objCommit) {
                $strMessage = $this->generate_comment((string)$objCommit->message, (string)$objCommit->url, (string)$objCommit->id);
                $this->handle_commit((string)$objCommit->timestamp, $strMessage, (string)$objCommit->author->email, (string)$objCommit->author->name);
            }
        } // SVN commit
        else {
            $strMessage = $this->generate_comment((string)$objData->message, (string)$objData->changeset_url, (string)$objData->revision);
            $this->handle_commit((string)$objData->time, $strMessage, (string)$objData->author_email, (string)$objData->author_full_name);
        }

        exit;
    }


    /**
     *
     */
    function pre_deploy()
    {
        $varValue = @file_get_contents('php://input');

        if ($varValue == '') {
            exit;
        }

        $objData = json_decode($varValue);

        if (!is_object($objData)) {
            header('HTTP/1.1 400 Bad Request');
            die('HTTP/1.1 400 Bad Request');
        }

        exit;
    }


    /**
     *
     */
    function post_deploy()
    {
        $varValue = @file_get_contents('php://input');

        if ($varValue == '') {
            exit;
        }

        $objData = json_decode($varValue);

        if (!is_object($objData)) {
            header('HTTP/1.1 400 Bad Request');
            die('HTTP/1.1 400 Bad Request');
        }

        exit;
    }


    /**
     * @param $strDate
     * @param $strMessage
     * @param string $strAuthorEmail
     * @param string $strAuthorName
     */
    function handle_commit($strDate, $strMessage, $strAuthorEmail = '', $strAuthorName = '')
    {
        $objUser = Users::findByEmail($strAuthorEmail);

        // Commit message [#15 tagged:committed responsible:chris milestone:"Beta Release" state:resolved time:2.00]
        if (preg_match('/\[(#([\d]+))?([^\]]*)\]/is', $strMessage, $arrMatches)) {
            $strMessage = trim(str_replace($arrMatches[0], '', $strMessage));
            $fltTime = $this->parse_time($arrMatches[3]);

            $objTicket = null;
            $intTicket = (int)$arrMatches[2];

            if ($intTicket > 0) {
                $objTicket = Tasks::findByTaskId($this->active_project, $intTicket);
            }

            if ($objTicket instanceof Task) {
                $this->addComment($objTicket, $strDate, $strMessage, $objUser, $strAuthorName, $strAuthorEmail);

                if ($fltTime !== false && $objUser  instanceof User) {
                    $this->addTime($objUser, $fltTime, $strDate, $strMessage, $objTicket);
                }

                return;
            }

            if ($fltTime !== false && $objUser instanceof User) {
                $this->addTime($objUser, $fltTime, $strDate, $strMessage);
            }

            return;
        }


        // Commit Message (Ticket #15)
        if (preg_match('/\(?((complete)[d|s]?[\s]+)?ticket[\s]+[#]?(\d+):?\)?/is', $strMessage, $arrMatches)) {
            $strMessage = trim(str_replace($arrMatches[0], '', $strMessage));

            $objTicket = null;
            $intTicket = (int)$arrMatches[3];

            if ($intTicket > 0) {
                $objTicket = Tasks::findByTaskId($this->active_project, $intTicket);
            }

            if ($objTicket instanceof Task) {
                $this->addComment($objTicket, $strDate, $strMessage, $objUser, $strAuthorName, $strAuthorEmail);

                // Complete ticket
                if (strtolower($arrMatches[2]) == 'complete' && $objUser instanceof User) {
                    $objTicket->complete($objUser);
                }
            }

            return;
        }
    }


    /**
     * @param $strMessage
     * @return bool|float
     */
    function parse_time($strMessage)
    {
        // Find time in message like "#15 time:2.00"
        $arrTokens = array_map('trim', explode(' ', strtolower($strMessage)));
        if (!empty($arrTokens)) {
            foreach ($arrTokens as $strToken) {
                list($strKey, $strValue) = array_map('trim', explode(':', $strToken));

                if ($strKey == 'time') {
                    return round($strValue, 2);
                }
            }
        }

        // Find time amount in brackets
        if (preg_match('/\(([\d]+[\.|,][\d]+?)\)/is', $strMessage, $arrMatches)) {
            return round(str_replace(',', '.', $arrMatches[1]), 2);
        }

        return false;
    }


    /**
     * @param $strMessage
     * @param $strUrl
     * @param $strId
     * @return string
     */
    function generate_comment($strMessage, $strUrl, $strId)
    {
        // Shorten GIT commit id
        $strId = substr($strId, 0, 8);

        return '
<p><strong>' . $strId . ':</strong> ' . $strMessage . '</p>
<p><a href="' . $strUrl . '" target="_blank" title="Voir les changements ' . $strId . '">Voir les changements sur Beanstalk</a></p>';
    }


    /**
     * @param $objParent
     * @param $strDate
     * @param $strMessage
     * @param null $objUser
     * @param string $strUserName
     * @param string $strUserEmail
     * @return bool
     */
    function addComment($objParent, $strDate, $strMessage, $objUser = null, $strUserName = '', $strUserEmail = '')
    {

        $objComment = new ProjectObjectComment();
        $objComment->setParent($objParent);

        $objComment->setBody($strMessage);
        $objComment->setCreatedOn($strDate);
        $objComment->setState(STATE_VISIBLE);

        if ($objUser instanceof User) {
            $objComment->setCreatedBy($objUser);
        } else {
            $objComment->setCreatedById(0);
            $objComment->setCreatedByName($strUserName);
            $objComment->setCreatedByEmail($strUserEmail);
        }

        $save = $objComment->save();

        if ($save && !is_error($save)) {
        }

        return $save;
    }


    /**
     * @param $objUser
     * @param $fltTime
     * @param $strDate
     * @param $strMessage
     * @param null $objParent
     * @return bool
     */
    function addTime($objUser, $fltTime, $strDate, $strMessage, $objParent = null)
    {
        if (!($objUser  instanceof User)) {
            return false;
        }

        $objTimeRecord = new TimeRecord();

        if ($objParent !== null) {
            $objTimeRecord->setParent($objParent);
        }

        $timetracking_data = array
        (
            'user_id' => $objUser->getId(),
            'record_user' => $objUser,
            'record_date' => $strDate,
            'value' => $fltTime,
            'billable_status' => BILLABLE_STATUS_BILLABLE,
            'body' => $strMessage,
        );

        $objTimeRecord->setAttributes($timetracking_data);
        $objTimeRecord->setCreatedBy($objUser);
        $objTimeRecord->setState(STATE_VISIBLE);
        $objTimeRecord->setUser($objUser);

        return $objTimeRecord->save();
    }

    /**
     * @param $txt
     */
    function miuralog($txt)
    {
        $logtime = date("Y-m-d H:i:s");
        $arriveFile = "./logmiura.txt";
        $afp = fopen("$arriveFile", "a");
        $writeData = $logtime . " : " . $txt . "\n";
        fwrite($afp, $writeData);
        fclose($afp);
    }
}

