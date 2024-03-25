<?php
/**
 * Gmail
 *
 * @author Pierre - dev@nettools.ovh
 * @license MIT
 */



// namespace
namespace Nettools\Mailing\MailSenders;


use \Nettools\Mailing\Mailer;
use \Nettools\Mailing\MailerEngine\Headers;





/**
 * Strategy to send emails with Gmail API
 *
 * The `$params` constructor array parameter may define the following values :
 *   - label (string) : to assign a label to sent email
 */
class Gmail extends MailSender
{
	// [----- PROTECTED -----
	
	protected $service = NULL;    

	// ----- PROTECTED -----]
	
	
	/**
	 * Set Google service object
	 *
	 * @param \Google\Service\Gmail $service Gmail service object to send messages through
	 */ 
	function setGmailServiceObject(\Google\Service\Gmail $service)
	{
		$this->service = $service;
	}
	
	

	/**
	 * Is the mailsender object ready ?
	 *
	 * @return bool
	 */
	function ready()
	{
		// tester 
		return parent::ready() && !is_null($this->service);
	}
	
	
	
	/**
	 * Destroy object, and disconnet 
	 */
	function destroy()
	{
		if ( $this->params['persist'] && $this->ready() )
			$this->service = null;
	}
	
	
	
	/**
	 * Override default SMTP behavior, where a message is sent for each bcc recipient ; gmail (as php `mail` function) handle to/cc/bcc headers
	 *
     * @param string $subject Subject
     * @param string $mail String containing the email data
     * @param \Nettools\Mailing\MailerEngine\Headers $headers Email headers
	 */
	function handleBcc($subject, $mail, Headers $headers)
	{
		// do nothing
	}
	
	
	
	/**
     * Handle Cc 
     *
     * For PHPMail strategy, we don't have to do anything, as gmail handle cc header
     *
     * @param string $subject Subject
     * @param string $mail String containing the email data
     * @param \Nettools\Mailing\MailerEngine\Headers $headers Email headers
     */
	function handleCc($subject, $mail, Headers $headers)
	{
	}
	
	
	
	/**
	 * Sending email
	 *
     * @param string $to Recipient
     * @param string $subject Subject ; must be encoded if necessary
     * @param string $mail String containing the email data
     * @param string $headers Email headers
	 * @throws \Nettools\Mailing\Exception
	 */
	function doSend($to, $subject, $mail, $headers)
	{
		// merge headers and mail content ; to/subject headers are already in `$headers` thanks to MailSender::handleHeaders_ToSubject function call
		$m = $headers/* . "\r\nTo: $to"*/ . "\r\n\r\n" . $mail;
		
		$mbody = new \Google\Service\Gmail\Message(['raw' => base64_encode($m)]);

		if ( $id = $this->service->users_messages->send('me', $mbody)->id )
		{
			// if 'label' parameter
			if ( array_key_exists('label', $this->params) )
				if ( $label = $this->params['label'] )
				{
					$req = new \Google\Service\Gmail\ModifyMessageRequest();
					$req->setAddLabelIds([$label]);
					$this->service->users_messages->modify('me', $id, $req);
				}
			
			
			return TRUE;
		}
		else
			throw new \Nettools\Mailing\Exception('Message not sent');
	}
}
?>