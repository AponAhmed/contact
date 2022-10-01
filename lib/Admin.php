<?php

namespace gApiContact\lib;

use gApiContact\lib\GmailApi;
use Google_Client;

/**
 * Description of AdminView
 *
 * @author apon
 */
class Admin {

    public $gmailApi;

    //put your code here
    public function __construct() {
        //ajax Actions Hook for Admin
        add_action('wp_ajax_gApiC_config', [$this, 'gApiC_config']);
        add_action('wp_ajax_adminFonfigUpdate', [$this, 'adminFonfigUpdate']);
        //
        //Assets Reg
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));
    }

    public function admin_assets() {
        wp_register_style('gApicontact-css', plugin_dir_url(GAC_ROOT) . '/assets/admin-style.css', false, '1.0.0');
        wp_enqueue_style('gApicontact-css');
        wp_enqueue_script('gApicontact-scripts', plugin_dir_url(GAC_ROOT) . '/assets/admin-scripts.js', array('jquery'), '1.0');
        wp_localize_script('gApicontact-scripts', 'ajax_object', array('ajax_url' => admin_url('admin-ajax.php'), 'we_value' => 1234));
    }

    /**
     * Init Admin part, Use for any kind of asset or execution in admin area
     */
    public function adminInit() {
        //Admin menu
        add_action("admin_menu", [$this, "gapi_contact_admin_menu"]);
    }

    /**
     * Add Admin menu
     *      */
    public function gapi_contact_admin_menu($param) {

        add_submenu_page(
                "options-general.php", //$parent_slug
                "Mail Config", //$page_title
                "Mail Config", //$menu_title
                "manage_options", //$capability
                "mail-admin", //$menu_slug
                [$this, 'optionPage'] //Calback
        );
    }

    /**
     * Ajax Config POST
     * @return  JSON response;
     */
    public function gApiC_config() {
        if (isset($_POST['jsonData'])) {
            update_option("gmailApiCredentials", $_POST['jsonData']);
            $this->gmailApi = new GmailApi();
            if ($this->gmailApi->client) {
                echo 1;
            } else {
                echo 0;
            }
        }
        wp_die();
    }

    /**
     * Ajax Admin Config Update
     * @return  JSON response;
     */
    function adminFonfigUpdate() {
        $data = array();
        parse_str($_POST['fData'], $data);

        foreach ($data['data'] as $option => $val) {
            update_option($option, $val);
        }
        echo 1;
        wp_die();
    }

    public function optionPage() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1><hr><br><br>
            <?php
            $this->gmailApi = new GmailApi();
            if ($this->gmailApi->configured) {
                if ($this->gmailApi->connect) {
                    $this->adminConfig();
                    //echo "<pre>";
                    //var_dump($this->gmailApi->client);
                    //echo "</pre>";
                } else {
                    $this->login();
                }
            } else {
                $this->SetConfig();
            }
            ?>
        </div>
        <?php
    }

    private function login() {
        $link = $this->gmailApi->client->createAuthUrl();
        echo "<a href='$link' class=\"google-login button action\"><span style=\"padding: 4px 0;\" class=\"dashicons dashicons-google\"></span> Login</a>";
        echo "<br>Login With Google Account";
    }

    public function adminConfig() {
        $name = get_option('admin_name');
        $email = get_option('admin_email');
        $SuEmail = get_option('supervisor_email');
        $tracking = get_option('send_with_tracking');
        $secondMailOp = get_option('second_mailOption');
        //var_dump($name,$email,$SuEmail);
        ?>
        <form id="adminFonfig">
            <div class="max-w5">
                <div class="optionField">
                    <label>Admin Name</label>
                    <div class="input-wrap">
                        <input type="text" name="data[admin_name]" value="<?php echo $name; ?>">
                        <span class="description">Admin Name Who Receive Mail</span>
                    </div>
                </div>
                <div class="optionField">
                    <label>Admin Email</label>
                    <div class="input-wrap">
                        <input type="email" name="data[admin_email]" value="<?php echo $email; ?>">
                        <span class="description">Admin email Address to Receive Mail with tracking information</span>
                    </div>
                </div>
                <hr>
                <div class="optionField">
                    <label>Send Another</label>
                    <div class="input-wrap">
                        <div class="secondMailOption">
                            <select id="secMailControl"  name="data[send_with_tracking]" style="max-width: 62px">
                                <option value="1" <?php echo $tracking == "1" ? "selected" : "" ?>>Yes</option>
                                <option value="0"  <?php echo $tracking == "0" ? "selected" : "" ?>>No</option>
                            </select>
                            <select id="secondMailOption"  name="data[second_mailOption]" >
                                <option value="admin" <?php echo $secondMailOp == "admin" ? "selected" : "" ?>>Admin Address</option>
                                <option value="supervisor"  <?php echo $secondMailOp == "supervisor" ? "selected" : "" ?>>Supervisor Custom</option>
                            </select>
                        </div>
                        <input type="email" title="Supervisor Email Address" class="sup_emailAdress" name="data[supervisor_email]" value="<?php echo $SuEmail; ?>" placeholder="Supervisor Email">
                        <span class="description">Send Another Email Without Tracking Information</span>
                    </div>
                </div>
                <hr>
                <div class="optionField">
                    <label></label>
                    <button type="submit" class="button action updataBtn">Update</button>
                </div>
            </div>
        </form>
        <script>
            jQuery("#adminFonfig").on("submit", function (e) {
                jQuery(".updataBtn").html('Updating...');
                e.preventDefault();
                var data = {
                    action: 'adminFonfigUpdate',
                    fData: jQuery("#adminFonfig").serialize()
                };
                jQuery.post(ajaxurl, data, function (response) {
                    if (response == 1) {
                        jQuery(".updataBtn").html('Updated');
                    }
                });
                //jQuery("#secMailControl")
            });
        </script>
        <?php
    }

    private function SetConfig() {
        ?>
        <form id="gApiCcofigForm">
            <div class="gApiContactConfig">
                <label>Credentials
                    <span class="crdDisc description">Create App and use credentials from <a target="_new" href="https://console.cloud.google.com/apis/credentials?project=gmailapi-364203">Google Api Console</a> </span>
                </label>
                <div class="fieldArea">
                    <!--<div class="upload-area">
                        <input id="uploadConfig" class="hide" type="file">
                        <label for="uploadConfig" class="uploadTrig">Upload</label>
                    </div>-->
                    <div class="configTextInput">
                        <textarea id="jsonData" placeholder="Paste JSON Credentials Here"></textarea>
                    </div>
                    <hr>
                    <button type="submit" class="authButton button button-primary">Authorize</button> 
                </div>
            </div>
        </form>
        <?php
    }

}
