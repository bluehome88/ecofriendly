<?php
class WC_Dropshipping_Admin {
	public $orders = null;
	public $product = null;
	public $csv = null;

	public function __construct() {
		require_once( 'class-wc-dropshipping-product.php' );
		require_once( 'class-wc-dropshipping-csv-import.php' );
		$this->product = new WC_Dropshipping_Product();
		$this->csv = new WC_Dropshipping_CSV_Import();

		// admin menu
		add_action('admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action('admin_enqueue_scripts', array( $this, 'my_admin_scripts' ) );
		// admin dropship supplier
		add_action( 'create_dropship_supplier', array( $this, 'create_term' ), 5, 3 );
		add_action( 'delete_dropship_supplier', array( $this, 'delete_term' ), 5 );  
		add_action( 'created_term', array( $this, 'save_category_fields' ), 10, 3 );
		add_action( 'edit_term', array( $this, 'save_category_fields' ), 10, 3 );
		add_action( 'dropship_supplier_add_form_fields', array( $this, 'add_category_fields' ) );
		add_action( 'dropship_supplier_edit_form_fields', array( $this, 'edit_category_fields' ), 10, 2 );
		add_action( 'wp_ajax_CSV_upload_form',array($this,'ajax_save_category_fields'));
		add_filter( 'manage_edit-dropship_supplier_columns', array($this, 'manage_columns'), 10, 1 );
		add_action( 'manage_dropship_supplier_custom_column', array($this, 'column_content'), 10 ,3);
		// woocommerce settings tab
		//add_filter( 'woocommerce_settings_tabs_array',array($this,'add_settings_tab'),50);
		//add_action( 'woocommerce_settings_tabs_dropship_manager', array($this,'dropship_manager_settings_tab') );
		//add_action( 'woocommerce_update_options_dropship_manager', array($this,'update_settings') );
		add_filter( 'woocommerce_get_sections_email',array($this,'add_settings_tab'),50);
		add_action( 'woocommerce_settings_email', array($this,'dropship_manager_settings_tab'),10,1);
		add_action( 'woocommerce_settings_save_email', array($this,'update_settings') );

		/*add_action('init', array($this,'cloneUserRole'));*/

		add_action( 'admin_init', array($this,'my_remove_menu_pages'));

		add_action('admin_menu', array($this,'dropshipper_order_list_page'));
		add_action('wp_ajax_dropshipper_shipping_info_edited',  array($this,'dropshipper_shipping_info_edited_callback'));
		

	}
	

	public function admin_styles() {
		$base_name = explode('/',plugin_basename(__FILE__));
		wp_enqueue_script( 'wc_dropship_manager_scripts', plugins_url().'/'.$base_name[0].'/assets/js/wc_dropship_manager.js', array( 'jquery', 'jquery-blockui', 'jquery-ui-sortable', 'jquery-ui-widget', 'jquery-ui-core', 'jquery-tiptip' ));
		wp_enqueue_script( 'jquery-tiptip', plugins_url().'/woocommerce/assets/js/jquery-tiptip/jquery.tipTip.min.js', array( 'jquery' ), true );
		wp_enqueue_style( 'woocommerce_admin_styles', plugins_url().'/woocommerce/assets/css/admin.css', array() );

	}

	public function my_admin_scripts() {
		$base_name = explode('/',plugin_basename(__FILE__));
		//wp_enqueue_script('jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.11.0/jquery-ui.min.js');
		wp_enqueue_script('jquery-ui', plugins_url().'/'.$base_name[0]. '/assets/js/jquery-ui.min.js', array('jquery'),
			'1.11.0', true);
   		wp_enqueue_script( 'my-great-script', plugins_url().'/'.$base_name[0]. '/assets/js/myscript.js', array( 'jquery' ), '1.0.0', true );
	}

	
	public function dropshipper_shipping_info_edited_callback() {
		global $wpdb;
		if(isset($_POST['id']) && isset($_POST['info']) ){
			$id = intval( $_POST['id'] );
			$info = $_POST['info'];
			update_post_meta($_POST['id'], 'dropshipper_shipping_info_'.get_current_user_id(), $info);
			echo 'true';
		}
		else{
			echo 'false';
		}
		die(); // this is required to return a proper result
	}

	
	public function manage_columns($cols) {
		unset($cols['description']);
		unset($cols['slug']);
		unset($cols['posts']);
		//$cols['account_number'] = 'Account Number';
		$cols['order_email_addresses'] = 'Email Addresses';
		$cols['inventory'] = '';
		$cols['posts'] = 'Count';
		return $cols;
	}

	public function column_content($blank, $column_name, $term_id) {
		$ds = wc_dropshipping_get_dropship_supplier( intval( $term_id ) );
		switch($column_name)
		{
			case 'account_number':
				echo $ds['account_number'];
			break;
			case 'order_email_addresses':
				echo $ds['order_email_addresses'];
			break;
			case 'inventory':
				echo '<p><a title="Inventory Upload for '.$ds['name'].'" href="'.admin_url( 'admin-ajax.php' ).'?action=get_CSV_upload_form&width=600&height=350&term_id='.$term_id.'" class="thickbox button-primary csvwindow" term_id="'.$term_id.'" >Inventory CSV</a></p>';
			break;
		}
	}

	public function get_dropship_supplier_fields() {
		$meta = array(
			'account_number' => '',
			'order_email_addresses' => '',
			'csv_delimiter' => ',',
			'csv_column_indicator' => '',
			'csv_column_sku' => '',
			'csv_column_qty' => '',
			'csv_type' => '',
			'csv_quantity' => '',
			'csv_indicator_instock' => '',
		);
		return $meta;
	}

	public function add_category_fields() {
		$meta = $this->get_dropship_supplier_fields();
		$this->display_add_form_fields($meta);
	}

	public function edit_category_fields( $term, $taxonomy ) {
		$meta = get_term_meta( $term->term_id, 'meta',true );
		$this->display_edit_form_fields($meta);
	}

	public function display_add_form_fields($data) {
		add_thickbox();
		echo '<div class="form-field term-account_number-wrap">
				<label for="account_number" >Account #</label>
				<input type="text" size="40" name="account_number" value="'.$data['account_number'].'" />
				<p>Your store\'s account number with this dropshipper. Leave blank if you don\'t have an account number</p>
			</div>
			<div class="form-field term-order_email_addresses-wrap">
				<label for="order_email_addresses" >Email Addresses</label>
				<input type="text" size="40" name="order_email_addresses" value="'.$data['order_email_addresses'].'" required />
				<p>When a customer purchases product from you the dropshipper is sent an email notification. List the email addresses that should be notified when a new order is placed for this dropshipper.<p>
			</div>';

	}

	public function display_edit_form_fields($data) {
		$csv_types = array('quantity'=>'Quantity on Hand','indicator'=>'Indicator');
		echo	'<tr class="term-account_number-wrap">
						<th><label for="account_number" >Account #</label></th>
						<td><input type="text" size="40" name="account_number" value="'.$data['account_number'].'" />
						<p>Your store\'s account number with this dropshipper. Leave blank if you don\'t have an account number</p></td>
					</tr>
					<tr  class="term-order_email_addresses-wrap">
						<th><label for="order_email_addresses" >Email Addresses</label></th>
						<td><input type="text" size="40" name="order_email_addresses" value="'.$data['order_email_addresses'].'" required />
						<p>When a customer purchases product from you the dropshipper is sent an email notification. List the email addresses that should be notified when a new order is placed for this dropshipper.<p></td>
					</tr>
				</table>
				<h3>Supplier Inventory CSV Import Settings</h3>
				<p>(If you do not receive inventory statuses by CSV from this supplier then just leave these settings blank)</p>
				<table class="form-table">
					<tr  class="term-csv_delimiter-wrap">
						<th><label for="csv_delimiter" >CSV column delimiter</label></th>
						<td><input type="text" size="2" name="csv_delimiter" value="'.$data['csv_delimiter'].'" />
						<p>Please indicate what character is used to separate fields in the CSV. Normally this is a comma</p></td>
					</tr>
					<tr  class=" term-column_sku-wrap">
						<th><label for="csv_column_sku" >CSV sku column #</label></th>
						<td><input type="text" size="2" name="csv_column_sku" value="'.$data['csv_column_sku'].'" />
						<p>Please indicate which column in the CSV is the product SKU. This should be the manufacturers SKU. Dropship Manager Pro will append the SKU code for this suppler automatically when you upload</p></td>
					</tr>
					<tr  class=" term-csv_type-wrap">
						<th><label for="csv_type">CSV type</label></th>
						<td><select name="csv_type" id="csv_type" >';
								foreach($csv_types as $csv_type=>$description)
								{
									$selected = '';
									if ($data['csv_type'] === $csv_type) {$selected = 'selected';}
									echo '<option value="'.$csv_type.'" '.$selected.'>'.$description.'</option>';
								}
		echo '</select>
						<p>Please indicate how the CSV data should be read. <Br />Quantity on hand - this means that the CSV contains a column showing the suppliers remaining stock</p></td>
					</tr>
					<tr  class="csv_quantity csv_types">
						<th><label for="csv_column_qty" >CSV quantity column #</label></th>
						<td><input type="text" size="2" name="csv_column_qty" value="'.$data['csv_column_qty'].'" />
						<p>If you are using a CSV to update in-stock status please indicate which column in the csv is the inventory quantity remaining</p></td>
					</tr>
					<tr  class="csv_indicator csv_types">
						<th><label for="csv_column_indicator" >CSV Indicator column #</label></th>
						<td><input type="text" size="2" name="csv_column_indicator" value="'.$data['csv_column_indicator'].'" />
						<p>If you are using a CSV to update in-stock status please indicate which column in the csv indicates the in-stock status</p></td>
					</tr>
					<tr  class="csv_indicator csv_types">
						<th><label for="csv_indicator_instock" >CSV Indicator In-stock value</label></th>
						<td><input type="text" size="2" name="csv_indicator_instock" value="'.$data['csv_indicator_instock'].'" />
						<p>If you are using a CSV to update in-stock status please indicate which column in the csv indicates the in-stock value</p></td>
					</tr>';
	}

	/*public function cloneUserRole()	{
		 global $wp_roles;
		 if (!isset($wp_roles))
		 $wp_roles = new WP_Roles();
		 $adm = $wp_roles->get_role('subscriber');
		 // Adding a new role with all admin caps.
		 $wp_roles->add_role('dropshipper', 'Dropshipper', $adm->capabilities);
	}*/

	

	public function my_remove_menu_pages() {
		global $user_ID;
		if ( current_user_can( 'dropshipper' ) ) {
			//remove_menu_page('edit-comments.php');
			//remove_menu_page('tools.php');
			//remove_menu_page( 'edit.php' ); 
		}
	}


	public function dropshipper_order_list_page(){
		global $user_ID;
		if ( current_user_can( 'dropshipper' ) ) {
		  $page_title = 'Order Lists';
		  $menu_title = 'Order List';
		  $capability = 'dropshipper';
		  $menu_slug  = 'dropshipper-order-list';
		  $function   = 'dropshipper_order_list';
		  $icon_url   = 'dashicons-media-code';
		  add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function );
		}
	}

	public function save_category_fields( $term_id, $tt_id, $taxonomy ) {

		// check for uploaded csv
		if(count($_FILES) > 0  && $_FILES['csv_file']['error'] == 0) {
			// we are saving an inventory form submit
			do_action('wc_dropship_manager_parse_csv');
		} else {
			
			if($taxonomy == 'dropship_supplier'){
				// do update
				$meta = $this->get_dropship_supplier_fields();
				foreach ($meta as $key => $val) {
					if (isset($_POST[$key])) $meta[$key] = $_POST[$key];
				}
				
				$cterm = update_term_meta( $term_id, 'meta', $meta );
			}

			/*Create New User When Create Term*/
			if($cterm != '' && $taxonomy == 'dropship_supplier'){

				$username = @$_POST['tag-name'];
				$email = @$_POST['order_email_addresses'];
				/*$password = wp_generate_password();*/
	
				$user_id = username_exists( $username );
				
				update_user_meta($user_id, 'supplier_id', $term_id);
				
				if ( !empty($username) && !empty($email) && !$user_id && email_exists($email) == false ) {

					$random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
					$user_id = wp_create_user( $username, $random_password, $email );
					update_user_meta($user_id, 'supplier_id', $term_id);
					
					$user_id_role = new WP_User($user_id);
					$user_id_role->set_role('dropshipper');
					
					/*Send User Password*/
					
			        $to = $email;			       
					$subject = 'Registration Detail';
					$from = get_option( 'admin_email' );
					 
					// To send HTML mail, the Content-type header must be set
					$headers  = 'MIME-Version: 1.0' . "\r\n";
					$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
					 
					// Create email headers
					$headers .= 'From: '.$from."\r\n".
					    'Reply-To: '.$from."\r\n" .
					    'X-Mailer: PHP/' . phpversion();
					 
					// Compose a simple HTML email message
					$message = '<html><body>';
					$message .= '<h1 style="color:#f40;">Hi '.$user_id_role->display_name.'!</h1>';
					$message .= '<p style="color:#080;font-size:15px;">Thanks For Registration</p>';
					$message .= '<p>Your Email:&nbsp;'. $email .'</p>';
					$message .= '<p>Your Password:&nbsp;'. $random_password .'</p>';
					$message .= '<p>Change Your Password Once you login</p>';
					$message .= '</body></html>';
					wp_mail($to, $subject, $message, $headers);
					//mail($to, $subject, $message, $headers);
				
				} else {
					$random_password = __('User already exists.  Password inherited.');
				}
				
	        }
		}
	}

	public function ajax_save_category_fields() {
		$this->save_category_fields( $_POST['term_id'], '', $_POST['taxonomy'] );
		if (defined('DOING_AJAX') && DOING_AJAX) {
			wp_die();
		}
	}

	/* Order term when created (put in position 0). */
	public function create_term( $term_id, $tt_id = '', $taxonomy = '' ) {
		if ( $taxonomy != 'dropship_supplier' && ! taxonomy_is_product_attribute( $taxonomy ) )
			return;
		$meta_name = taxonomy_is_product_attribute( $taxonomy ) ? 'order_' . esc_attr( $taxonomy ) : 'order';
		update_term_meta( $term_id, $meta_name, 0 );
	}

	/* When a term is deleted, delete its meta. */
	public function delete_term( $term_id ) {
		$term_id = (int) $term_id;
		update_term_meta( $term_id, $meta_name, 0 );
		if ( ! $term_id )
				return;
			
		global $wpdb; 
		$wpdb->query( "DELETE FROM {$wpdb->termmeta} WHERE `term_id` = " . $term_id );
		
	}

	/* Admin Settings Area */
	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs['dropship_manager'] = __( 'Dropshipping Notifications', 'woocommerce-dropshipping' );
		return $settings_tabs;
	}

	public function dropship_manager_settings_tab() {
		global $current_section;
		if ($current_section == 'dropship_manager')
		{
			$this->display_settings();
		}
	}

	public function update_settings() {
		global $current_section;
		if ($current_section == 'dropship_manager') {
			$options = get_option( 'wc_dropship_manager' );
			foreach ($_POST as $key => $opt) {
				if ($key != 'submit') $options[$key] = $_POST[$key];
			}
            
			if(isset($_POST['csv_inmail'])){
				$options['csv_inmail'] = '1';
			} else {
				$options['csv_inmail'] = '0';
			}
            
            if(isset($_POST['billing_phone'])){
				$options['billing_phone'] = '1';
			} else {
				$options['billing_phone'] = '0';
			}
		
			if(isset($_POST['full_information'])){
				$options['full_information'] = '1';
			} else {
				$options['full_information'] = '0';
			}

			if(isset($_POST['show_logo'])){
				$options['show_logo'] = '1';
			} else {
				$options['show_logo'] = '0';
			}
			if(isset($_POST['order_date'])){
				$options['order_date'] = '1';
			} else {
				$options['order_date'] = '0';
			}
			
			if(isset($_POST['smtp_check'])){
				$options['smtp_check'] = '1';
			} else {
				$options['smtp_check'] = '0';
			}

			if(isset($_POST['from_name'])){
				$options['from_name'] = $_POST['from_name'];
			} else {
				$options['from_name'] = '';
			}

			if(isset($_POST['from_email'])){
				$options['from_email'] = $_POST['from_email'];
			} else {
				$options['from_email'] = '';
			}
			
			if(isset($_POST['product_price'])){
				$options['product_price'] = '1';
			} else {
				$options['product_price'] = '0';
			}
			if(isset($_POST['shipping'])){
				$options['shipping'] = '1';
			} else {
				$options['shipping'] = '0';
			}
			if(isset($_POST['payment_method'])){
				$options['payment_method'] = '1';
			} else {
				$options['payment_method'] = '0';
			}
			if(isset($_POST['billing_address'])){
				$options['billing_address'] = '1';
			} else {
				$options['billing_address'] = '0';
			}
			if(isset($_POST['shipping_address'])){
				$options['shipping_address'] = '1';
			} else {
				$options['shipping_address'] = '0';
			}
			if(isset($_POST['product_image'])){
				$options['product_image'] = '1';
			} else {
				$options['product_image'] = '0';
			}
			if(isset($_POST['store_name'])){
				$options['store_name'] = '1';
			} else {
				$options['store_name'] = '0';
			}
			if(isset($_POST['store_address'])){
				$options['store_address'] = '1';
			} else {
				$options['store_address'] = '0';
			}
			if(isset($_POST['complete_email'])){
				$options['complete_email'] = '1';
			} else {
				$options['complete_email'] = '0';
			}
			if(isset($_POST['order_complete_link'])){
				$options['order_complete_link'] = '1';
			} else {
				$options['order_complete_link'] = '0';
			}
			
			update_option( 'wc_dropship_manager', $options );
		}
	}

	public function display_settings() {
		// Tab to update options
		$options = get_option( 'wc_dropship_manager' );
		$csvcheck = $options['csv_inmail'];
		$full_information = $options['full_information'];
		$show_logo = $options['show_logo'];
		$order_date = $options['order_date'];
		$smtp_check = $options['smtp_check'];
		$from_name = $options['from_name'];
		$from_email = $options['from_email'];
		$product_price = $options['product_price'];
		$shipping = $options['shipping'];
		$payment_method = $options['payment_method'];
		$billing_address = $options['billing_address'];
		$billing_phone = $options['billing_phone'];
		$shipping_address = $options['shipping_address'];
		$product_image = $options['product_image'];
		$store_name = $options['store_name'];
		$store_address = $options['store_address'];
		$complete_email = $options['complete_email'];
		$order_complete_link = $options['order_complete_link'];
		

		if($csvcheck == '1'){
			$csvInMail = ' checked="checked" ';
		} else {
			$csvInMail = ' ';
		}

		if($full_information == '1'){
			$checkfull = ' checked="checked" ';
		} else {
			$checkfull = ' ';
		}

		if($show_logo == '1'){
			$logoshow = ' checked="checked" ';
		} else {
			$logoshow = ' ';
		}

		if($order_date == '1'){
			$date_order = ' checked="checked" ';
		} else {
			$date_order = ' ';
		}
		
		if($smtp_check == '1'){
			$check_smtp = ' checked="checked" ';
		} else {
			$check_smtp = ' ';
		}
		
		if($product_price == '1'){
			$price_product = ' checked="checked" ';
		} else {
			$price_product = ' ';
		}
		if($shipping == '1'){
			$product_shipping = ' checked="checked" ';
		} else {
			$product_shipping = ' ';
		}
		if($payment_method == '1'){
			$method_payment = ' checked="checked" ';
		} else {
			$method_payment = ' ';
		}
		if($billing_address == '1'){
			$address_billing = ' checked="checked" ';
		} else {
			$address_billing = ' ';
		}

        if($billing_phone == '1'){
			$phone_billing = ' checked="checked" ';
		} else {
			$phone_billing = ' ';
		}
        

		if($shipping_address == '1'){
			$address_shipping = ' checked="checked" ';
		} else {
			$address_shipping = ' ';
		}
		if($product_image == '1'){
			$image_product = ' checked="checked" ';
		} else {
			$image_product = ' ';
		}
		if($store_name == '1'){
			$name_store = ' checked="checked" ';
		} else {
			$name_store = ' ';
		}
		if($store_address == '1'){
			$address_store = ' checked="checked" ';
		} else {
			$address_store = ' ';
		}
		if($complete_email == '1'){
			$email_complete = ' checked="checked" ';
		} else {
			$email_complete = ' ';
		}
		if($order_complete_link == '1'){
			$link_complete_order = ' checked="checked" ';
		} else {
			$link_complete_order = ' ';
		}


		$woocommerce_url = plugins_url().'/woocommerce/';
		echo '<h3>Email Notifications</h3>
			<p>When an order switches to processing status, emails are sent to each supplier to notify them to ship the products. These options control the supplier email notification</p>
			<table>
				<tr>
					<td><label for="email_order_note">Email order note:</label></td>
					<td><img class="help_tip" data-tip="This note will appear on the email a supplier will receive with your order notification" src="'.$woocommerce_url.'assets/images/help.png" height="16" width="16"></td>
					<td><textarea name="email_order_note" cols="45" >'.$options['email_order_note'].'</textarea></td>
				</tr>
			</table>';
		echo '<h3>Packing slip</h3>
			<p>These options control the information on the generated packing slip that is sent to your supplier. <br />Talk to your supplier to make sure they print out and include this packing slip with each order so that your customer will see it</p>
			<table>
				<tr>
					<p>NOTE: For best results, keep logo dimensions within 200x60 px</p>
					<td><label for="packing_slip_url_to_logo" >Url to logo:</label></td>
					<td><img class="help_tip" data-tip="This logo will appear on the PDF packingslip" src="'.$woocommerce_url.'assets/images/help.png" height="16" width="16"></td>
					<td><input name="packing_slip_url_to_logo" value="'.$options['packing_slip_url_to_logo'].'" size="100" /></td>
				</tr>
				<tr>
					<td><label for="packing_slip_url_to_logo_width" >Logo Width:</label></td>
					<td><img class="help_tip" data-tip="Custom width of the logo in the PDF packingslip" src="'.$woocommerce_url.'assets/images/help.png" height="16" width="16"></td>
					<td><input name="packing_slip_url_to_logo_width" value="'.$options['packing_slip_url_to_logo_width'].'" size="5" /></td>
				</tr>
				<tr>
					<td><label for="packing_slip_company_name" >Company Name:</label></td>
					<td><img class="help_tip" data-tip="This address will appear on the PDF packingslip" src="'.$woocommerce_url.'assets/images/help.png" height="16" width="16"></td>
					<td><input name="packing_slip_company_name" value="'.$options['packing_slip_company_name'].'" size="100" /></td>
				</tr>
				<tr>
					<td><label for="packing_slip_address" >Address:</label></td>
					<td><img class="help_tip" data-tip="This address will appear on the PDF packingslip" src="'.$woocommerce_url.'assets/images/help.png" height="16" width="16"></td>
					<td><input name="packing_slip_address" value="'.$options['packing_slip_address'].'" size="100" /></td>
				</tr>
				<tr>
					<td><label for="packing_slip_customer_service_email" >Customer service email:</label></td>
					<td><img class="help_tip" data-tip="This email address will appear on the PDF packingslip" src="'.$woocommerce_url.'assets/images/help.png" height="16" width="16"></td>
					<td><input name="packing_slip_customer_service_email" value="'.$options['packing_slip_customer_service_email'].'" size="50" /></td>
				</tr>
				<tr>
					<td><label for="packing_slip_customer_service_phone">Customer service phone:</label></td>
					<td><img class="help_tip"  data-tip="This phone number will appear on the PDF packingslip" src="'.$woocommerce_url.'assets/images/help.png" height="16" width="16"></td>
					<td><input name="packing_slip_customer_service_phone" value="'.$options['packing_slip_customer_service_phone'].'" size="50" /></td>
				</tr><tr>
					<td ><label for="packing_slip_thankyou">Thank you mesage:</label></td>
					<td><img class="help_tip" data-tip="This message will appear on the PDF packingslip" src="'.$woocommerce_url.'assets/images/help.png" height="16" width="16"></td>
					<td><textarea name="packing_slip_thankyou" cols="45" >'.$options['packing_slip_thankyou'].'</textarea></td>
				</tr>
			</table>';
		echo '<h3>Inventory Stock Status Update</h3>
			<p>These options control how the importing of supplier inventory spreadsheets</p>
			<table>
				<tr>
					<td><label for="inventory_pad">Inventory pad:</label></td>
					<td><img class="help_tip" data-tip="If inventory stock falls below this number it will be considered out of stock. <br>Set to zero if you want to chance that your supplier will not have the item in stock by the time you submit your order." src="'.$woocommerce_url.'assets/images/help.png" height="16" width="16"></td>
					<td><input name="inventory_pad" value="'.$options['inventory_pad'].'" size="3" /></td>
				</tr>
				<!--<tr>
					<td valign="top"><label for="url_product_feed">Url to product feed:</label></td>
					<td><img class="help_tip" data-tip="After updating the in-stock/out of stock status this url will be called to regenerate your product feed. <br />(Just leave blank if you don\'t have a product feed)" src="'.$woocommerce_url.'assets/images/help.png" height="16" width="16"></td>
					<td>
						<input name="url_product_feed" value="'.$options['url_product_feed'].'" size="100" />
					</td>
				</tr>-->
			</table>';
		echo '<h3>Send CSV in E-mail </h3>
			<p>Following option controls, if you want to send a CSV file, containing order details, as an attachment to the order email which is sent to supplier</p>
			<table>
				<tr>
					<td><label for="csv_inmail">Send CSV to Supplier:</label></td>
					<td><input name="csv_inmail" type="checkbox" '.$csvInMail.' /></td>
				</tr>
			</table>';
		echo'<h3>Send your full order information</h3>
			<table>
				<tr>
					<td><label for="full_information"><b>Do you want to also send your full order information as a PDF to your supplier to use as a packing slip?:</b></label></td>
					<td><input name="full_information" class="fullinfo" type="checkbox" '.$checkfull.' /></td>
				</tr>
			</table>';
		echo'<div class="slidesection">
			<p></p>
			<table>
				<tr>
					<td><label for="show_logo">Show logo in the header:</label></td>
					<td><input name="show_logo" type="checkbox" '.$logoshow.' /></td>
				</tr>
			</table>';
		echo'<p></p>
			<table>
				<tr>
					<td><label for="order_date">Show order date beside order number:</label></td>
					<td><input name="order_date" type="checkbox" '.$date_order.' /></td>
				</tr>
			</table>';
		echo'<p></p>
			<table>
				<tr>
					<td><label for="product_price">Show product prices:</label></td>
					<td><input name="product_price" type="checkbox" '.$price_product.' /></td>
				</tr>
			</table>';
		echo'<p></p>
			<table>
				<tr>
					<td><label for="shipping">Show shipping:</label></td>
					<td><input name="shipping" type="checkbox" '.$product_shipping.' /></td>
				</tr>
			</table>';

       echo'<p></p>
			<table>
				<tr>
					<td><label for="shipping">Show user phone number to supplier:</label></td>
					<td><input name="billing_phone" type="checkbox" '.$phone_billing.' /></td>
				</tr>
			</table>';

		echo'<p></p>
			<table>
				<tr>
					<td><label for="payment_method">Show payment method:</label></td>
					<td><input name="payment_method" type="checkbox" '.$method_payment.' /></td>
				</tr>
			</table>';
		echo'<p></p>
			<table>
				<tr>
					<td><label for="billing_address">Show billing address at the bottom:</label></td>
					<td><input name="billing_address" type="checkbox" '.$address_billing.' /></td>
				</tr>
			</table>';
		echo'<p></p>
			<table>
				<tr>
					<td><label for="shipping_address">Show shipping address at the bottom:</label></td>
					<td><input name="shipping_address" type="checkbox" '.$address_shipping.' /></td>
				</tr>
			</table>';			
		echo'<p></p>
			<table>
				<tr>
					<td><label for="product_image">Show product thumbnail image:</label></td>
					<td><input name="product_image" type="checkbox" '.$image_product.' /></td>
				</tr>
			</table>';
		echo'<p></p>
			<table>
				<tr>
					<td><label for="store_name">Add store name in the CSV filename:</label></td>
					<td><input name="store_name" type="checkbox" '.$name_store.' /></td>
				</tr>
			</table>';		
		echo'<p></p>
			<table>
				<tr>
					<td><label for="store_address">Add website address of the store in the CSV filename:</label></td>
					<td><input name="store_address" type="checkbox" '.$address_store.' /></td>
				</tr>
			</table>';
		echo'<p></p>
			<table>
				<tr>
					<td><label for="complete_email">Send an addition email to supplier when order completed:</label></td>
					<td><input name="complete_email" type="checkbox" '.$email_complete.' /></td>
				</tr>
			</table>';
		echo'<p></p>
			<table>
				<tr>
					<td><label for="order_complete_link">Allow Dropshippers to mark their orders as shipped by clicking a link on the email, without logging in:</label></td>
					<td><input name="order_complete_link" type="checkbox" '.$link_complete_order.' /></td>
				</tr>
			</table>';
		
		echo '<h3>SMTP Optional</h3>
			<p></p>
			<table>
				<tr>
					<td><label for="smtp_check">Check this option if you are using SMTP mail function in your website:</label></td>
					<td><input name="smtp_check" type="checkbox" '.$check_smtp.' /></td>
				</tr>
			</table>';
			
			echo '<h2>Email sender information (if empty then it will pick these settings from woocommerce default settings)</h2>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="from_name">"From" name <img class="help_tip"  data-tip="This option will override default functionality of woocommerce" src="'.$woocommerce_url.'assets/images/help.png" height="16" width="16"></label>
						</th>
						<td class="forminp forminp-text">
							<input name="from_name" id="from_name" type="text" style="min-width:300px;" value="'.$from_name.'" class="" placeholder="">
						</td>
					</tr>
					<tr valign="top">
						<th scope="row" class="titledesc">
							<label for="from_email">"From" address <img class="help_tip"  data-tip="This option will override default functionality of woocommerce" src="'.$woocommerce_url.'assets/images/help.png" height="16" width="16"></label>
						</th>
						<td class="forminp forminp-email">
							<input name="from_email" id="from_email" type="email" style="min-width:300px;" value="'.$from_email.'" class="" placeholder="" multiple="multiple">
						</td>
					</tr>
				</tbody>
			</table>
		</div>';

	}
}
