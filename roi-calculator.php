<?php
/*
Plugin Name: ROI Calculator
Description: ROI calculator plugin.
Version: 1.0
Author: Marko Moguljak
*/

if (!class_exists('ROICalculatorPlugin')) {
   class ROICalculatorPlugin
   {
      private $options;

      public function __construct()
      {
         if (!session_id()) {
            session_start();
         }
         $this->options = get_option('roi_calculator_options');
         add_shortcode('roi_calculator_form', array($this, 'display_form'));
         add_action('admin_menu', array($this, 'admin_menu'));
         add_action('admin_post_delete_submission', array($this, 'delete_submission'));
         add_action('admin_post_export_to_csv', array($this, 'export_to_csv'));
         // add_action('admin_post_save_roi_calculator_settings', array($this, 'save_settings'));
         add_action('admin_menu', array($this, 'add_settings_page'));
         add_action('admin_post_save_roi_calculator_settings', array($this, 'save_roi_calculator_settings'));
         add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
         add_action('wp_ajax_handle_form_submission', array($this, 'handle_form_submission'));
         add_action('wp_ajax_nopriv_handle_form_submission', array($this, 'handle_form_submission'));
         add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_styles'));
      }

      public function enqueue_scripts()
      {
         wp_enqueue_style('roi_calculator_style', plugins_url('css/style.css', __FILE__));
         wp_enqueue_script('roi-calculator', plugin_dir_url(__FILE__) . 'js/roi-calculator.js', array('jquery'), '1.0.0', true);
         wp_localize_script('roi-calculator', 'roiCalculatorAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php')
         ));
      }

      public function enqueue_admin_styles($hook)
      {
         if ($hook != 'toplevel_page_roi-calculator' && $hook != 'roi-calculator_page_roi-calculator-user-data') {
            return;
         }

         wp_enqueue_style('roi_calculator_admin_style', plugins_url('css/admin-style.css', __FILE__));
      }

      public function display_form()
      {
         ob_start();
?>
         <div class="roi-calculator">
            <div class="roi-calculator-wrapper">
               <div class="roi-calculator-form-wrapper">
                  <form id="roi-calculator-form" class="roi-calculator-form">
                     <label for="monthly_spendings">Mjesečna potrošnja u EUR</label>
                     <input type="number" name="monthly_spendings" id="monthly_spendings" required>

                     <label for="region">Odaberite regiju</label>
                     <select name="region" id="region" required>
                        <option value="Grad Zagreb i Središnja Hrvatska" data-value="1">Grad Zagreb i Središnja Hrvatska</option>
                        <option value="Istočna Hrvatska" data-value="1.1">Istočna Hrvatska</option>
                        <option value="Gorska Hrvatska" data-value="1.1">Gorska Hrvatska</option>
                        <option value="Istra i Kvarner" data-value="1">Istra i Kvarner</option>
                        <option value="Dalmacija" data-value="1.1">Dalmacija</option>
                     </select>
                     <div class="roi-form-row">
                        <div class="roi-form-item">
                           <label for="your_name">Ime i prezime</label>
                           <input type="text" name="your_name" id="your_name" required>
                        </div>
                        <div class="roi-form-item">
                           <label for="phone_number">Telefon</label>
                           <input type="text" name="phone_number" id="phone_number" required>
                        </div>
                     </div>
                     <label for="email">E-mail</label>
                     <input type="email" name="email" id="email" required>
                     <label for="privacy_policy" class="roi-form-checkbox">
                        <div>Prihvaćam politiku privatnosti</div>
                        <input type="checkbox" name="privacy_policy" id="privacy_policy" required>
                        <span class="checkmark"></span>
                     </label>
                     <label for="specialised_offer" class="roi-form-checkbox">
                        <div>Želim ponudu za solarnu elektranu po mjeri</div>
                        <input type="checkbox" name="specialised_offer" id="specialised_offer">
                        <span class="checkmark"></span>
                     </label>

                     <input type="submit" value="Izračunaj uštedu" class="roi-btn">
                  </form>
               </div>
               <div id="roi-calculator-results" class="roi-calculator-results-wrapper">
                  <h3>Izračun potrošnje</h3>
                  <div class="results-item">
                     <div class="results-text">Vaš godišnji trošak električne energije/EUR: </div>
                     <div class="results-number"><span id="roi-results-annual">0.00</span><span>€</span></div>
                  </div>
                  <div class="results-item">
                     <div class="results-text">Vaš godišnji trošak električne energije nakon instalacije solarne elektrane/EUR: </div>
                     <div class="results-number"><span id="roi-results-powerplant">0.00</span><span>€</span></div>
                  </div>
                  <div class="results-item">
                     <div class="results-text">Ukupna godišnja ušteda/EUR: </div>
                     <div class="results-number"><span id="roi-results-savings">0.00</span><span>€</span></div>
                  </div>
                  <div class="results-item">
                     <div class="results-text">Povrat investicije/godine: </div>
                     <div class="results-number"><span id="roi-results-return">0</span></div>
                  </div>

               </div>

            </div>
         </div>
      <?php
         return ob_get_clean();
      }

      public function admin_menu()
      {
         add_menu_page('ROI Calculator', 'ROI Calculator', 'manage_options', 'roi-calculator', array($this, 'settings_page'), 'dashicons-calculator', 20);
         add_submenu_page('roi-calculator', 'Settings', 'Settings', 'manage_options', 'roi-calculator-settings', array($this, 'settings_page'));
         add_submenu_page('roi-calculator', 'User Data', 'User Data', 'manage_options', 'roi-calculator-user-data', array($this, 'user_data_page'));
      }

      public function user_data_page()
      {
         global $wpdb;
         $table_name = $wpdb->prefix . 'roi_calculator';
         $rows = $wpdb->get_results("SELECT * FROM $table_name");
      ?>
         <div class="roi-calculator-settings">
            <h2>ROI Calculator User Data</h2>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
               <input type="hidden" name="action" value="export_to_csv">
               <input type="submit" value="Export to CSV" class="button button-primary">
            </form>
            <table class="widefat fixed" cellspacing="0">
               <thead>
                  <tr>
                     <th id="columnname" class="manage-column column-columnname" scope="col">Ime i prezime</th>
                     <th id="columnname" class="manage-column column-columnname" scope="col">Telefon</th>
                     <th id="columnname" class="manage-column column-columnname" scope="col">E-mail</th>
                     <th id="columnname" class="manage-column column-columnname" scope="col">Mjesečna potrošnja u EUR</th>
                     <th id="columnname" class="manage-column column-columnname" scope="col">Regija</th>
                     <th id="columnname" class="manage-column column-columnname" scope="col">Ponuda za solarnu elektranu po mjeri</th>
                     <th id="columnname" class="manage-column column-columnname" scope="col">Delete</th>
                  </tr>
               </thead>
               <tbody>
                  <?php foreach ($rows as $row) { ?>
                     <tr>
                        <td><?php echo $row->your_name; ?></td>
                        <td><?php echo $row->phone_number; ?></td>
                        <td><?php echo $row->email; ?></td>
                        <td><?php echo $row->monthly_spendings; ?></td>
                        <td><?php echo $row->region; ?></td>
                        <td><?php echo $row->specialised_offer ? 'Yes' : 'No'; ?></td>
                        <td>
                           <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                              <input type="hidden" name="action" value="delete_submission">
                              <input type="hidden" name="submission_id" value="<?php echo $row->id; ?>">
                              <input type="submit" value="Delete" class="button button-secondary">
                           </form>
                        </td>
                     </tr>
                  <?php } ?>
               </tbody>
            </table>
         </div>
      <?php
      }

      public function delete_submission()
      {
         if (isset($_POST['submission_id']) && current_user_can('manage_options')) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'roi_calculator';
            $wpdb->delete($table_name, array('id' => intval($_POST['submission_id'])));
         }
         wp_redirect(admin_url('admin.php?page=roi-calculator-user-data'));
         exit;
      }

      public function export_to_csv()
      {
         if (current_user_can('manage_options')) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'roi_calculator';
            $rows = $wpdb->get_results("SELECT id, your_name, phone_number, email, monthly_spendings, region, specialised_offer FROM $table_name", ARRAY_A);

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename=user_data.csv');

            $output = fopen('php://output', 'w');
            fputcsv($output, array('ID', 'Ime i prezime', 'Telefon', 'Email', 'Mjesečna potrošnja u EUR', 'Regija', 'Ponuda za solarnu elektranu po mjeri'));

            foreach ($rows as $row) {

               $csv_row = array(
                  $row['id'],
                  $row['your_name'],
                  $row['phone_number'],
                  $row['email'],
                  $row['monthly_spendings'],
                  $row['region'],
                  $row['specialised_offer'] ? 'Yes' : 'No'
               );
               fputcsv($output, $csv_row);
            }
            fclose($output);
            exit;
         }
      }


      public function settings_page()
      {
      ?>
         <div class="wrap roi-calculator-settings">
            <h2>ROI Calculator Settings</h2>

            <div class="info-shortcode">
               <h4>Calculator shortcode</h4>
               <p>[roi_calculator_form]</p>
            </div>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
               <?php wp_nonce_field('save_roi_calculator_settings_nonce'); ?>
               <input type="hidden" name="action" value="save_roi_calculator_settings">
               <table class="form-table">
                  <tr valign="top">
                     <th scope="row">Company E-mail</th>
                     <td><input type="email" name="company_email" value="<?php echo isset($this->options['company_email']) ? esc_attr($this->options['company_email']) : ''; ?>" required></td>
                  </tr>
                  <tr valign="top">
                     <th scope="row">Sender E-mail</th>
                     <td><input type="email" name="sender_email" value="<?php echo isset($this->options['sender_email']) ? esc_attr($this->options['sender_email']) : ''; ?>" required></td>
                  </tr>
               </table>
               <input type="submit" value="Save Changes" class="button button-primary">
            </form>
         </div>
<?php
      }

      public function save_roi_calculator_settings()
      {
         if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
         }

         check_admin_referer('save_roi_calculator_settings_nonce');

         if (isset($_POST['company_email'])) {
            $this->options['company_email'] = sanitize_email($_POST['company_email']);
         }

         if (isset($_POST['sender_email'])) {
            $this->options['sender_email'] = sanitize_email($_POST['sender_email']);
         }

         update_option('roi_calculator_options', $this->options);

         wp_redirect(add_query_arg(array('page' => 'roi-calculator-settings', 'updated' => 'true'), admin_url('admin.php')));
         exit;
      }

      public function add_settings_page()
      {
         add_options_page(
            'ROI Calculator Settings',
            'ROI Calculator',
            'manage_options',
            'roi-calculator-settings',
            array($this, 'settings_page')
         );
      }




      public function handle_form_submission()
      {
         if (!isset($_POST['your_name']) || !isset($_POST['phone_number']) || !isset($_POST['email']) || !isset($_POST['monthly_spendings']) || !isset($_POST['region']) || !isset($_POST['resultsAnnual']) || !isset($_POST['resultsPowerplant']) || !isset($_POST['resultsAnnualSavings']) || !isset($_POST['resultsReturn'])) {
            wp_send_json_error('Missing form data.');
            return;
         }

         global $wpdb;
         $table_name = $wpdb->prefix . 'roi_calculator';

         $your_name = sanitize_text_field($_POST['your_name']);
         $phone_number = sanitize_text_field($_POST['phone_number']);
         $email = sanitize_email($_POST['email']);
         $monthly_spendings = floatval($_POST['monthly_spendings']);
         $region = sanitize_text_field($_POST['region']);
         $specialised_offer = isset($_POST['specialised_offer']) ? 1 : 0;

         $data = array(
            'your_name' => $your_name,
            'phone_number' => $phone_number,
            'email' => $email,
            'monthly_spendings' => $monthly_spendings,
            'region' => $region,
            'specialised_offer' => $specialised_offer,
         );

         $result = $wpdb->insert($table_name, $data);

         if ($result === false) {
            wp_send_json_error('Failed to save data.');
         } else {
            // Get calculated results from AJAX request
            $resultsAnnual = floatval($_POST['resultsAnnual']);
            $resultsPowerplant = floatval($_POST['resultsPowerplant']);
            $resultsAnnualSavings = floatval($_POST['resultsAnnualSavings']);
            $resultsReturn = sanitize_text_field($_POST['resultsReturn']);

            // Send emails
            $this->send_emails($your_name, $email, $phone_number, $resultsAnnual, $resultsPowerplant, $resultsAnnualSavings, $resultsReturn, $specialised_offer);

            wp_send_json_success('Form submitted successfully.');
         }
      }


      private function send_emails($your_name, $email, $phone_number, $resultsAnnual, $resultsPowerplant, $resultsAnnualSavings, $resultsReturn, $specialised_offer)
      {
         $company_email = $this->options['company_email'];
         $sender_email = $this->options['sender_email'];

         $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ASAP solar <' . $sender_email . '>'
        );

         // Email to user
         $user_email_subject = 'ASAPsolar - izračun uštede uz solarnu elektranu';
         $user_email_body = "
    <p>Poštovani, </p>
    <p>Zahvaljujemo na Vašem vremenu i povjerenju. U nastavku donosimo okviran izračun uštede vašeg kućanstva nakon instalacije solarnih panela.</p>
    <p>Vaš godišnji trošak električne energije: {$resultsAnnual}</p>
    <p>Vaš godišnji trošak elektrićne energije nakon instalacije solarne elektrane: {$resultsPowerplant}</p>
    <p>Ukupna godišnja ušteda: {$resultsAnnualSavings}</p>
    <p>Povrat investicije: {$resultsReturn}</p>
    ";

         if ($specialised_offer) {
            $user_email_body .= "<p>Uskoro ćete primiti naš poziv u kojem ćemo vas zamoliti nekoliko dodatnih informacija kako bismo vam kreirali neobvezujuću ponudu za solarnu elektranu po mjeri.</p>";
         } else {
            $user_email_body .= "
        <p>Uložite već danas u besplatnu energiju budućnosti!</p> 
        <p>Uz podatke koje ste unijeli u kalkulator, molimo da nam na ovaj mail pošaljete dodatne podatke:!</p>
        <p>- točna adresa i poštanski broj</p>
        <p>- vrsta pokrova</p>
        <p>- fotografija krova</p>
        <p>U slučaju nejasnoća, slobodno nam se obratite telefonski na broj 095 852 1575.</p>
        ";
         }

         $user_email_body .= "<p>Sunčani pozdrav!</p>";

         // Email to company
         $company_email_subject = 'Novi unos u ROI kalkulator';
         $company_email_body = "
    <p>Ime i prezime: {$your_name}</p>
    <p>E-mail: {$email}</p>
    <p>Godišnji trošak električne energije: {$resultsAnnual}</p>
    <p>Godišnji trošak električne energije nakon instalacije solarne elektrane: {$resultsPowerplant}</p>
    <p>Godišnja ušteda: {$resultsAnnualSavings}</p>
    <p>Povrat investicije: {$resultsReturn}</p>
    <p>Telefon: {$phone_number}</p>
    ";

         if ($specialised_offer) {
            $company_email_body .= "<p>Ponuda za solarnu elektranu po mjeri - DA</p>";
         } else {
            $company_email_body .= "<p>Ponuda za solarnu elektranu po mjeri - NE</p>";
         }

         wp_mail($email, $user_email_subject, $user_email_body, $headers);
         wp_mail($company_email, $company_email_subject, $company_email_body, $headers);
      }


      public static function install()
      {
         global $wpdb;
         $table_name = $wpdb->prefix . 'roi_calculator';
         $charset_collate = $wpdb->get_charset_collate();

         $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            your_name tinytext NOT NULL,
            phone_number tinytext NOT NULL,
            email text NOT NULL,
            monthly_spendings float NOT NULL,
            region text NOT NULL,
            specialised_offer boolean DEFAULT FALSE,
            PRIMARY KEY (id)
         ) $charset_collate;";

         require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
         dbDelta($sql);

         add_option('roi_calculator_options', array(
            'company_email' => get_option('admin_email')
         ));
      }

      public static function uninstall()
      {
         global $wpdb;
         $table_name = $wpdb->prefix . 'roi_calculator';
         $sql = "DROP TABLE IF EXISTS $table_name;";
         $wpdb->query($sql);
         delete_option('roi_calculator_options');
      }
   }

   register_activation_hook(__FILE__, array('ROICalculatorPlugin', 'install'));
   register_uninstall_hook(__FILE__, array('ROICalculatorPlugin', 'uninstall'));

   $roi_calculator_plugin = new ROICalculatorPlugin();
}
?>
