<?php
/*
  Plugin Name: Contact Plugin
  Plugin URI: http://wordpress.org/plugins/hello-dolly/
  Description: Contact Plugin with Gmail API
  Author: SiATEX
  Version: 1.0
  Author URI: http://siatex.com/
 */

namespace gApiContact;

define("LIB", dirname(__FILE__) . "/lib/");
define("GAC_ROOT", __FILE__);

use gApiContact\lib\Admin;
use gApiContact\lib\GmailApi;

include "vendor/autoload.php";
foreach (glob(LIB . "*.php") as $filename) {
    include $filename;
}

class GapiContact {

    public $admin;

    public function __construct() {
        add_action('wp_ajax_contactActionAjax', [$this, 'contactActionAjax']);
        add_action('wp_ajax_nopriv_contactActionAjax', [$this, 'contactActionAjax']);

        //init Admin Or Frontend
        if (is_admin()) {
            $this->admin = new Admin();
            $this->admin->adminInit();
        } else {
            $this->init();
        }
    }

    public function contactActionAjax() {
        @ini_set('display_errors', 1);
        $data = array();
        parse_str($_POST['data'], $data);
        $res = $this->sendEmail($data);
        if ($res) {
            echo json_encode(['message' => "Sucessfully Sent.", "error" => false]);
        } else {
            echo json_encode(['message' => "Sorry !, Message Not Send. Please try again", "error" => true]);
        }
        wp_die(); //End Request
    }

    function sendEmail($data) {
        $gmail = new GmailApi();
        $AdminName = get_option('admin_name');
        $AdminEmail = get_option('admin_email');
        $tracking = get_option('send_with_tracking');

        $SuEmail = get_option('supervisor_email');
        $secondMailOp = get_option('second_mailOption');

        $subject = $data['subject'];
        $message = nl2br($data['message']); //Orginal Message       
        //Traking information
        $clientIP = $gmail->getClientIP();
        $country = $gmail->convertip($clientIP);

        $traking = "";
        $traking .= "<br/><br/>\n<b>Subject:</b> " . $data['subject'] . " (" . get_site_url() . ")";
        $traking .= "<br/>\n<b>Name:</b> $data[name]";
        $traking .= "<br/>\n<b>Email:</b> $data[email]";
        $traking .= "<br/>\n<b>Date:</b> " . date("F d, Y");
        $traking .= "<br/>\n<b>IP:</b> <a href=\"https://ip-api.com/#" . $clientIP . "\">" . $clientIP . "</a>&nbsp;&nbsp;(" . $country . ")";
        if (!empty($data['reff'])) {
            $traking .= "<br/><br/>\n<b>Referer Page:</b> " . $data['reff'];
        }
        $messageWithTreaking = $message . $traking;
        //Traking 
        $options = [
            'fromName' => $data['name'],
            'fromEmail' => $data['email'],
            'toName' => $AdminName,
            'Return-Path' => $data['email']
        ];
        $orgBody = $message;
        $message = $gmail->send($AdminEmail, $subject, $messageWithTreaking, $options); //With Tracking
        //        
        //Second Mail-------------------------
        if ($tracking == 1) {//without Tracking
            if ($secondMailOp == "supervisor") {
                $AdminEmail = $SuEmail;
            }
            $traking2 = "\n<b>Name:</b> $data[name]";
            $traking2 .= "<br/>\n<b>Email:</b> $data[email]";
            $traking2 .= "<br/>\n<b>Date:</b> " . date("F d, Y");
            $traking2 .= "<br/><b>Website:</b> " . get_site_url();
            $traking2 .= "<br/>";
            $message = $traking2 . "<br>" . $orgBody;
            $gmail->send($AdminEmail, $subject, $message, $options);
        }

        return $message;
    }

    /**
     * Initialize for Front-End
     */
    public function init() {
        add_shortcode('contactForm', [$this, 'contactForm']);
        add_action('wp_enqueue_scripts', array($this, 'frontEndScript'));
    }

    function frontEndScript() {
        wp_register_style('contact-css', plugin_dir_url(GAC_ROOT) . '/assets/styles.css', false, '1.0.0');
        wp_enqueue_style('contact-css');

        wp_enqueue_script('contact-scripts', plugin_dir_url(GAC_ROOT) . '/assets/scripts.js', array('jquery'), '1.0');
        wp_localize_script('contact-scripts', 'contactAjaxObj', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    function contactForm() {
        ?>
        <div class="contactForm">
            <form id="contactForm">
                <div class="input-wrap"><label>Your Name (required)</label><input name="name" type="text" class="contactFormField" required=""> </div>
                <div class="input-wrap"><label>Your Email (required)</label><input name="email" type="email" class="contactFormField" required=""> </div>
                <div class="input-wrap"><label>Subject</label><input name="subject" type="text" class="contactFormField"> </div>
                <div class="input-wrap"><label>Message</label><textarea class="contactFormField" name="message" required=""></textarea></div>
                <div class="question">
                    <label id="question"></label>
                    <input id="ans" class="contactFormField" type="text" required="">
                </div>
                <div>
                    <button type="submit" id="submitBtn" class="contactFormButton">Send</button>
                    <span class="contactMsg"></span>
                </div>
            </form>
            <script>
                window.addEventListener('load', (event) => {
                    contactForm_init();
                });
            </script>
        </div>
        <?php
    }

}

$gApiContact = new GapiContact();

