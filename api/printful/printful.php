<?php
require_once(__DIR__ . '/../../template/top.php');
require_once(__DIR__ . '/../api-cache.php');
/**
 * Thin client for the Printful API (store products/variants, orders, and the
 * read-only catalog), layered over api-cache.php for cached GET requests.
 */
class PrintfulCustomAPI {
    private $api_key;
    /**
     * @param string $printful_api_key The Printful API bearer token (defaults to PRINTFUL_API_KEY).
     */
    public function __construct($printful_api_key = PRINTFUL_API_KEY) {
        $this->api_key = $printful_api_key;
    }

    /**
     * Perform a request against the Printful API, using the shared cache for GETs.
     *
     * @param string $URI     API path relative to https://api.printful.com/ (may contain $1 placeholders filled from $args).
     * @param mixed  $data     Request body to POST as JSON; false issues a GET.
     * @param mixed  ...$args  Extra arguments passed through to the cache layer (e.g. path substitutions, query strings).
     * @return mixed The decoded JSON response.
     * @throws PrintfulCustomAPIException On a cURL error or a non-200 response.
     */
    protected function send_request($URI, $data = false, ...$args) {
        $ch = curl_init();
        $headers = array();
        $headers[] = 'Authorization: Bearer ' . $this->api_key;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if ($data !== false) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            $headers[] = 'Content-Type: application/json';
        } else {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $cache_result = get_valid_cache_entry('https://api.printful.com/' . $URI, $ch, ...$args);

        if($cache_result->fetched_new_content) {
            if ($cache_result->curl_errno) {
                // TODO: handle errors
                throw new PrintfulCustomAPIException("Encountered an error executing the API request at {$URI}: " . curl_error($ch), 1);
            }

            if ($cache_result->http_code != 200) {
                throw new PrintfulCustomAPIException("Received non-success response from the API request at {$URI}: {$cache_result->http_code} --- RAW: {$cache_result->content}", 2);
            }
        }

        curl_close($ch);

        return json_decode($cache_result->content);
    }

    /**
     * Create a single-item Printful order (draft).
     *
     * @param string $name             Recipient name.
     * @param array  $shipping_address  Address fields (address1, address2, city, state_code, country_code, zip, phone, email).
     * @param mixed  $item_price        Retail price of the item.
     * @param int    $quantity          Quantity to order.
     * @param mixed  $sync_variant_id   Numeric sync-variant id, or an external variant id (prefixed with @).
     * @param mixed  $options           Optional per-item options; serialized into the order notes.
     * @param mixed  $amount_paid       If set, overrides the item's retail price with the amount actually paid.
     * @return PrintfulOrder The created order.
     * @throws PrintfulCustomAPIException On an API error.
     */
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
        if (is_numeric($sync_variant_id)) {
            $payload->items[0]->sync_variant_id = $sync_variant_id;
        } else {
            $payload->items[0]->external_variant_id = str_replace('@', '', $sync_variant_id);
        }
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

    /**
     * Confirm a draft order for fulfillment.
     *
     * @param mixed $order_id The Printful order id to confirm.
     * @return PrintfulOrder The confirmed order.
     * @throws PrintfulCustomAPIException On an API error.
     */
    public function confirm_order($order_id) {
        $create_order_results = $this->send_request("orders/$1/confirm", null, $order_id);
        $parsed_created_order_results = $this->parse_results($create_order_results);

        $order = new PrintfulOrder($parsed_created_order_results->get_results());

        return $order;
    }

    // Cancel a Printful order (DELETE /orders/{id}). Only succeeds while the order
    // is still cancellable (draft/pending) — a fulfilled/shipped order can't be
    // cancelled. Direct request (not cached, since it mutates). Returns the HTTP
    // status code.
    /**
     * @param mixed $order_id The Printful order id to cancel.
     * @return int The HTTP status code returned by the DELETE request.
     */
    public function cancel_order($order_id) {
        $ch = curl_init('https://api.printful.com/orders/' . rawurlencode($order_id));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . $this->api_key));
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code;
    }

    /**
     * List store products, optionally filtered by a search string.
     *
     * @param string $search_string Optional product-name search filter.
     * @return PrintfulResult The paginated result set of products.
     * @throws PrintfulCustomAPIException On an API error.
     */
    public function get_products($search_string = "") {
        if (!empty($search_string)) {
            $search_string = "&search=" . $search_string;
        }

        $products_results = $this->send_request("store/products$1", false, "?limit=100" . $search_string);
        $parsed_products_results = $this->parse_results($products_results);

        foreach ($parsed_products_results->get_results() as $product) {
            $product = new PrintfulProduct($product);
        }

        return $parsed_products_results;
    }

    /**
     * Fetch a single store product (with its sync variants) by id.
     *
     * @param mixed $product_id The store product id.
     * @return PrintfulSyncProduct|null The product, or null if not found.
     * @throws PrintfulCustomAPIException If the id is empty or the API errors.
     */
    public function get_product($product_id) {
        if (empty($product_id)) {
            throw new PrintfulCustomAPIException("Null or empty product id passed.");
        }

        $product_results = $this->send_request("store/products/$1", false, $product_id);
        $parsed_product_results = $this->parse_results($product_results);

        if ($parsed_product_results->get_results()) {
            return new PrintfulSyncProduct($parsed_product_results);
        } else {
            return null;
        }
    }

    /**
     * Fetch a single store sync variant by id.
     *
     * @param mixed $sync_variant_id The sync variant id.
     * @return PrintfulVariant|null The variant, or null if not found.
     * @throws PrintfulCustomAPIException If the id is empty or the API errors.
     */
    public function get_variant($sync_variant_id) {
        if (empty($sync_variant_id)) {
            throw new PrintfulCustomAPIException("Null or empty sync variant id passed.");
        }

        $sync_variant_results = $this->send_request("store/variants/$1", false, $sync_variant_id);
        $parsed_sync_variant_results = $this->parse_results($sync_variant_results);

        if ($parsed_sync_variant_results->get_results()) {
            //return new PrintfulSyncVariant($parsed_sync_variant_results->get_results()); // this is the correct output, but there is a bug with the Printful API
            return new PrintfulVariant($parsed_sync_variant_results->get_results());
        } else {
            return null;
        }
    }

    /**
     * Convenience lookup of a product's price and currency.
     *
     * @param mixed $product_id The store product id.
     * @return array A two-element array of [price, currency].
     * @throws PrintfulCustomAPIException On an API error.
     */
    public function get_product_price($product_id) {
        $product = $this->get_product($product_id);
        return [$product->get_product_price(), $product->get_product_currency()];
    }

    /**
     * Fetch a variant from the read-only Printful catalog by id.
     *
     * @param mixed $variant_id The catalog variant id.
     * @return PrintfulCatalogVariant|null The catalog variant, or null if not found.
     * @throws PrintfulCustomAPIException If the id is empty or the API errors.
     */
    public function get_catalog_variant($variant_id) {
        if (empty($variant_id)) {
            throw new PrintfulCustomAPIException("Null or empty variant id passed.");
        }

        $variant_results = $this->send_request("products/variant/$1", false, $variant_id);
        $parsed_variant_results = $this->parse_results($variant_results);

        if ($parsed_variant_results->get_results()) {
            return new PrintfulCatalogVariant($parsed_variant_results);
        } else {
            return null;
        }
    }

    /**
     * Fetch a product from the read-only Printful catalog by id.
     *
     * @param mixed $product_id The catalog product id.
     * @return PrintfulCatalogProduct|null The catalog product, or null if not found.
     * @throws PrintfulCustomAPIException If the id is empty or the API errors.
     */
    public function get_catalog_product($product_id) {
        if (empty($product_id)) {
            throw new PrintfulCustomAPIException("Null or empty product id passed.");
        }

        $product_results = $this->send_request("products/$1", false, $product_id);
        $parsed_product_results = $this->parse_results($product_results);

        if ($parsed_product_results->get_results()) {
            return new PrintfulCatalogProduct($parsed_product_results);
        } else {
            return null;
        }
    }

    /**
     * @param mixed $results A decoded Printful API response.
     * @return PrintfulResult The wrapped result/pagination.
     */
    private function parse_results($results) {
        return new PrintfulResult($results);
    }
}

/**
 * Wraps a decoded Printful API response, exposing its result payload and paging.
 */
class PrintfulResult {
    private $results;
    private $pagination;
    private $has_pages = false;

    /**
     * Hydrate from a decoded Printful API response.
     *
     * @param object $object The decoded response (with a `result` and optional `paging`).
     */
    public function __construct($object) {
        $this->results = $object->result;

        if (property_exists($object, 'paging')) {
            $this->pagination = $object->paging;
            $this->has_pages = true;
        }
    }

    /** @return mixed The `result` payload of the response. */
    public function get_results() {
        return $this->results;
    }

    /** @return mixed The paging object, if the response was paginated. */
    public function get_pagination() {
        return $this->pagination;
    }
}

/**
 * Wraps a Printful store sync-product (its base product plus its sync variants).
 */
class PrintfulSyncProduct {
    private $name;
    private $price;
    private $currency;

    private $product;
    private $variants = array();

    /**
     * Hydrate from a PrintfulResult wrapping a store product response.
     *
     * @param PrintfulResult $object Result whose payload holds `sync_product` and `sync_variants`.
     */
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

    /** @return PrintfulProduct The base product. */
    public function get_product() {
        return $this->product;
    }

    /** @return PrintfulVariant[] The product's sync variants. */
    public function get_variants() {
        return $this->variants;
    }

    /** @return string The product name. */
    public function get_name() {
        return $this->name;

    }

    /** @return mixed The first variant's retail price (product price). */
    public function get_product_price() {
        return $this->price;

    }

    /** @return mixed The first variant's currency. */
    public function get_product_currency() {
        return $this->currency;

    }
}

/**
 * Wraps a Printful store sync-variant response (its product and variant details).
 */
class PrintfulSyncVariant {
    private $name;
    private $price;
    private $currency;

    private $product;
    private $variant;

    /**
     * Hydrate from a PrintfulResult wrapping a sync-variant response.
     *
     * @param PrintfulResult $object Result whose payload holds `sync_variant` and `sync_product`.
     */
    public function __construct($object) {
        $this->name = $object->get_results()->sync_variant->name;
        $this->price = $object->get_results()->sync_variant->retail_price;
        $this->currency = $object->get_results()->sync_variant->currency;

        $this->product = new PrintfulProduct($object->get_results()->sync_product);
        $this->variant = new PrintfulVariant($object->get_results()->sync_variant);
    }

    /** @return PrintfulProduct The base product. */
    public function get_product() {
        return $this->product;
    }

    /** @return PrintfulVariant The variant details. */
    public function get_variant() {
        return $this->variant;
    }

    /** @return string The variant name. */
    public function get_name() {
        return $this->name;

    }

    /** @return mixed The variant's retail price. */
    public function get_variant_price() {
        return $this->price;

    }

    /** @return mixed The variant's currency. */
    public function get_variant_currency() {
        return $this->currency;

    }
}

/**
 * Wraps a Printful product object from a decoded API response.
 */
class PrintfulProduct {
    private $id;
    private $external_id;
    private $name;
    private $number_of_variants;
    private $synced;
    private $thumbnail_url;

    /**
     * Hydrate from a decoded Printful product object.
     *
     * @param object $object The decoded product object.
     */
    public function __construct($object) {
        $this->id = $object->id;
        $this->external_id = $object->id;
        $this->name = $object->id;
        $this->number_of_variants = $object->id;
        $this->synced = $object->id;
        $this->thumbnail_url = $object->id;
    }

    /** @return mixed The product id. */
    public function get_id() {
        return $this->id;
    }
    /** @return mixed The product's external id. */
    public function get_external_id() {
        return $this->external_id;
    }
    /** @return mixed The product name. */
    public function get_name() {
        return $this->name;
    }
    /** @return mixed The number of variants. */
    public function get_number_of_variants() {
        return $this->number_of_variants;
    }
    /** @return mixed The synced-variant count/flag. */
    public function get_synced() {
        return $this->synced;
    }
    /** @return mixed The product thumbnail URL. */
    public function get_thumbnail_url() {
        return $this->thumbnail_url;
    }
}

/**
 * Wraps a Printful variant object (store or order item) from a decoded API response.
 */
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

    /**
     * Hydrate from a decoded Printful variant object.
     *
     * @param object $object The decoded variant object (with `product` and `files`).
     */
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

    /** @return mixed The variant id. */
    public function get_id() {
        return $this->id;
    }
    /** @return mixed The catalog variant id. */
    public function get_variant_id() {
        return $this->variant_id;
    }
    /** @return string The variant name. */
    public function get_name() {
        return $this->name;
    }
    /** @return mixed The variant's external id. */
    public function get_external_id() {
        return $this->external_id;
    }
    /** @return mixed The internal (Printful) price, or null if not present. */
    public function get_internal_price() {
        return $this->internal_price;
    }
    /** @return mixed The retail price. */
    public function get_price() {
        return $this->price;
    }
    /** @return mixed The currency, if present. */
    public function get_currency() {
        return $this->currency;
    }
    /** @return PrintfulVariantProduct The variant's product summary. */
    public function get_product() {
        return $this->product;
    }
    /** @return PrintfulVariantFile[] The variant's print/preview files. */
    public function get_files() {
        return $this->files;
    }
    /**
     * Find the first file of a given type.
     *
     * @param string $type A file type (see PrintfulVariantFilesTypes).
     * @return PrintfulVariantFile|null The matching file, or null if none.
     */
    public function get_file_by_type($type) {
        foreach ($this->files as $file) {
            if ($file->get_type() == $type) {
                return $file;
            }
        }
        return null;
    }
}

/**
 * Wraps the product summary embedded in a Printful variant object.
 */
class PrintfulVariantProduct {
    private $variant_id;
    private $product_id;
    private $image;
    private $name;

    /**
     * Hydrate from a decoded variant's `product` object.
     *
     * @param object $object The decoded product summary object.
     */
    public function __construct($object) {
        $this->variant_id = $object->variant_id;
        $this->product_id = $object->product_id;
        $this->image = $object->image;
        $this->name = $object->name;
    }

    /** @return mixed The catalog variant id. */
    public function get_variant_id() {
        return $this->variant_id;
    }
    /** @return mixed The catalog product id. */
    public function get_product_id() {
        return $this->product_id;
    }
    /** @return mixed The product image URL. */
    public function get_image() {
        return $this->image;
    }
    /** @return string The product name. */
    public function get_name() {
        return $this->name;
    }
}

/**
 * Constants for the known Printful variant file types.
 */
class PrintfulVariantFilesTypes {
    const PREVIEW = "preview";
    const VOREINSTELLUNG = "default";
    const BACK = "back";
}

/**
 * Wraps a Printful variant file object (print/preview artwork) from a decoded API response.
 */
class PrintfulVariantFile {
    private $id;
    private $filename;
    private $url;
    private $thumbnail_url;
    private $preview_url;
    private $type;

    /**
     * Hydrate from a decoded Printful file object.
     *
     * @param object $object The decoded file object.
     */
    public function __construct($object) {
        $this->id = $object->id;
        $this->filename = $object->filename;
        $this->url = $object->url;
        $this->thumbnail_url = $object->thumbnail_url;
        $this->preview_url = $object->preview_url;
        $this->type = $object->type;
    }

    /** @return mixed The file id. */
    public function get_id() {
        return $this->id;
    }
    /** @return string The file's original filename. */
    public function get_filename() {
        return $this->filename;
    }
    /** @return string The full-resolution file URL. */
    public function get_url() {
        return $this->url;
    }
    /** @return string The thumbnail URL. */
    public function get_thumbnail_url() {
        return $this->thumbnail_url;
    }
    /** @return string The preview URL. */
    public function get_preview_url() {
        return $this->preview_url;
    }
    /** @return string The file type (see PrintfulVariantFilesTypes). */
    public function get_type() {
        return $this->type;
    }
}

/**
 * Wraps a Printful catalog variant response (variant plus optional product).
 */
class PrintfulCatalogVariant {
    private $variant;
    private $product;

    /**
     * Hydrate from a decoded catalog variant response.
     *
     * @param object $object The decoded response whose `result` holds `variant` and optional `product`.
     */
    public function __construct($object) {
        $this->variant = new PrintfulCatalogVariantVariant($object->result->variant);
        if (property_exists($object->result, 'product')) {
            $this->product = new PrintfulCatalogVariantProduct($object->result->product);
        }
    }

    /** @return PrintfulCatalogVariantVariant The catalog variant details. */
    public function get_variant() {
        return $this->variant;
    }
    /** @return PrintfulCatalogVariantProduct The catalog product, if present. */
    public function get_product() {
        return $this->product;
    }
}

/**
 * Wraps the variant portion of a Printful catalog variant response (colour and size).
 */
class PrintfulCatalogVariantVariant {
    private $id;
    private $colour_code;
    private $colour_code2;
    private $colour_name;
    private $size;

    /**
     * Hydrate from a decoded catalog `variant` object.
     *
     * @param object $object The decoded variant object.
     */
    public function __construct($object) {
        $this->id = $object->id;
        $this->colour_code = $object->color_code;
        $this->colour_code2 = $object->color_code2;
        $this->colour_name = property_exists($object, 'color') ? $object->color : '';
        $this->size = property_exists($object, 'size') ? $object->size : '';
    }

    /** @return mixed The catalog variant id. */
    public function get_id() {
        return $this->id;
    }
    /** @return mixed The primary colour hex code. */
    public function get_colour_code() {
        return $this->colour_code;
    }
    /** @return mixed The secondary colour hex code. */
    public function get_secondary_colour_code() {
        return $this->colour_code2;
    }
    /** @return string The colour name, or '' if absent. */
    public function get_colour_name() {
        return $this->colour_name;
    }
    /** @return string The size, or '' if absent. */
    public function get_size() {
        return $this->size;
    }
}

/**
 * Wraps the product portion of a Printful catalog response (type, brand, model, dimensions).
 */
class PrintfulCatalogVariantProduct {
    private $type;
    private $type_name;
    private $brand;
    private $model;
    private $dimensions = null;
    private $description;

    /**
     * Hydrate from a decoded catalog `product` object.
     *
     * @param object $object The decoded product object (with optional `dimensions`).
     */
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

    /** @return mixed The product type code. */
    public function get_type() {
        return $this->type;
    }
    /** @return mixed The human-readable product type name. */
    public function get_type_name() {
        return $this->type_name;
    }
    /** @return mixed The brand. */
    public function get_brand() {
        return $this->brand;
    }
    /** @return mixed The model. */
    public function get_model() {
        return $this->model;
    }
    /** @return PrintfulCatalogVariantProductDimensions|null The dimensions, or null if absent. */
    public function get_dimensions() {
        return $this->dimensions;
    }
    /** @return mixed The product description. */
    public function get_description() {
        return $this->description;
    }
}

/**
 * A Printful catalog product together with its list of catalog variants.
 */
class PrintfulCatalogProduct extends PrintfulCatalogVariantProduct {
    private $variants = array();

    /**
     * Hydrate from a PrintfulResult wrapping a catalog product response.
     *
     * @param PrintfulResult $object Result whose payload holds `variants` and `product`.
     */
    public function __construct($object) {
        foreach ($object->get_results()->variants as $variant) {
            $this->variants[] = new PrintfulCatalogVariantVariant($variant);
        }
        parent::__construct($object->get_results()->product);
    }

    /** @return PrintfulCatalogVariantVariant[] The catalog variants. */
    public function get_variants() {
        return $this->variants;
    }
}

/**
 * Wraps the print-area dimensions of a Printful catalog product.
 */
class PrintfulCatalogVariantProductDimensions {
    private $front; // nullable?
    private $side = null;

    /**
     * Hydrate from a decoded catalog `dimensions` object.
     *
     * @param object $object The decoded dimensions object (with `front` and optional `side`).
     */
    public function __construct($object) {
        $this->front = $object->front;
        if (property_exists($object, 'side')) {
            $this->side = $object->side;
        }
    }

    /** @return mixed The front print-area dimensions. */
    public function get_front() {
        return $this->front;
    }
    /** @return mixed The side print-area dimensions, or null if absent. */
    public function get_side() {
        return $this->side;
    }
}

/**
 * Wraps a Printful order object (recipient, items, costs, status) from a decoded API response.
 */
class PrintfulOrder {
    private $id;
    private $recipient;
    private $items = array();
    private $costs;
    private $status;
    private $shipping_class;
    private $shipping_service_name;
    private $notes;

    /**
     * Hydrate from a decoded Printful order object.
     *
     * @param object $object The decoded order object.
     */
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

    /** @return mixed The order id. */
    public function get_id() {
        return $this->id;
    }
    /** @return PrintfulOrderRecipient The shipping recipient. */
    public function get_recipient() {
        return $this->recipient;
    }
    /** @return PrintfulVariant[] The order line items. */
    public function get_items() {
        return $this->items;
    }
    /** @return PrintfulOrderCosts The order cost breakdown. */
    public function get_costs() {
        return $this->costs;
    }
    /** @return mixed The order status (see PrintfulOrderStatus). */
    public function get_status() {
        return $this->status;
    }
    /** @return mixed The shipping class/method. */
    public function get_shipping_class() {
        return $this->shipping_class;
    }
    /** @return mixed The shipping service name, if present. */
    public function get_shipping_service_name() {
        return $this->shipping_service_name;
    }
    /** @return mixed The order notes. */
    public function get_notes() {
        return $this->notes;
    }
}

/**
 * Wraps the cost breakdown of a Printful order from a decoded API response.
 */
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

    /**
     * Hydrate from a decoded Printful order `costs` object.
     *
     * @param object $object The decoded costs object.
     */
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

    /** @return mixed The currency, if present. */
    public function get_currency() {
        return $this->currency;
    }
    /** @return mixed The subtotal. */
    public function get_subtotal() {
        return $this->subtotal;
    }
    /** @return mixed The discount amount. */
    public function get_discount() {
        return $this->discount;
    }
    /** @return mixed The shipping cost. */
    public function get_shipping() {
        return $this->shipping;
    }
    /** @return mixed The digitization fee. */
    public function get_digitization() {
        return $this->digitization;
    }
    /** @return mixed The additional fee. */
    public function get_additional_fee() {
        return $this->additional_fee;
    }
    /** @return mixed The fulfillment fee. */
    public function get_fulfillment_fee() {
        return $this->fulfillment_fee;
    }
    /** @return mixed The tax amount. */
    public function get_tax() {
        return $this->tax;
    }
    /** @return mixed The VAT amount. */
    public function get_vat() {
        return $this->vat;
    }
    /** @return mixed The order total. */
    public function get_total() {
        return $this->total;
    }
}

/**
 * Wraps the shipping recipient/address of a Printful order from a decoded API response.
 */
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

    /**
     * Hydrate from a decoded Printful order `recipient` object.
     *
     * @param object $object The decoded recipient object.
     */
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

    /** @return mixed The recipient name. */
    public function get_name() {
        return $this->name;
    }
    /** @return mixed The company name. */
    public function get_company() {
        return $this->company;
    }
    /** @return mixed The first address line. */
    public function get_address1() {
        return $this->address1;
    }
    /** @return mixed The second address line. */
    public function get_address2() {
        return $this->address2;
    }
    /** @return mixed The city. */
    public function get_city() {
        return $this->city;
    }
    /** @return mixed The state/province code. */
    public function get_state_code() {
        return $this->state_code;
    }
    /** @return mixed The state/province name. */
    public function get_state_name() {
        return $this->state_name;
    }
    /** @return mixed The country code. */
    public function get_country_code() {
        return $this->country_code;
    }
    /** @return mixed The country name. */
    public function get_country_name() {
        return $this->country_name;
    }
    /** @return mixed The postal/ZIP code. */
    public function get_zip() {
        return $this->zip;
    }
    /** @return mixed The phone number. */
    public function get_phone() {
        return $this->phone;
    }
    /** @return mixed The email address. */
    public function get_email() {
        return $this->email;
    }
}

/**
 * Wraps a Printful shipment object (carrier, tracking, items) from a decoded API response.
 */
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

    /**
     * Hydrate from a decoded Printful shipment object.
     *
     * @param object $object The decoded shipment object.
     */
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

    /** @return mixed The shipment id. */
    public function get_id() {
        return $this->id;
    }
    /** @return mixed The shipment status, if present. */
    public function get_status() {
        return $this->status;
    }
    /** @return mixed The carrier. */
    public function get_carrier() {
        return $this->carrier;
    }
    /** @return mixed The shipping service. */
    public function get_service() {
        return $this->service;
    }
    /** @return mixed The tracking number. */
    public function get_tracking_number() {
        return $this->tracking_number;
    }
    /** @return mixed The tracking URL. */
    public function get_tracking_url() {
        return $this->tracking_url;
    }
    /** @return mixed The creation timestamp. */
    public function get_created() {
        return $this->created;
    }
    /** @return mixed The ship date. */
    public function get_ship_date() {
        return $this->ship_date;
    }
    /** @return mixed The shipped-at timestamp. */
    public function get_shipped_at() {
        return $this->shipped_at;
    }
    /** @return int Whether this is a reshipment (boolean as int). */
    public function get_reshipment() {
        return $this->reshipment;
    }
    /** @return PrintfulShipmentItem[] The shipped items. */
    public function get_items() {
        return $this->items;
    }
}

/**
 * Wraps a single line item within a Printful shipment.
 */
class PrintfulShipmentItem {
    private $item_id;
    private $quantity;

    /**
     * Hydrate from a decoded shipment item object.
     *
     * @param object $object The decoded item object.
     */
    public function __construct($object) {
        $this->item_id = $object->item_id;
        $this->quantity = $object->quantity;
    }

    /** @return mixed The order item id. */
    public function get_item_id() {
        return $this->item_id;
    }
    /** @return mixed The quantity shipped. */
    public function get_quantity() {
        return $this->quantity;
    }
}

/**
 * Base class for Printful webhook event payloads.
 */
class PrintfulWebhookEvent {
    // TODO
}

/**
 * Printful "package shipped" webhook event, carrying the shipment and order.
 */
class PrintfulShippedEvent extends PrintfulWebhookEvent {
    private $shipment;
    private $order;

    /**
     * Hydrate from a decoded shipped-event object.
     *
     * @param object $object The decoded event data (with `shipment` and `order`).
     */
    public function __construct($object) {
        $this->shipment = new PrintfulShipment($object->shipment);
        $this->order = new PrintfulOrder($object->order);
    }

    /** @return PrintfulShipment The shipment. */
    public function get_shipment() {
        return $this->shipment;
    }
    /** @return PrintfulOrder The order. */
    public function get_order() {
        return $this->order;
    }
}

/**
 * Printful "package returned" webhook event; a shipped event plus a return reason.
 */
class PrintfulReturnedEvent extends PrintfulShippedEvent {
    private $reason;
    /**
     * Hydrate from a decoded returned-event object.
     *
     * @param object $object The decoded event data (with `reason`, plus `shipment` and `order`).
     */
    public function __construct($object) {
        $this->reason = $object->reason;
        parent::__construct($object);
    }

    /** @return mixed The return reason. */
    public function get_reason() {
        return $this->reason;
    }
}

/**
 * Constants for the possible Printful order fulfillment statuses.
 */
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

/**
 * Exception thrown for Printful API request and response errors.
 */
class PrintfulCustomAPIException extends Exception {
    /**
     * @param string         $message  The error message.
     * @param int            $code     Optional error code.
     * @param Exception|null $previous Optional previous exception for chaining.
     */
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    /** @return string A human-readable representation of the exception. */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}" . PHP_EOL;
    }
}