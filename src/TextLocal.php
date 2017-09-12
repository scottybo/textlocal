<?php
namespace App\TextLocalApi;
use Illuminate\Contracts\Config\Repository;
use GuzzleHttp\Client;

/**
 * Textlocal API2 Wrapper Class
 *
 * This class is used to interface with the Textlocal API2 to send messages, manage contacts, retrieve messages from
 * inboxes, track message delivery statuses, access history reports
 * 
 * Original code by Andy Dixon (andy.dixon@textlocal.com), modified to work with Laravel and Guzzle
 *
 * @package    Textlocal
 * @subpackage API
 * @author     Scott Bowler <scott.bowler@dcsworldwide.net>
 * @version    1.4-UK
 */
class TextLocal
{

	private $config;

	public $errors = array();
	public $warnings = array();
	public $lastRequest = array();

	/**
	 * Instantiate the object
	 */
	function __construct(Repository $config)
	{
            $this->config = $config;
	}

	/**
	 * Private function to construct and send the request and handle the response
	 * @param       $command
	 * @param array $params
	 * @return array|mixed
	 * @throws TextLocalException
	 * @todo Add additional request handlers - eg fopen, file_get_contacts
	 */
	private function _sendRequest($command, $params = array())
	{
            if ($this->config->get('textlocal.key') && !empty($this->config->get('textlocal.key'))) {
                $params['apiKey'] = $this->config->get('textlocal.key');
            } else {
                $params['hash'] = $this->config->get('textlocal.hash');
            }
            
            // Create request string
            $params['username'] = $this->config->get('textlocal.username');

            $this->lastRequest = $params;

            $client = new Client(); //GuzzleHttp\Client
            $result = $client->post($this->config->get('textlocal.url') . $command . '/', [
                'form_params' => $params
            ]);

            $body = json_decode($result->getBody());

            if($body->status != 'success') {
                if (isset($body->errors) && count($body->errors) > 0) {
                    foreach ($body->errors as $error) {
                        throw new TextLocalException('TextLocal API returned an error: '. $error->message);
                    }
                }
            }          

            return $body;
	}

	/**
	 * Get last request's parameters
	 * @return array
	 */
	public function getLastRequest()
	{
		return $this->lastRequest;
	}

	/**
	 * Send an SMS to one or more comma separated numbers
	 * @param       $numbers
	 * @param       $message
	 * @param       $sender
	 * @param null  $sched
	 * @param false $test
	 * @param null  $receiptURL
	 * @param numm  $custom
	 * @param false $optouts
	 * @param false $simpleReplyService
	 * @return array|mixed
	 * @throws TextLocalException
	 */

	public function sendSms($numbers, $message, $sender, $sched = null, $test = false, $receiptURL = null, $custom = null, $optouts = false, $simpleReplyService = false)
	{

            if (!is_array($numbers))
                    throw new TextLocalException('Invalid $numbers format. Must be an array');
            if (empty($message))
                    throw new TextLocalException('Empty message');
            if (empty($sender))
                    throw new TextLocalException('Empty sender name');
            if (!is_null($sched) && !is_numeric($sched))
                    throw new TextLocalException('Invalid date format. Use numeric epoch format');

            $params = array(
                    'message'       => rawurlencode($message),
                    'numbers'       => implode(',', $numbers),
                    'sender'        => rawurlencode($sender),
                    'schedule_time' => $sched,
                    'test'          => $test,
                    'receipt_url'   => $receiptURL,
                    'custom'        => $custom,
                    'optouts'       => $optouts,
                    'simple_reply'  => $simpleReplyService
            );

            return $this->_sendRequest('send', $params);
	}


	/**
	 * Send an SMS to a Group of contacts - group IDs can be retrieved from getGroups()
	 * @param       $groupId
	 * @param       $message
	 * @param null  $sender
	 * @param false $test
	 * @param null  $receiptURL
	 * @param numm  $custom
	 * @param false $optouts
	 * @param false $simpleReplyService
	 * @return array|mixed
	 * @throws TextLocalException
	 */
	public function sendSmsGroup($groupId, $message, $sender = null, $sched = null, $test = false, $receiptURL = null, $custom = null, $optouts = false, $simpleReplyService = false)
	{

		if (!is_numeric($groupId))
			throw new TextLocalException('Invalid $groupId format. Must be a numeric group ID');
		if (empty($message))
			throw new TextLocalException('Empty message');
		if (empty($sender))
			throw new TextLocalException('Empty sender name');
		if (!is_null($sched) && !is_numeric($sched))
			throw new TextLocalException('Invalid date format. Use numeric epoch format');

		$params = array(
			'message'       => rawurlencode($message),
			'group_id'      => $groupId,
			'sender'        => rawurlencode($sender),
			'schedule_time' => $sched,
			'test'          => $test,
			'receipt_url'   => $receiptURL,
			'custom'        => $custom,
			'optouts'       => $optouts,
			'simple_reply'  => $simpleReplyService
		);

		return $this->_sendRequest('send', $params);
	}
	
    /**
     * Send bulk SMS messages.
     * 
     * @param  string $data JSON-formatted string.
     * @throws \Exception
     * @return mixed
     */
    public function sendBulkSms($data)
    {
        if (is_array($data)) {
            $data = json_encode($data);
        
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON string');
            }
        }
        
        if (strlen(trim($data)) === 0) {
            throw new \Exception('No data to send');
        }

        return $this->_sendRequest('bulk_json', array(
            'data' => $data,
        ));
    }

	/**
	 * Send an MMS to a one or more comma separated contacts
	 * @param       $numbers
	 * @param       $fileSource - either an absolute or relative path, or http url to a file.
	 * @param       $message
	 * @param null  $sched
	 * @param false $test
	 * @param false $optouts
	 * @return array|mixed
	 * @throws TextLocalException
	 */
	public function sendMms($numbers, $fileSource, $message, $sched = null, $test = false, $optouts = false)
	{

		if (!is_array($numbers))
			throw new TextLocalException('Invalid $numbers format. Must be an array');
		if (empty($message))
			throw new TextLocalException('Empty message');
		if (empty($fileSource))
			throw new TextLocalException('Empty file source');
		if (!is_null($sched) && !is_numeric($sched))
			throw new TextLocalException('Invalid date format. Use numeric epoch format');

		$params = array(
			'message'       => rawurlencode($message),
			'numbers'       => implode(',', $numbers),
			'schedule_time' => $sched,
			'test'          => $test,
			'optouts'       => $optouts
		);

		/** Local file. POST to service */
		if (is_readable($fileSource))
			$params['file'] = '@' . $fileSource;
		else $params['url'] = $fileSource;

		return $this->_sendRequest('send_mms', $params);
	}

	/**
	 * Send an MMS to a group - group IDs can be
	 * @param       $groupId
	 * @param       $fileSource
	 * @param       $message
	 * @param null  $sched
	 * @param false $test
	 * @param false $optouts
	 * @return array|mixed
	 * @throws TextLocalException
	 */
	public function sendMmsGroup($groupId, $fileSource, $message, $sched = null, $test = false, $optouts = false)
	{

		if (!is_numeric($groupId))
			throw new TextLocalException('Invalid $groupId format. Must be a numeric group ID');
		if (empty($message))
			throw new TextLocalException('Empty message');
		if (empty($fileSource))
			throw new TextLocalException('Empty file source');
		if (!is_null($sched) && !is_numeric($sched))
			throw new TextLocalException('Invalid date format. Use numeric epoch format');

		$params = array(
			'message'       => rawurlencode($message),
			'group_id'      => $groupId,
			'schedule_time' => $sched,
			'test'          => $test,
			'optouts'       => $optouts
		);

		/** Local file. POST to service */
		if (is_readable($fileSource))
			$params['file'] = '@' . $fileSource;
		else $params['url'] = $fileSource;

		return $this->_sendRequest('send_mms', $params);
	}

	/**
	 *Returns reseller customer's ID's
	 * @return array
	 **/

	public function getUsers()
	{
		return $this->_sendRequest('get_users');
	}

	/**
	 * Transfer credits to a reseller's customer
	 * @param $user - can be ID or Email
	 * @param $credits
	 * @return array|mixed
	 * @throws TextLocalException
	 **/

	public function transferCredits($user, $credits)
	{

		if (!is_numeric($credits))
			throw new TextLocalException('Invalid credits format');
		if (!is_numeric($user))
			throw new TextLocalException('Invalid user');
		if (empty($user))
			throw new TextLocalException('No user specified');
		if (empty($credits))
			throw new TextLocalException('No credits specified');

		if (is_int($user)) {
			$params = array(
				'user_id' => $user,
				'credits' => $credits
			);
		} else {
			$params = array(
				'user_email' => rawurlencode($user),
				'credits'    => $credits
			);
		}

		return $this->_sendRequest('transfer_credits', $params);
	}

	/**Get templates from an account **/

	public function getTemplates()
	{
		return $this->_sendRequest('get_templates');
	}

	/** Check the availability of a keyword
	 * @param $keyword
	 * return array|mixed
	 */
	public function checkKeyword($keyword)
	{

		$params = array('keyword' => $keyword);
		return $this->_sendRequest('check_keyword', $params);
	}

	/**
	 * Create a new contact group
	 * @param $name
	 * @return array|mixed
	 */
	public function createGroup($name)
	{
		$params = array('name' => $name);
		return $this->_sendRequest('create_group', $params);
	}

	/**
	 * Get contacts from a group - Group IDs can be retrieved with the getGroups() function
	 * @param     $groupId
	 * @param     $limit
	 * @param int $startPos
	 * @return array|mixed
	 * @throws TextLocalException
	 */
	public function getContacts($groupId, $limit, $startPos = 0)
	{

		if (!is_numeric($groupId))
			throw new TextLocalException('Invalid $groupId format. Must be a numeric group ID');
		if (!is_numeric($startPos) || $startPos < 0)
			throw new TextLocalException('Invalid $startPos format. Must be a numeric start position, 0 or above');
		if (!is_numeric($limit) || $limit < 1)
			throw new TextLocalException('Invalid $limit format. Must be a numeric limit value, 1 or above');

		$params = array(
			'group_id' => $groupId,
			'start'    => $startPos,
			'limit'    => $limit
		);
		return $this->_sendRequest('get_contacts', $params);
	}

	/**
	 * Create one or more number-only contacts in a specific group, defaults to 'My Contacts'
	 * @param        $numbers
	 * @param string $groupid
	 * @return array|mixed
	 */
	public function createContacts($numbers, $groupid = '5')
	{
		$params = array("group_id" => $groupid);

		if (is_array($numbers)) {
			$params['numbers'] = implode(',', $numbers);
		} else {
			$params['numbers'] = $numbers;
		}

		return $this->_sendRequest('create_contacts', $params);
	}

	/**
	 * Create bulk contacts - with name and custom information from an array of:
	 * [first_name] [last_name] [number] [custom1] [custom2] [custom3]
	 *
	 * @param array  $contacts
	 * @param string $groupid
	 * @return array|mixed
	 */
	function createContactsBulk($contacts, $groupid = '5')
	{
		// JSON & URL-encode array
		$contacts = urlencode(json_encode($contacts));

		$params = array
		("group_id" => $groupid, "contacts" => $contacts);
		return $this->_sendRequest('create_contacts_bulk', $params);
	}

	/**
	 * Get a list of groups and group IDs
	 * @return array|mixed
	 */
	public function getGroups()
	{
		return $this->_sendRequest('get_groups');
	}

	/**
	 * Get the status of a message based on the Message ID - this can be taken from sendSMS or from a history report
	 * @param $messageid
	 * @return array|mixed
	 */
	public function getMessageStatus($messageid)
	{
		$params = array("message_id" => $messageid);
		return $this->_sendRequest('status_message', $params);
	}

	/**
	 * Get the status of a message based on the Batch ID - this can be taken from sendSMS or from a history report
	 * @param $batchid
	 * @return array|mixed
	 */
	public function getBatchStatus($batchid)
	{
		$params = array("batch_id" => $batchid);
		return $this->_sendRequest('status_batch', $params);
	}

	/**
	 * Get sender names
	 * @return array|mixed
	 */
	public function getSenderNames()
	{
		return $this->_sendRequest('get_sender_names');
	}

	/**
	 * Get inboxes available on the account
	 * @return array|mixed
	 */
	public function getInboxes()
	{
		return $this->_sendRequest('get_inboxes');
	}

	/**
	 * Get Credit Balances
	 * @return array
	 */
	public function getBalance()
	{
		$result = $this->_sendRequest('balance');
		return array('sms' => $result->balance->sms, 'mms' => $result->balance->mms);
	}

	/**
	 * Get messages from an inbox - The ID can ge retrieved from getInboxes()
	 * @param $inbox
	 * @return array|bool|mixed
	 */
	public function getMessages($inbox)
	{
		if (!isset($inbox)) return false;
		$options = array('inbox_id' => $inbox);
		return $this->_sendRequest('get_messages', $options);
	}

	/**
	 * Cancel a scheduled message based on a message ID from getScheduledMessages()
	 * @param $id
	 * @return array|bool|mixed
	 */
	public function cancelScheduledMessage($id)
	{
		if (!isset($id)) return false;
		$options = array('sent_id' => $id);
		return $this->_sendRequest('cancel_scheduled', $options);
	}

	/**
	 * Get Scheduled Message information
	 * @return array|mixed
	 */
	public function getScheduledMessages()
	{
		return $this->_sendRequest('get_scheduled');
	}

	/**
	 * Delete a contact based on telephone number from a group
	 * @param     $number
	 * @param int $groupid
	 * @return array|bool|mixed
	 */
	public function deleteContact($number, $groupid = 5)
	{
		if (!isset($number)) return false;
		$options = array('number' => $number, 'group_id' => $groupid);
		return $this->_sendRequest('delete_contact', $options);
	}

	/**
	 * Delete a group - Be careful, we can not recover any data deleted by mistake
	 * @param $groupid
	 * @return array|mixed
	 */
	public function deleteGroup($groupid)
	{
		$options = array('group_id' => $groupid);
		return $this->_sendRequest('delete_group', $options);
	}


	/**
	 * Get single SMS history (single numbers, comma seperated numbers when sending)
	 * @param $start
	 * @param $limit
	 * @param $min_time             Unix timestamp
	 * @param $max_time             Unix timestamp
	 * @return array|bool|mixed
	 */
	public function getSingleMessageHistory($start, $limit, $min_time, $max_time)
	{
		return $this->getHistory('get_history_single', $start, $limit, $min_time, $max_time);
	}

	/**
	 * Get API SMS Message history
	 * @param $start
	 * @param $limit
	 * @param $min_time             Unix timestamp
	 * @param $max_time             Unix timestamp
	 * @return array|bool|mixed
	 */
	public function getAPIMessageHistory($start, $limit, $min_time, $max_time)
	{
		return $this->getHistory('get_history_api', $start, $limit, $min_time, $max_time);
	}

	/**
	 * Get Email to SMS History
	 * @param $start
	 * @param $limit
	 * @param $min_time             Unix timestamp
	 * @param $max_time             Unix timestamp
	 * @return array|bool|mixed
	 */
	public function getEmailToSMSHistory($start, $limit, $min_time, $max_time)
	{
		return $this->getHistory('get_history_email', $start, $limit, $min_time, $max_time);
	}

	/**
	 * Get group SMS history
	 * @param $start
	 * @param $limit
	 * @param $min_time             Unix timestamp
	 * @param $max_time             Unix timestamp
	 * @return array|bool|mixed
	 */
	public function getGroupMessageHistory($start, $limit, $min_time, $max_time)
	{
		return $this->getHistory('get_history_group', $start, $limit, $min_time, $max_time);
	}

	/**
	 * Generic function to provide validation and the request method for getting all types of history
	 * @param $type
	 * @param $start
	 * @param $limit
	 * @param $min_time
	 * @param $max_time
	 * @return array|bool|mixed
	 */
	private function getHistory($type, $start, $limit, $min_time, $max_time)
	{
		if (!isset($start) || !isset($limit) || !isset($min_time) || !isset($max_time)) return false;
		$options = array('start' => $start, 'limit' => $limit, 'min_time' => $min_time, 'max_time' => $max_time);
		return $this->_sendRequest($type, $options);
	}

	/**
	 * Get a list of surveys
	 * @return array|mixed
	 */
	public function getSurveys()
	{
		return $this->_sendRequest('get_surveys');
	}

	/**
	 * Get a deatils of a survey
	 * @return array|mixed
	 */
	public function getSurveyDetails()
	{
		$options = array('survey_id' => $surveyid);
		return $this->_sendRequest('get_survey_details');
	}

	/**
	 * Get a the results of a given survey
	 * @return array|mixed
	 */
	public function getSurveyResults($surveyid, $start, $end)
	{
		$options = array('survey_id' => $surveyid, 'start_date' => $start, 'end_date' => $end);
		return $this->_sendRequest('get_surveys', $options);
	}

	/**
	 * Get all account optouts
	 * @return array|mixed
	 */

	public function getOptouts($time = null)
	{
		return $this->_sendRequest('get_optouts');
	}
}

class Contact
{
	var $number;
	var $first_name;
	var $last_name;
	var $custom1;
	var $custom2;
	var $custom3;

	var $groupID;

	/**
	 * Structure of a contact object
	 * @param        $number
	 * @param string $firstname
	 * @param string $lastname
	 * @param string $custom1
	 * @param string $custom2
	 * @param string $custom3
	 */
	function __construct($number, $firstname = '', $lastname = '', $custom1 = '', $custom2 = '', $custom3 = '')
	{
		$this->number = $number;
		$this->first_name = $firstname;
		$this->last_name = $lastname;
		$this->custom1 = $custom1;
		$this->custom2 = $custom2;
		$this->custom3 = $custom3;
	}
}
