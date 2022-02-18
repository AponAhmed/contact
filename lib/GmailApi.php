<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

namespace gApiContact\lib;

use Google_Client;
use Exception;
use Google_Service_Gmail_Draft;
use Google_Service_Gmail_Message;
use Google_Service_Gmail;

/**
 * Description of GmailApi
 *
 * @author apon
 */
class GmailApi {

    //put your code here
    private $tokenField = "gmailApiToken";
    private $credentials;
    public $token = [];
    public $connect = false;
    public $configured = false;
    //Sender Property
    public $to;
    public $subject;
    public $body;
    public $options;

    public function __construct() {
        $credentials = get_option("gmailApiCredentials");
        if ($credentials != "") {
            $this->configured = true;
            $this->getAccessToken();
        }
        $this->credentials = json_decode(stripslashes($credentials), true);
        if ($this->configured) {
            $this->client = $this->create_client();
        }
    }

    function storeAccessToken() {
        update_option($this->tokenField, json_encode($this->token));
    }

    function getAccessToken() {
        $jsonStr = get_option($this->tokenField);
        if ($jsonStr != "") {
            $this->token = json_decode($jsonStr, true);
        }
    }

    public function get2Redirect() {
        if (isset($_GET["code"])) {
            return true;
        }
        return false;
    }

    public function create_client() {
        $client = new Google_Client();
        $client->setApplicationName("gApiContact");
        $client->setScopes(Google_Service_Gmail::MAIL_GOOGLE_COM);
        $client->setAuthConfig($this->credentials);
        $client->setAccessType("offline");
        $client->setPrompt("select_account consent");

        //$client->setRedirectUri("http://localhost/GmailApi"); // Must Match with credential's redirect URL
        // Load previously authorized token from a file, if it exists.
        // The file token.json stores the user's access and refresh tokens, and is
        // created automatically when the authorization flow completes for the first
        // time.
        //$tokenPath = 'token.json';
        if ($this->token) {
            // $accessToken = json_decode(file_get_contents($this->tokenPath), true);
            $client->setAccessToken($this->token);
        }
        // If there is no previous token or it's expired.
        if ($client->isAccessTokenExpired()) {
            //echo "Expired";
            // Refresh the token if possible, else fetch a new one.
            if ($client->getRefreshToken()) {
                try {
                    $res = $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
                    if (isset($res['error'])) {
                        if ($res['error'] && !$this->get2Redirect()) {
                            $this->token = "";
                            $this->storeAccessToken();
                            $this->getAccessToken();
                            //var_dump($this->token);
                            $this->connect = false;
                            return $client;
                        }
                    }
                } catch (Exception $e) {
                    echo "Not Geting Refresh Token; - " . $e;
                    //echo $e;
                    //$this->department->oauth_token = "";
                    //$this->department->save();
                    $this->connect = false;
                    return $client;
                }
            } elseif ($this->get2Redirect()) {
                // echo "Weating For Token In redirect";

                $authCode = $_GET["code"];
                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists("error", $accessToken)) {
                    throw new Exception(join(", ", $accessToken));
                }
            } else {
                //echo "Revoke";
                $this->connect = false;
                return $client;
            }
            $this->token = $client->getAccessToken();
            //var_dump($this->token);
            $this->storeAccessToken();
        } else {
            //echo "<p>not expired</p>";
        }
        //echo "Connected";
        $this->connect = true;
        return $client;
    }

    /**
     * To Send Email Via Gmail API
     * 
     * @param mix $to Receiver array or string for single send
     * @param string $subject Subject line of email
     * @param string $message Mail Body 
     * @param Array $options Mail Sending options  
     */
    public function send($to, $subject = "", $message = "", $options = []) {
        //var_dump($to, $subject, $message, $options);

        $this->to = $to;
        $this->subject = $subject;
        $this->body = $message;

        $defaultOption = [
            'fromName' => "",
            'fromEmail' => "",
            'toName' => "",
            'CC' => "",
            'BCC' => "",
            "Return-Path" => ""
        ];

        $options = array_merge($defaultOption, $options);

        $this->options = $options;

        $service = new Google_Service_Gmail($this->client);
        //var_dump($service);
        // Print the labels in the user's account.
        //FormEmail
        $FormEmail = !empty($options['fromEmail']) ? $options['fromEmail'] : "me";
        $message = $this->createMessage($FormEmail, $to, $subject, $message, $options);
        //$draft = new Google_Service_Gmail_Draft();
        //$draft->setMessage($message);
        //$draft = $service->users_drafts->create('me', $draft);
        $message = $service->users_messages->send('me', $message);
        //var_dump($message);
        // var_dump($this->client); 
        if (isset($message->id) && !empty($message->id)) {
            return true;
        }
        return false;
    }

    /**
     * @param $sender string sender email address
     * @param $to string recipient email address
     * @param $subject string email subject
     * @param $messageText string email text
     * @return Google_Service_Gmail_Message
     */
    function createMessage($sender, $to, $subject, $messageText, $options) {
        $message = new Google_Service_Gmail_Message();
        $rawMessageString = $this->createMessageMIME();
        $rawMessage = strtr($rawMessageString, array('+' => '-', '/' => '_'));
        $message->setRaw($rawMessage);
        return $message;
    }

    /**
     * To Create MIME of Mail By PHPMailer  
     *     
     */
    public function createMessageMIME() {
        $mail = new \PHPMailer\PHPMailer\PHPMailer();

        $mail->CharSet = "UTF-8";
        $mail->SetFrom($this->options['fromEmail'], $this->options['fromName']);
        $mail->From = $this->options['fromEmail'];
        $mail->FromName = $this->options['fromName'];

        $mail->addAddress($this->to, $this->options['toName']);     //Add a recipient

        if ($this->options['Return-Path']) {
            $mail->addReplyTo($this->options['Return-Path']);
        }
        if (isset($this->options['CC']) && !empty($this->options['CC'])) {
            $mail->addCC($this->options['CC']);
        }
        if (isset($this->options['BCC']) && !empty($this->options['BCC'])) {
            $mail->addBCC($this->options['BCC']);
        }
        $mail->XMailer = "GmailAPI-MIME::PHPMailer";
        $mail->MessageID = "<" . md5('HELLO' . (idate("U") - 1000000000) . uniqid()) . "@gmail.com>";

        $mail->isHTML(true);
        $mail->Subject = $this->subject;
        $mail->Body = $this->body;
        //Pre send to generate MIME
        $mail->preSend();
        $mime = $mail->getSentMIMEMessage();
        $mime = rtrim(strtr(base64_encode($mime), '+/', '-_'), '=');
        return $mime;
    }

    /**
     * @param $service Google_Service_Gmail an authorized Gmail API service instance.
     * @param $user string User's email address or "me"
     * @param $message Google_Service_Gmail_Message
     * @return Google_Service_Gmail_Draft
     */
    function createDraft($service, $user, $message) {
        $draft = new Google_Service_Gmail_Draft();
        $draft->setMessage($message);

        try {
            $draft = $service->users_drafts->create($user, $draft);
            print 'Draft ID: ' . $draft->getId();
        } catch (Exception $e) {
            print 'An error occurred: ' . $e->getMessage();
        }

        return $draft;
    }

    /**
     * @param $service Google_Service_Gmail an authorized Gmail API service instance.
     * @param $userId string User's email address or "me"
     * @param $message Google_Service_Gmail_Message
     * @return null|Google_Service_Gmail_Message
     */
    function sendMessage($service, $userId, $message) {
        try {
            $message = $service->users_messages->send($userId, $message);
            print 'Message with ID: ' . $message->getId() . ' sent.';
            return $message;
        } catch (Exception $e) {
            print 'An error occurred: ' . $e->getMessage();
        }

        return null;
    }

    function getClientIP() {
        if (isset($_SERVER)) {
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
                return $_SERVER["HTTP_X_FORWARDED_FOR"];
            if (isset($_SERVER["HTTP_CLIENT_IP"]))
                return $_SERVER["HTTP_CLIENT_IP"];
            return $_SERVER["REMOTE_ADDR"];
        }
        if (getenv('HTTP_X_FORWARDED_FOR'))
            return getenv('HTTP_X_FORWARDED_FOR');

        if (getenv('HTTP_CLIENT_IP'))
            return getenv('HTTP_CLIENT_IP');

        return getenv('REMOTE_ADDR');
    }

    function convertip($ip) {
        //?fields=country,city,lat,lon
        $url = "http://ip-api.com/json/$ip";
        $content = file_get_contents($url);
        $ob = json_decode($content);
        if (isset($ob->status) && $ob->status == 'success') {
            return $ob->city . "," . $ob->country;
        }
    }

}
