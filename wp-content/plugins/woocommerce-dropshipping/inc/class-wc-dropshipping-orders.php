<?php
class WC_Dropshipping_Orders {
	public function __construct() {
		$this->init();
	}

	public function init() {
		// order processing
		add_filter('wc_dropship_manager_send_order_email_html',array($this,'send_order_email_html'));
		add_filter('wc_dropship_manager_send_order_attachments',array($this,'send_order_attach_packingslip'),10,3);
		add_action('woocommerce_order_actions',array( $this,'add_order_meta_box_order_processing'));
		add_action('woocommerce_order_status_processing',array($this,'order_processing'));
		add_action('woocommerce_order_status_completed',array($this,'order_complete'));
		add_action('woocommerce_order_action_resend_dropship_supplier_notifications',array($this,'order_processing'));
		add_action('wc_dropship_manager_send_order',array($this,'send_order'),10, 2);
		add_filter( 'wp_mail_content_type',array($this,'wpse27856_set_content_type') );
	}

	function wpse27856_set_content_type(){
	    return "text/html";
	}	

	public function add_order_meta_box_order_processing( $actions ) {
		$actions['resend_dropship_supplier_notifications'] = 'Resend Dropship Supplier Notifications';
		return $actions;
	}

	
	public function order_complete( $order_id ) {
		$dropship_data = get_option( 'wc_dropship_manager' );
		$complete_email = $dropship_data['complete_email'];
		$fullinfo = $dropship_data['full_information'];

		if($fullinfo == '1' && $complete_email == '1' ) {
			$order = new WC_Order( $order_id ); // load the order from woocommerce
			$this->notify_warehouse($order); // notify the warehouse to ship the order
		}
	}
	
	/* Notify Suppliers */
	// perform all tasks that happen once an order is set to processing
	public function order_processing( $order_id ) {
		$order = new WC_Order( $order_id ); // load the order from woocommerce
		$this->notify_warehouse($order); // notify the warehouse to ship the order
	}

	// parse the order, build pdfs, and send orders to the correct suppliers
	public function notify_warehouse( $order ) {
		$order_info = $this->get_order_info($order);
		$supplier_codes = $order_info['suppliers'];
		// for each supplier code, loop and send email with product info
		foreach($supplier_codes as $code => $supplier_info) {

			do_action('wc_dropship_manager_send_order',$order_info,$supplier_info);
		}
	}

	public function get_order_shipping_info($order) {
		$keys = explode(',','shipping_first_name,shipping_last_name,shipping_address_1,shipping_address_2,shipping_city,shipping_state,shipping_postcode,shipping_country,billing_phone,shipping_company');
		$info =  array();
        $info['name'] = $order->get_shipping_first_name().' '.$order->get_shipping_last_name();
        $info['phone'] = $this->formatPhone($order->get_billing_phone());
		$info['shipping_method'] = $order->get_shipping_method();
		foreach($keys as $key) {
			if ( is_callable( array( $order, "get_{$key}" ) ) ) {
			$info[$key] = $order->{'get_'.$key}();
			}else{
				$info[$key] = '';
			}
		}
		return $info;
	}

	/**
	 * @param $order WC_Order
	 *
	 * @return array
	 */
	public function get_order_billing_info($order) {
		$keys = explode(',','billing_first_name,billing_last_name,billing_address_1,billing_address_2,billing_city,billing_state,billing_postcode,billing_country,billing_phone,billing_email,billing_company');
		$info =  array();
                $info['name'] = $order->get_billing_first_name().' '.$order->get_billing_last_name();
                $info['phone'] = $this->formatPhone($order->get_billing_phone());
		foreach($keys as $key) {
			if ( is_callable( array( $order, "get_{$key}" ) ) ) {
				$info[$key] = $order->{'get_'.$key}();
			}else{
				$info[$key] = '';
			}
		}
		return $info;
	}

	public function get_order_product_info($item,$product) {
		

		global  $woocommerce;   
		$info = array();
		$info['sku'] = $product->get_sku();
        $info['qty'] = $item['qty'];
		$info['name'] = $item['name'];
		$product = wc_get_product( $product->get_id() );
		$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ) );
		$info['imgurl'] = $thumbnail['0'];


		$currency_symbol = get_woocommerce_currency_symbol();
		


		/*$item_data = $item->get_data();
		$info['price'] = '<span class="currency">'. $currency_symbol .'</span>'. $item_data['subtotal'];*/
		$info['price'] = '<span class="currency">'. $currency_symbol .'</span>'. $item->get_subtotal();

		

		$product_attributes = maybe_unserialize( get_post_meta( $product->get_id(), '_product_attributes', true ) );
		$info['product_attribute_keys'] = array();
		if(is_array($product_attributes)) {
			$info['product_attribute_keys'] = array_keys($product_attributes);
			foreach($product_attributes as $key=>$data) {
				$info[$key] = $data['value'];
			}
		}

		// Product Variations
		$info['variation_data'] = [];
		$info['variation_labels'] = [];
		$info['variation_name'] = [];
		$variation_attributes = [];
		if($product->is_type('variable'))
		{
			$info['variation_data'] = $product->get_variation_attributes();
			$variation = wc_get_product($item['variation_id']);
			$variation_att = $variation->get_variation_attributes();
			foreach ($variation_att as $key => $value) {
				$variation_attributes[] =$key.':'. $value;
			}
			$var_lab = str_replace("attribute_pa_"," ",$variation_attributes);
			$variation_label = implode(',',$var_lab);
			$info['variation_labels'] = $variation_label;
			$v_name = explode('- ', $info['name']);
			$info['variation_name'] = $v_name[0];
			//print_r($info['variation_name'] );
		}
		else
		{
			$info['variation_name'] =  $info['name'];
			$info['variation_labels'] ='';
			//print_r($info['variation_name'] );
		}
		
		// Product Add-Ons Plugin
		//$info['order_item_meta'] = [];
		$info['order_item_meta'] = $item->get_formatted_meta_data();
        if(function_exists('get_product_addons')) {
			//$info['order_item_meta'] = $item->get_formatted_meta_data();
			$info['product_addons'] = get_product_addons($product);
			/*for($i=0;$i<count($info['product_addons']);$i++)
			{
				$addon = $info['product_addons'][$i];
				$addon['key'] = $this->get_addon_key_string($addon);
				$info['product_addons'][$i] = $addon;
			}*/
			foreach($info['order_item_meta'] as $key=>$item_meta)
            {
				$info['order_item_meta'][$key]->display_label = $this->get_addon_display_label($info['order_item_meta'][$key]);
			}
		}
		
		
		return $info;
	}

	private function get_addon_display_label($item_meta)
	{
		$d = $item_meta->display_key;
		// remove the price from the meta display name
		return trim(preg_replace('/\(\$\d.*\)/','',$d));
	}

	
	public function get_order_info($order) {
		// gather some of the basic order info
		$order_info = array();
		/*$order_info['id'] = $order->get_order_number();*/
		$order_info['id'] = $order->get_id();
		$order_info['number'] = $order->get_order_number();
		$order_info['options'] = get_option( 'wc_dropship_manager' );
		$order_info['shipping_info'] = $this->get_order_shipping_info($order);
		$order_info['billing_info'] = $this->get_order_billing_info($order);
		$order_info['order'] = $order;
		// for each item determine what products go to what suppliers.
		// Build product/supplier lists so we can send send out our order emails
		$order_info['suppliers'] = array();
		$items = $order->get_items();
		

		if ( count( $items ) > 0 ) {
			foreach( $items as $item_id => $item ) {

				$sup_name = get_post_meta($item['product_id'], 'supplier', true);
				if($sup_name != "" || !empty($sup_name) || !is_null($sup_name)){
					wc_update_order_item_meta($item_id,'supplier',$sup_name);
				}

				$supid = get_post_meta($item['product_id'], 'supplierid', true);

				if($supid != "" || !is_null($supid)){
					update_post_meta($item_id,'supplierid', $supid);
					update_post_meta($order->get_id(),'supplier_'.$supid,$sup_name);
				}


				$ds = wc_dropshipping_get_dropship_supplier_by_product_id( intval( $item['product_id'] ) );
				if ($ds['id'] > 0) {
					$product = $order->get_product_from_item( $item ); // get the product obj
					
					$prod_info = $this->get_order_product_info($item,$product);


					if(!array_key_exists($ds['slug'],$order_info['suppliers']))
					{
						$order_info['suppliers'][$ds['slug']] = $ds;  // ...add newly found dropship_supplier to the supplier array
						$order_info[$ds['slug']] = array(); // ... and create an empty array to store product info in
					}
					$order_info[$ds['slug']][] = $prod_info;
					//$order_info[$ds['slug'].'_raw'][] = $product;
				}
			}
		} else {
			// how did we get here?
			//$this->sendAlert('No Products found for order #'.$order_info['id'].'!');
			//die;
		}
		return $order_info;
	}

	public function formatPhone($pnum) {
		return preg_replace('~.*(\d{3})[^\d]*(\d{3})[^\d]*(\d{4}).*~', '($1) $2-$3', $pnum);
	}

	public function get_from_name() {
		return wp_specialchars_decode(get_option( 'woocommerce_email_from_name' ));
	}

	public function get_from_address() {
		return get_option( 'woocommerce_email_from_address' );
	}

	public function get_content_type() {
		return " text/html";
	}

	// for sending failure notifications
	public function sendAlert($text) {
		wp_mail( get_bloginfo('admin_email'), 'Alert from '.get_bloginfo('name'), $text );
	}

	public function make_directory( $path ) {
		$upload_dir = wp_upload_dir();
		$order_dir = $upload_dir['basedir'].'/'.$path;
		if( ! file_exists( $order_dir ) )
    			wp_mkdir_p( $order_dir );
		return $order_dir;
	}

	// generate packingslip PDF
	public function make_pdf($order_info,$supplier_info,$html,$file_name) {
		
		// Include TCPDF library
		if (!class_exists('TCPDF')) {
			require_once( wc_dropshipping_get_base_path() . '/lib/tcpdf_min/tcpdf.php' );
		}
		$options = get_option( 'wc_dropship_manager' );
		$logourl = $options['packing_slip_url_to_logo'];
		$fullinfo = $options['full_information'];
		$show_logo = $options['show_logo'];
		$bill = $options['billing_phone'];

		$from_name = $options['from_name'];

		$from_email = $options['from_email'];
		
		if(trim($from_name) == "")
		{
			$from_name = get_option( 'woocommerce_email_from_name' );
		}

		if(trim($from_email) == "")
		{
			$from_email = get_option( 'woocommerce_email_from_address' );
		}
			
		// make a directory for the current order (if it doesn't already exist)
		$pdf_path = $this->make_directory($order_info['id']);
		// generate a pdf for the current order and the current supplier
		$file = $pdf_path.'/'.$file_name;
		// create new PDF document
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
		// set document information
		$pdf->SetCreator(PDF_CREATOR);
	
		$logo_image_width = $options['packing_slip_url_to_logo_width'];
		$str = $logo_image_width;
		$arr = preg_split('/(?<=[0-9])(?=[a-z]+)/i',$str);
		$logo_width  = $arr['0'];                        
		$logo_size_final = ( ($logo_width > 70) ? 70 .'px' : $options['packing_slip_url_to_logo_width'] );
		if($fullinfo == '1' && $logourl != '' && $show_logo == '1' ) {
			// set default header data
			$pdf->SetHeaderData($options['packing_slip_url_to_logo'], $logo_size_final, $from_name.' '.date('Y-m-d'));
		} elseif(($fullinfo == '1' && $logourl != '' && $show_logo == '1' )){
           $pdf->SetHeaderData($options['packing_slip_url_to_logo'], $logo_size_final, $from_name.' '.date('Y-m-d'));
		}
		
		// set header and footer fonts
		$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
		//$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
		// remove default header/footer
		//$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);  // set default monospaced font
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);  // set margins
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		//$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM); // set auto page breaks
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);  // set image scale factor
		$pdf->AddPage();
		$pdf->writeHTML($html, true, false, true, false, '');
		$pdf->Output($file, 'F'); // save PDF
		return $file;
	}

	// generate packing csv
	public function make_csv($order_info,$supplier_info,$html,$file_name) {

		$options = get_option( 'wc_dropship_manager' );
		if($options['csv_inmail'] == '1') {
			$order = new WC_Order( $order_info['id'] );
			$csv_path = $this->make_directory($order_info['id']);
			$filepath = $csv_path.'/'.$file_name;
			$file = fopen($filepath, 'w+');
			$headers=array( 'Product Name', 'Product SKU', 'Product Quantity');
			fputcsv( $file, $headers ); 
			foreach($order_info[$supplier_info['slug']] as $prod_info)
			{
				fputcsv($file, array( $prod_info['name'], $prod_info['sku'], $prod_info['qty']));

			}	

			fclose($file);
			return $filepath;
		}
    }

	// get HTML packingslip
	public function get_packingslip_html($order_info,$supplier_info) {
		$html = '';
		$filename = 'packingslip.html';
		if (file_exists(get_stylesheet_directory().'/woocommerce-dropshipping/'.$supplier_info['slug'].'_'.$filename))
		{
			/* 	User can create a custom supplier packingslip PDF by creating a "woocommerce-dropshipping" directory
				inside their theme's directory and placing a custom SUPPLIERCODE_packingslip.html there */
			$templatepath = get_stylesheet_directory().'/woocommerce-dropshipping/'.$supplier_info['slug'].'_'.$filename;
		}
		else if (file_exists(get_stylesheet_directory().'/wc_dropship_manager/'.$supplier_info['slug'].'_'.$filename))
		{
			/* 	User can create a custom supplier packingslip PDF by creating a "dropship_manager" directory
				inside their theme's directory and placing a custom SUPPLIERCODE_packingslip.html there */
			$templatepath = get_stylesheet_directory().'/wc_dropship_manager/'.$supplier_info['slug'].'_'.$filename;
		}
		else if (file_exists(get_stylesheet_directory().'/woocommerce-dropshipping/'.$filename))
		{
			/* 	User can override the default packingslip PDF by creating a "woocommerce-dropshipping" directory
				inside their theme's directory and placing a custom packingslip.html there */
			$templatepath = get_stylesheet_directory().'/woocommerce-dropshipping/'.$filename;
		}
		else if (file_exists(get_stylesheet_directory().'/wc_dropship_manager/'.$filename))
		{
			/* 	User can override the default packingslip PDF by creating a "dropship_manager" directory
				inside their theme's directory and placing a custom packingslip.html there */
			$templatepath = get_stylesheet_directory().'/wc_dropship_manager/'.$filename;
		}
		else
		{
			$templatepath = wc_dropshipping_get_base_path() . $filename;
		}
		return $this->get_template_html($templatepath,$order_info,$supplier_info);
	}
	
	// get HTML packingslip
	public function get_packingslip_text($order_info,$supplier_info) {
		$html = '';
		$filename = 'packingslip_text.html';
		if (file_exists(get_stylesheet_directory().'/woocommerce-dropshipping/'.$supplier_info['slug'].'_'.$filename))
		{
			/* 	User can create a custom supplier packingslip PDF by creating a "woocommerce-dropshipping" directory
				inside their theme's directory and placing a custom SUPPLIERCODE_packingslip.html there */
			$templatepath = get_stylesheet_directory().'/woocommerce-dropshipping/'.$supplier_info['slug'].'_'.$filename;
		}
		else if (file_exists(get_stylesheet_directory().'/wc_dropship_manager/'.$supplier_info['slug'].'_'.$filename))
		{
			/* 	User can create a custom supplier packingslip PDF by creating a "dropship_manager" directory
				inside their theme's directory and placing a custom SUPPLIERCODE_packingslip.html there */
			$templatepath = get_stylesheet_directory().'/wc_dropship_manager/'.$supplier_info['slug'].'_'.$filename;
		}
		else if (file_exists(get_stylesheet_directory().'/woocommerce-dropshipping/'.$filename))
		{
			/* 	User can override the default packingslip PDF by creating a "woocommerce-dropshipping" directory
				inside their theme's directory and placing a custom packingslip.html there */
			$templatepath = get_stylesheet_directory().'/woocommerce-dropshipping/'.$filename;
		}
		else if (file_exists(get_stylesheet_directory().'/wc_dropship_manager/'.$filename))
		{
			/* 	User can override the default packingslip PDF by creating a "dropship_manager" directory
				inside their theme's directory and placing a custom packingslip.html there */
			$templatepath = get_stylesheet_directory().'/wc_dropship_manager/'.$filename;
		}
		else
		{
			$templatepath = wc_dropshipping_get_base_path() . $filename;
		}
		return $this->get_template_html($templatepath,$order_info,$supplier_info);
	}

	public function get_template_html($templatepath,$order_info,$supplier_info) {
		$html = '';
		ob_start();
		if (file_exists($templatepath)){
			include($templatepath);
		} else {
			echo '<b>Template '.$templatepath.' not found!</b>';
		}
		$html = ob_get_clean();
		return $html;
	}

	// send the pdf to the supplier
	public function send_order($order_info,$supplier_info) {	
		$order = wc_get_order($order_info['id']);
			$options = get_option( 'wc_dropship_manager' );

			$smtp_check = $options['smtp_check'];

			$from_name = $options['from_name'];

			$from_email = $options['from_email'];

			if(trim($from_name) == "")
			{
				$from_name = get_option( 'woocommerce_email_from_name' );
			}

			if(trim($from_email) == "")
			{
				$from_email = get_option( 'woocommerce_email_from_address' );
			}

			

			if($smtp_check == '1'){


				$attachments = array();
				$attachments = apply_filters('wc_dropship_manager_send_order_attachments',$attachments,$order_info,$supplier_info);  // create a pdf packing slip file
				

				$fullinfo = $order_info['options']['full_information'];

				$bill = $order_info['options']['billing_phone'];
					array_push($attachments, $attachments['pdf_packingslip'] );
					array_push($attachments, $attachments['csv_packingslip'] );
				


				$hdrs = array();
				$hdrs['From'] = $from_email;
				$hdrs['To'] = $supplier_info['order_email_addresses'];
				$hdrs['CC'] = $from_email;
				$textPlain = $this->get_packingslip_text($order_info,$supplier_info);
				$text = $this->get_packingslip_html($order_info,$supplier_info);
				$text = $options['email_order_note'] . $text;
				$html = apply_filters('wc_dropship_manager_send_order_email_html',$text);

			 	if ($order->get_status() == 'completed') {
			  		$hdrs['Subject'] = 'Order #'.$order_info['number'].' is completed ';
				}else {
					$hdrs['Subject'] = 'New Order #'.$order_info['number'].' From '.$from_name;
				}
				//Mail Subject
				/*if ($order_status == 'completed') {
					$hdrs['Subject'] = 'Order #'.$order_info['id'].' is completed ';
				}else {
					$hdrs['Subject'] = 'New Order #'.$order_info['id'].' From '.$from_name;
				}*/

				$message = $html;
				$headers  = "From: ".wp_specialchars_decode($from_name)." <".$from_email.">\r\n";
				$headers .= "CC: ".$from_email."\r\n";


				wp_mail($hdrs['To'], $hdrs['Subject'], $message, $headers, $attachments) ;
			}
			else
			{
				$fullinfo = $order_info['options']['full_information'];
				$bill = $order_info['options']['billing_phone'];
				$attachments = array();
				$attachments = apply_filters('wc_dropship_manager_send_order_attachments',$attachments,$order_info,$supplier_info);  // create a pdf packing slip file
                if($bill == '0'){
                   	$attachments = array();
					$attachments = apply_filters('wc_dropship_manager_send_order_attachments',$attachments,$order_info,$supplier_info);
                }
				$options = get_option( 'wc_dropship_manager' );
				$text = '';
				if(isset($attachments['pdf_packingslip'])) {
					$encoded_attachment = chunk_split(base64_encode(file_get_contents($attachments['pdf_packingslip']))); 
				}
				if(isset($attachments['csv_packingslip'])) {
					$encoded_attachment_csv = chunk_split(base64_encode(file_get_contents($attachments['csv_packingslip']))); 
				}


				$hdrs = array();
				$hdrs['From'] = $from_email;
				$hdrs['To'] = $supplier_info['order_email_addresses'];
				$hdrs['CC'] = $from_email;
				$order_status = $order->get_status();
				if ($order_status == 'completed') {
					$hdrs['Subject'] = 'Order #'.$order_info['id'].' is completed ';
				}else {
					$hdrs['Subject'] = 'New Order #'.$order_info['id'].' From '.$from_name;
				}
				
				$semi_rand = md5(time()); 
				$semi_rand_mixed = $semi_rand."11";
				$mime_boundary_alt = "{$semi_rand}";
				$mime_boundary_mixed = "{$semi_rand_mixed}";
				$headers  = "From: ".wp_specialchars_decode($from_name)." <".$from_email.">\r\n";
				$headers .= "MIME-Version: 1.0\n";  
				$headers .= "CC: ".$from_email."\r\n";
				$headers .= "Content-Type: multipart/mixed;\n";
				$headers .= " boundary=\"{$mime_boundary_mixed}\"";
			
				if (strlen($supplier_info['account_number']) > 0)
				{
					$text .= $from_name.' account number: '.$supplier_info['account_number'].'<br/>';
				}
				$textPlain = $this->get_packingslip_text($order_info,$supplier_info);
				$text = $this->get_packingslip_html($order_info,$supplier_info);
				$text = $options['email_order_note'] . $text;
				$html = apply_filters('wc_dropship_manager_send_order_email_html',$text);		
				$message = "This is a multi-part message in MIME format.\n\n";
				$message .=  "--{$mime_boundary_mixed}\n";
				$message .= "Content-Type: multipart/alternative;\n";
				$message .= " boundary=\"{$mime_boundary_alt}\"\n\n";		
				// The space in front of boundary is crucial.
				$email_message_text  = strip_tags($html); 

				$email_message_html = $html;
				
				$attachment_name = $order_info['id'].'_'.$supplier_info['slug'].'.pdf';
				
				// Add a multipart boundary above the plain message
				$message .= "--{$mime_boundary_alt}\n" .
		          "Content-Type: text/plain; charset=\"iso-8859-1\"\n" .
		          "Content-Transfer-Encoding: 7bit\n\n" .
		          $textPlain."\n\n" .
		          "--{$mime_boundary_alt}\n" .
		          "Content-Type: text/html; charset=\"iso-8859-1\"\n" .
		          "Content-Transfer-Encoding: 7bit\n\n" .
		          $email_message_html . "\n\n" .
					"--{$mime_boundary_alt}--\n";
// 		          "--{$mime_boundary_alt}\n" .
// 		            "Content-Type: application/pdf; name=".$attachment_name."\n" .
// 				          "Content-Transfer-Encoding: base64\n\n" .
// 						  "Content-Disposition: attachment".
// 				          $encoded_attachment . "\n\n" ;
// 		          "--{$mime_boundary_alt}--\n";      // Must have 2 hyphens at the end.
				  
			
				

				$fullinfo = $order_info['options']['full_information'];
				$bill = $order_info['options']['billing_phone'];
				$csv_inmail = $order_info['options']['csv_inmail'];
				$sup_companyname = $order_info['options']['store_name'];
				$sup_address = $order_info['options']['store_address'];
				$pack_company = $order_info['options']['packing_slip_company_name'];
				$pack_address = $order_info['options']['packing_slip_address'];

				if( $fullinfo == '1' && $sup_companyname == '1' && $sup_address == '1') {
					 /*$csv_name = $order_info['id'].'_'.$supplier_info['slug'].'_'.$pack_company.'_'.$pack_address.'.csv';*/
					 $csv_name = $order_info['number'].'_'.$supplier_info['slug'].'_'.$pack_company.'_'.$pack_address.'.csv';
				}else if( $fullinfo == '1' && $sup_address == '1' ) {
					/*$csv_name = $order_info['id'].'_'.$supplier_info['slug'].'_'.$pack_address.'.csv';*/
					$csv_name = $order_info['number'].'_'.$supplier_info['slug'].'_'.$pack_address.'.csv';
				}else if( $fullinfo == '1' && $sup_companyname == '1' ){
					/*$csv_name = $order_info['id'].'_'.$supplier_info['slug'].'_'.$pack_company.'.csv';*/
					$csv_name = $order_info['number'].'_'.$supplier_info['slug'].'_'.$pack_company.'.csv';
				}else{
					/*$csv_name = $order_info['id'].'_'.$supplier_info['slug'].'.csv';*/
					$csv_name = $order_info['number'].'_'.$supplier_info['slug'].'.csv';
				}
				$fullinfo = $options['full_information'];
				$bill = $order_info['options']['billing_phone'];
				if($fullinfo == '1' && $bill == '1'){
		        $message .= "--{$mime_boundary_mixed}\n" . 
				          "Content-Type: application/pdf; name=".$attachment_name."\n" .
				          "Content-Transfer-Encoding: base64"."\r\n" .
						  "Content-Disposition: attachment; filename=\"".$attachment_name."\""."\r\n"."\r\n".
				          $encoded_attachment. "\r\n" ;  
		      	} elseif ($fullinfo == '1' && $bill == '0') {
                 	$message .= "--{$mime_boundary_mixed}\n" . 
				          "Content-Type: application/pdf; name=".$attachment_name."\n" .
				          "Content-Transfer-Encoding: base64"."\r\n" .
						  "Content-Disposition: attachment; filename=\"".$attachment_name."\""."\r\n"."\r\n".
				          $encoded_attachment. "\r\n" ;                  
				}
		      	
				if($csv_inmail == '1') {
				$message .= "--{$mime_boundary_mixed}"."\r\n" . 
				          "Content-Type: application/octet-stream; name=\"".$csv_name."\""."\r\n" .
				          "Content-Transfer-Encoding: base64"."\r\n" .
						  "Content-Disposition: attachment; filename=\"".$csv_name."\""."\r\n"."\r\n".
				          $encoded_attachment_csv. "\r\n" ;  
				}
				$message .= "--{$mime_boundary_mixed}--";			// Must have 2 hyphens at the en
			
				//wp_mail($hdrs['To'], $hdrs['Subject'] , $email_message_html, $headers);

				mail($hdrs['To'], $hdrs['Subject'], $message, $headers);

		}
	}

	public function send_order_email_html( $text ) {
		return '<b>'.$text.'</b>';
	}

	public function send_order_attach_packingslip($attachments,$order_info,$supplier_info) {
		$html = $this->get_packingslip_html($order_info,$supplier_info);

		

		/*$file_name = $order_info['id'].'_'.$supplier_info['slug'].'.pdf';
		$csv_name = $order_info['id'].'_'.$supplier_info['slug'].'.csv';*/
		$options = get_option( 'wc_dropship_manager' );
		
		$fullinfo = $options['full_information'];
		$bill = $options['billing_phone'];

		$file_name = $order_info['number'].'_'.$supplier_info['slug'].'.pdf';
		$csv_name = $order_info['number'].'_'.$supplier_info['slug'].'.csv';
		
			$attachments['pdf_packingslip'] = $this->make_pdf($order_info,$supplier_info,$html,$file_name);  // create a pdf packing slip file
		
		if($options['csv_inmail'] == '1') {
			$attachments['csv_packingslip'] = $this->make_csv($order_info,$supplier_info,$html,$csv_name);
		}
		
		return $attachments;
	}
}
