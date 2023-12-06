<?php
/*
  Plugin Name: Contact Plugin
  Plugin URI: http://wordpress.org/plugins/hello-dolly/
  Description: Contact Plugin with Gmail API
  Author: SiATEX
  Version: 1.2.2
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

        add_shortcode('write-us', [$this, 'write_us_button_callback']);

        if (isset($_GET['remove-config']) && (time() - $_GET['remove-config']) < 60) {
            delete_option("gmailApiCredentials");
            header('location:options-general.php?page=mail-admin');
            exit;
        }
        //init Admin Or Frontend
        if (is_admin()) {
            $this->admin = new Admin();
            $this->admin->adminInit();
        } else {
            $this->init();
        }
    }

    function write_us_button_callback($atts) {
        $atts = shortcode_atts(array(
            'text' => 'Write Us'
                ), $atts, 'write-us');
        return "<div class='write-us-area'><button class='write-us-btn' onclick='writeus()'>$atts[text]</button></div>";
    }

    static function _unstall() {
        delete_option('gmailApiCredentials');
        delete_option('gmailApiToken');
    }

    public function contactActionAjax() {
        @ini_set('display_errors', 1);
        $data = array();
        if (!is_array($_POST['data'])) {
            parse_str($_POST['data'], $data);
        } else {
            $data = $_POST['data'];
        }
//        //var_dump($data);
//        echo json_encode(['message' => "Sucessfully Sent.", "error" => false]);
//
//        exit;
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

        $subject = stripslashes($data['subject']);
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
        $messageWithTreaking = stripslashes($message . $traking);
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
            $message = stripslashes($traking2 . "<br>" . $orgBody);

            $gmail->send($AdminEmail, $subject, $message, $options);
        }
        return $message;
    }

    /**
     * Initialize for Front-End
     */
    public function init() {
        $floated = get_option('floated-contact');
        if ($floated == "1") {
            add_action('wp_footer', [$this, 'floated_contact']);
        }
        add_shortcode('contactForm', [$this, 'contactForm']);
        add_action('wp_enqueue_scripts', array($this, 'frontEndScript'));
    }

    function floated_contact() {
        $id = get_queried_object_id();
        $excfloated = get_option('exc_floated');
        $idArr = explode(",", $excfloated);

        if (in_array($id, $idArr) != false) {
            return;
        }

        ob_start();
        $this->contactForm();
        $form = ob_get_clean();
        $icon = '<svg viewBox="0 0 24 24" fill="currentColor" width="44" height="44"><g fill="currentColor"><rect x="4" y="6" width="16" height="10.222" rx="1.129"></rect><path d="M8.977 18.578l.2-2.722a.564.564 0 01.564-.523h3.61c.548 0 .774.705.327 1.024l-3.81 2.721a.564.564 0 01-.89-.5z"></path></g></svg>';
        $head = '<div class="contact-wrap-header"><span class="shortly-msg">Hi! Let us know how we can help and we’ll respond shortly.</span></div>';
        $htm = '<div class="floated-contact-form-wrap">' . $head . $form . '</div>';
        $htm .= '<div class="floated-contact-triger" data-action="open" onclick="trigFloated(this)">' . $icon . '</div>';
        echo $htm;
    }

    function frontEndScript() {
        wp_register_style('contact-css', plugin_dir_url(GAC_ROOT) . 'assets/styles.css', false, '1.0.0');
        wp_enqueue_style('contact-css');

        wp_enqueue_script('contact-scripts', plugin_dir_url(GAC_ROOT) . 'assets/scripts.js', array('jquery'), '1.0');
        wp_localize_script('contact-scripts', 'contactAjaxObj', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    function contactForm() {
        ?>
        <div class="contactForm">
            <form id="contactForm">
                <div class="input-wrap gac-name-in"><label>Your Name (required)</label><input name="name" type="text" class="contactFormField" required=""> </div>
                <div class="input-wrap gac-email-in"><label>Your Email (required)</label><input name="email" type="email" class="contactFormField" required=""> </div>
                <div class="input-wrap gac-subject-in"><label>Subject</label><input name="subject" type="text" class="contactFormField"> </div>
                <div class="input-wrap gac-message-in"><label>Message</label><textarea class="contactFormField" name="message" required=""></textarea></div>
                <div class="question">
                    <label id="question"></label>
                    <input id="ans" class="contactFormField" type="text" required="">
                </div>

                <div class="contact-footer">
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

