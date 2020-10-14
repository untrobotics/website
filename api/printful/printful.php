<?php

class PrintfulCustomAPI {
	private $encoded_api_key;
	
	public function __construct($printful_api_key = PRINTFUL_API_KEY) {
		$this->encoded_api_key = base64_encode($printful_api_key);
	}
	
	protected function send_request($URI, $data = false) {
		$ch = curl_init();

		$headers = array();
		$headers[] = 'Authorization: Basic ' . $this->encoded_api_key;
		
		curl_setopt($ch, CURLOPT_URL, 'https://api.printful.com/' . $URI);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		if ($data !== false) {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			$headers[] = 'Content-Type: application/json';
		} else {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		
		$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		if (curl_errno($ch)) {
			//echo 'Error:' . curl_error($ch);
			// TODO: handle errors
			throw new PrintfulCustomAPIException("Encountered an error executing the API request at {$URI}: " . curl_error($ch), 1);
		}
		
		if ($httpcode != 200) {
			throw new PrintfulCustomAPIException("Received non-success response from the API request at {$URI}: {$httpcode} --- RAW: {$result}", 2);
		}
		
		curl_close($ch);
		
		return json_decode($result);
	}
	
	public function create_order_single($name, $shipping_address, $item_price, $quantity, $sync_variant_id, $options = null, $amount_paid = null) {
		$payload = new stdClass();

		$payload->recipient = new stdClass();
		$payload->recipient->name = $name;
		$payload->recipient->address1 = $shipping_address['address1'];
		$payload->recipient->address2 = $shipping_address['address2'];
		$payload->recipient->city = $shipping_address['city'];
		$payload->recipient->state_code = $shipping_address['state_code'];
		$payload->recipient->country_code = $shipping_address['country_code'];
		$payload->recipient->zip = $shipping_address['zip'];
		$payload->recipient->phone = $shipping_address['phone'];
		$payload->recipient->email = $shipping_address['email'];

		$payload->items = array();
		$payload->items[0] = new stdClass();
		$payload->items[0]->quantity = $quantity;
		$payload->items[0]->sync_variant_id = $sync_variant_id;
		$payload->items[0]->retail_price = $item_price;
		$payload->items[0]->price = $item_price;
		$payload->notes = serialize($options);
		if ($options != null) {
			//$payload->items[0]->options = $options;
		}
		if ($amount_paid != null) {
			$payload->items[0]->retail_price = $amount_paid;
		}

		$create_order_results = $this->send_request('orders', $payload);
		$parsed_created_order_results = $this->parse_results($create_order_results);
		
		$order = new PrintfulOrder($parsed_created_order_results->get_results());
		
		return $order;
	}
	
	public function confirm_order($order_id) {
		$create_order_results = $this->send_request("orders/{$order_id}/confirm", null);
		$parsed_created_order_results = $this->parse_results($create_order_results);
		
		$order = new PrintfulOrder($parsed_created_order_results->get_results());
		
		return $order;
	}
	
	public function get_products() {
		$products_results = $this->send_request("store/products");
		$parsed_products_results = $this->parse_results($products_results);
		
		foreach ($parsed_products_results->get_results() as $product) {
			$product = new PrintfulProduct($product);
		}
		
		return $parsed_products_results;
	}
	
	public function get_product($product_id) {
		if (empty($product_id)) {
			throw new PrintfulCustomAPIException("Null or empty product id passed.");
		}
		
		$product_results = $this->send_request("store/products/{$product_id}");
		$parsed_product_results = $this->parse_results($product_results);
		
		if ($parsed_product_results->get_results()) {
			return new PrintfulSyncProduct($parsed_product_results);
		} else {
			return null;
		}
	}
	
	public function get_variant($sync_variant_id) {
		if (empty($sync_variant_id)) {
			throw new PrintfulCustomAPIException("Null or empty sync variant id passed.");
		}
		
		$sync_variant_results = $this->send_request("store/variants/{$sync_variant_id}");
		$parsed_sync_variant_results = $this->parse_results($sync_variant_results);
		
		if ($parsed_sync_variant_results->get_results()) {
			//return new PrintfulSyncVariant($parsed_sync_variant_results->get_results()); // this is the correct output, but there is a bug with the Printful API
			return new PrintfulVariant($parsed_sync_variant_results->get_results());
		} else {
			return null;
		}
	}
	
	public function get_product_price($product_id) {
		$product = $this->get_product($product_id);
		return [$product->get_product_price(), $product->get_product_currency()];
	}
	
	public function get_catalog_variant($variant_id) {
		if (empty($variant_id)) {
			throw new PrintfulCustomAPIException("Null or empty variant id passed.");
		}
		
		$variant_results = $this->send_request("products/variant/{$variant_id}");
		$parsed_variant_results = $this->parse_results($variant_results);
		
		if ($parsed_variant_results->get_results()) {
			return new PrintfulCatalogVariant($parsed_variant_results);
		} else {
			return null;
		}
	}
	
	public function get_catalog_product($product_id) {
		if (empty($product_id)) {
			throw new PrintfulCustomAPIException("Null or empty product id passed.");
		}
		
		$product_results = $this->send_request("products/{$product_id}");
		$parsed_product_results = $this->parse_results($product_results);

		if ($parsed_product_results->get_results()) {
			return new PrintfulCatalogProduct($parsed_product_results);
		} else {
			return null;
		}
	}
	
	private function parse_results($results) {
		return new PrintfulResult($results);
	}
}

class PrintfulResult {
	private $results;
	private $pagination;
	private $has_pages = false;
	
	public function __construct($object) {
		$this->results = $object->result;
		
		if (property_exists($object, 'paging')) {
			$this->pagination = $object->paging;
			$this->has_pages = true;
		}
	}
	
	public function get_results() {
		return $this->results;
	}
	
	public function get_pagination() {
		return $this->pagination;
	}
}

class PrintfulSyncProduct {
	private $name;
	private $price;
	private $currency;
	
	private $product;
	private $variants = array();
	
	public function __construct($object) {
		$this->name = $object->get_results()->sync_product->name;
		
		$this->product = new PrintfulProduct($object->get_results()->sync_product);
		foreach ($object->get_results()->sync_variants as $variant) {
			$this->variants[] = new PrintfulVariant($variant);
		}
		
		if (count($this->variants) > 0) {
			$this->price = $this->variants[0]->get_price();
			$this->currency = $this->variants[0]->get_currency();
		}
	}
	
	public function get_product() {
		return $this->product;
	}
	
	public function get_variants() {
		return $this->variants;
	}
	
	public function get_name() {
		return $this->name;
		
	}
	
	public function get_product_price() {
		return $this->price;
		
	}
	
	public function get_product_currency() {
		return $this->currency;
		
	}
}

class PrintfulSyncVariant {
	private $name;
	private $price;
	private $currency;
	
	private $product;
	private $variant;
	
	public function __construct($object) {
		$this->name = $object->get_results()->sync_variant->name;
		$this->price = $object->get_results()->sync_variant->retail_price;
		$this->currency = $object->get_results()->sync_variant->currency;
		
		$this->product = new PrintfulProduct($object->get_results()->sync_product);
		$this->variant = new PrintfulVariant($object->get_results()->sync_variant);
	}
	
	public function get_product() {
		return $this->product;
	}
	
	public function get_variant() {
		return $this->variant;
	}
	
	public function get_name() {
		return $this->name;
		
	}
	
	public function get_variant_price() {
		return $this->price;
		
	}
	
	public function get_variant_currency() {
		return $this->currency;
		
	}
}

class PrintfulProduct {
	private $id;
	private $external_id;
	private $name;
	private $number_of_variants;
	private $synced;
	private $thumbnail_url;
	
	public function __construct($object) {
		$this->id = $object->id;
		$this->external_id = $object->id;
		$this->name = $object->id;
		$this->number_of_variants = $object->id;
		$this->synced = $object->id;
		$this->thumbnail_url = $object->id;
	}
	
	public function get_id() {
		return $this->id;
	}
	public function get_external_id() {
		return $this->external_id;
	}
	public function get_name() {
		return $this->name;
	}
	public function get_number_of_variants() {
		return $this->number_of_variants;
	}
	public function get_synced() {
		return $this->synced;
	}
	public function get_thumbnail_url() {
		return $this->thumbnail_url;
	}
}

class PrintfulVariant {
	private $id;
	private $variant_id;
	private $name;
	private $external_id;
	
	private $internal_price = null;
	private $price;
	private $currency;
	
	private $product;
	private $files = array();
	
	public function __construct($object) {
		$this->id = $object->id;
		$this->variant_id = $object->variant_id;
		$this->name = $object->name;
		$this->external_id = $object->external_id;
		if (property_exists($object, 'price')) {
			$this->internal_price = $object->price;
		}
		$this->price = $object->retail_price;
		if (property_exists($object, 'currency')) {
			$this->currency = $object->currency;
		}
		$this->product = new PrintfulVariantProduct($object->product);
		foreach ($object->files as $file) {
			$this->files[] = new PrintfulVariantFile($file);
		}
	}
	
	public function get_id() {
		return $this->id;
	}
	public function get_variant_id() {
		return $this->variant_id;
	}
	public function get_name() {
		return $this->name;
	}
	public function get_external_id() {
		return $this->external_id;
	}
	public function get_internal_price() {
		return $this->internal_price;
	}
	public function get_price() {
		return $this->price;
	}
	public function get_currency() {
		return $this->currency;
	}
	public function get_product() {
		return $this->product;
	}
	public function get_files() {
		return $this->files;
	}
	public function get_file_by_type($type) {
		foreach ($this->files as $file) {
			if ($file->get_type() == $type) {
				return $file;
			}
		}
		return null;
	}
}

class PrintfulVariantProduct {
	private $variant_id;
	private $product_id;
	private $image;
	private $name;
	
	public function __construct($object) {
		$this->variant_id = $object->variant_id;
		$this->product_id = $object->product_id;
		$this->image = $object->image;
		$this->name = $object->name;
	}
	
	public function get_variant_id() {
		return $this->variant_id;
	}
	public function get_product_id() {
		return $this->product_id;
	}
	public function get_image() {
		return $this->image;
	}
	public function get_name() {
		return $this->name;
	}
}

class PrintfulVariantFilesTypes {
	const PREVIEW = "preview";
	const VOREINSTELLUNG = "default";
	const BACK = "back";
}

class PrintfulVariantFile {
	private $id;
	private $filename;
	private $url;
	private $thumbnail_url;
	private $preview_url;
	private $type;
	
	public function __construct($object) {
		$this->id = $object->id;
		$this->filename = $object->filename;
		$this->url = $object->url;
		$this->thumbnail_url = $object->thumbnail_url;
		$this->preview_url = $object->preview_url;
		$this->type = $object->type;
	}
	
	public function get_id() {
		return $this->id;
	}
	public function get_filename() {
		return $this->filename;
	}
	public function get_url() {
		return $this->url;
	}
	public function get_thumbnail_url() {
		return $this->thumbnail_url;
	}
	public function get_preview_url() {
		return $this->preview_url;
	}
	public function get_type() {
		return $this->type;
	}
}

class PrintfulCatalogVariant {
	private $variant;
	private $product;
	
	public function __construct($object) {
		$this->variant = new PrintfulCatalogVariantVariant($object->result->variant);
		if (property_exists($object->result, 'product')) {
			$this->product = new PrintfulCatalogVariantProduct($object->result->product);
		}
	}
	
	public function get_variant() {
		return $this->variant;
	}
	public function get_product() {
		return $this->product;
	}
}

class PrintfulCatalogVariantVariant {
	private $id;
	private $colour_code;
	private $colour_code2;
	
	public function __construct($object) {
		$this->id = $object->id;
		$this->colour_code = $object->color_code;
		$this->colour_code2 = $object->color_code2;
	}
	
	public function get_id() {
		return $this->id;
	}
	public function get_colour_code() {
		return $this->colour_code;
	}
	public function get_secondary_colour_code() {
		return $this->colour_code2;
	}
}

class PrintfulCatalogVariantProduct {
	private $type;
	private $type_name;
	private $brand;
	private $model;
	private $dimensions = null;
	private $description;
	
	public function __construct($object) {
		$this->type = $object->type;
		$this->type_name = $object->type_name;
		$this->brand = $object->brand;
		$this->model = $object->model;
		
		if (property_exists($object, 'dimensions')) {
			if ($object->dimensions != null) {
				$this->dimensions = new PrintfulCatalogVariantProductDimensions($object->dimensions);
			}
		}
		
		$this->description = $object->description;
	}
	
	public function get_type() {
		return $this->type;
	}
	public function get_type_name() {
		return $this->type_name;
	}
	public function get_brand() {
		return $this->brand;
	}
	public function get_model() {
		return $this->model;
	}
	public function get_dimensions() {
		return $this->dimensions;
	}
	public function get_description() {
		return $this->description;
	}
}

class PrintfulCatalogProduct extends PrintfulCatalogVariantProduct {
	private $variants = array();
	
	public function __construct($object) {		
		foreach ($object->get_results()->variants as $variant) {
			$this->variants[] = new PrintfulCatalogVariantVariant($variant);
		}
		parent::__construct($object->get_results()->product);
	}
	
	public function get_variants() {
		return $this->variants;
	}
}

class PrintfulCatalogVariantProductDimensions {
	private $front; // nullable?
	private $side = null;
	
	public function __construct($object) {
		$this->front = $object->front;
        if (property_exists($object, 'side')) {
            $this->side = $object->side;
        }
	}
	
	public function get_front() {
		return $this->front;
	}
	public function get_side() {
		return $this->side;
	}
}

class PrintfulOrder {
	private $id;
	private $recipient;
	private $items = array();
	private $costs;
	private $status;
	private $shipping_class;
	private $shipping_service_name;
	private $notes;
	
	public function __construct($object) {
		$this->id = $object->id;
		$this->recipient = new PrintfulOrderRecipient($object->recipient);
		foreach ($object->items as $item) {
			$items[] = new PrintfulVariant($item);
		}
		$this->costs = new PrintfulOrderCosts($object->costs);
		$this->status = $object->status;
		$this->shipping_class = $object->shipping;
		if (property_exists($object, 'shipping_service_name')) {
			$this->shipping_service_name = $object->shipping_service_name;
		}
		$this->notes = $object->notes;
	}
	
	public function get_id() {
		return $this->id;
	}
	public function get_recipient() {
		return $this->recipient;
	}
	public function get_items() {
		return $this->items;
	}
	public function get_costs() {
		return $this->costs;
	}
	public function get_status() {
		return $this->status;
	}
	public function get_shipping_class() {
		return $this->shipping_class;
	}
	public function get_shipping_service_name() {
		return $this->shipping_service_name;
	}
	public function get_notes() {
		return $this->notes;
	}
}

class PrintfulOrderCosts {
	private $currency;
    private $subtotal;
    private $discount;
    private $shipping;
    private $digitization;
    private $additional_fee;
    private $fulfillment_fee;
    private $tax;
    private $vat;
    private $total;
	
	public function __construct($object) {
		if (property_exists($object, 'currency')) {
			$this->currency = $object->currency;
		}
    	$this->subtotal = $object->subtotal;
    	$this->discount = $object->discount;
    	$this->shipping = $object->shipping;
    	$this->digitization = $object->digitization;
    	$this->additional_fee = $object->additional_fee;
    	$this->fulfillment_fee = $object->fulfillment_fee;
    	$this->tax = $object->tax;
    	$this->vat = $object->vat;
    	$this->total = $object->total;
	}
	
	public function get_currency() {
		return $this->currency;
	}
    public function get_subtotal() {
		return $this->subtotal;
	}
    public function get_discount() {
		return $this->discount;
	}
    public function get_shipping() {
		return $this->shipping;
	}
    public function get_digitization() {
		return $this->digitization;
	}
    public function get_additional_fee() {
		return $this->additional_fee;
	}
    public function get_fulfillment_fee() {
		return $this->fulfillment_fee;
	}
    public function get_tax() {
		return $this->tax;
	}
    public function get_vat() {
		return $this->vat;
	}
    public function get_total() {
		return $this->total;
	}
}

class PrintfulOrderRecipient {
	private $name;
	private $company;
	private $address1;
	private $address2;
	private $city;
	private $state_code;
	private $state_name;
	private $country_code;
	private $country_name;
	private $zip;
	private $phone;
	private $email;
	
	public function __construct($object) {
		$this->name = $object->name;
		$this->company = $object->company;
		$this->address1 = $object->address1;
		$this->address2 = $object->address2;
		$this->city = $object->city;
		$this->state_code = $object->state_code;
		$this->state_name = $object->state_name;
		$this->country_code = $object->country_code;
		$this->country_name = $object->country_name;
		$this->zip = $object->zip;
		$this->phone = $object->phone;
		$this->email = $object->email;
	}
	
	public function get_name() {
		return $this->name;
	}
	public function get_company() {
		return $this->company;
	}
	public function get_address1() {
		return $this->address1;
	}
	public function get_address2() {
		return $this->address2;
	}
	public function get_city() {
		return $this->city;
	}
	public function get_state_code() {
		return $this->state_code;
	}
	public function get_state_name() {
		return $this->state_name;
	}
	public function get_country_code() {
		return $this->country_code;
	}
	public function get_country_name() {
		return $this->country_name;
	}
	public function get_zip() {
		return $this->zip;
	}
	public function get_phone() {
		return $this->phone;
	}
	public function get_email() {
		return $this->email;
	}
}

class PrintfulShipment {
	private $id;
	private $status;
	private $carrier;
	private $service;
	private $tracking_number;
	private $tracking_url;
	private $created;
	private $ship_date;
	private $shipped_at;
	private $reshipment; // boolean as int
	private $items = array();
	
	public function __construct($object) {
		$this->id = $object->id;
		if (property_exists($object, 'status')) {
			$this->status = $object->status;
		}
		$this->carrier = $object->carrier;
		$this->service = $object->service;
		$this->tracking_number = $object->tracking_number;
		$this->tracking_url = $object->tracking_url;
		$this->created = $object->created;
		$this->ship_date = $object->ship_date;
		$this->shipped_at = $object->shipped_at;
		$this->reshipment = intval($object->reshipment);
		foreach ($object->items as $item) {
			$this->items[] = new PrintfulShipmentItem($item);
		}
	}
	
	public function get_id() {
		return $this->id;
	}	
	public function get_status() {
		return $this->status;
	}
	public function get_carrier() {
		return $this->carrier;
	}
	public function get_service() {
		return $this->service;
	}
	public function get_tracking_number() {
		return $this->tracking_number;
	}
	public function get_tracking_url() {
		return $this->tracking_url;
	}
	public function get_created() {
		return $this->created;
	}
	public function get_ship_date() {
		return $this->ship_date;
	}
	public function get_shipped_at() {
		return $this->shipped_at;
	}
	public function get_reshipment() {
		return $this->reshipment;
	}
	public function get_items() {
		return $this->items;
	}
}

class PrintfulShipmentItem {
	private $item_id;
	private $quantity;
	
	public function __construct($object) {
		$this->item_id = $object->item_id;
		$this->quantity = $object->quantity;
	}
	
	public function get_item_id() {
		return $this->item_id;
	}
	public function get_quantity() {
		return $this->quantity;
	}
}

class PrintfulWebhookEvent {
	// TODO
}

class PrintfulShippedEvent extends PrintfulWebhookEvent {
	private $shipment;
	private $order;
	
	public function __construct($object) {
		$this->shipment = new PrintfulShipment($object->shipment);
		$this->order = new PrintfulOrder($object->order);
	}
	
	public function get_shipment() {
		return $this->shipment;
	}
	public function get_order() {
		return $this->order;
	}
}

class PrintfulReturnedEvent extends PrintfulShippedEvent {
	private $reason;
	public function __construct($object) {
		$this->reason = $object->reason;
		parent::__construct($object);
	}
	
	public function get_reason() {
		return $this->reason;
	}
}

class PrintfulOrderStatus {
	const DRAFT = 'draft'; // - order is not submitted for fulfillment
	const FAILED = 'failed'; // - order was submitted for fulfillment but was not accepted because of an error (problem with address, printfiles, charging, etc.)
	const PENDING = 'pending'; // - order has been submitted for fulfillment
	const CANCELLED = 'canceled'; // - order is canceled
	const ON_HOLD = 'onhold'; // - order has encountered a problem during the fulfillment that needs to be resolved together with the Printful customer service
	const IN_PROGRESS = 'inprocess'; // - order is being fulfilled and is no longer cancellable
	const PARTIALLY_FULFILLED = 'partial'; // - order is partially fulfilled (some items are shipped already, the rest will follow)
	const FULFILLED = 'fulfilled'; // - all items are shipped
}

class PrintfulCustomAPIException extends Exception {
	public function __construct($message, $code = 0, Exception $previous = null) {
		parent::__construct($message, $code, $previous);
	}

	public function __toString() {
		return __CLASS__ . ": [{$this->code}]: {$this->message}" . PHP_EOL;
	}
}