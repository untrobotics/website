<?php

class JsonNoEmptyFieldSerializable implements JsonSerializable
{
    public function jsonSerialize(): array
    {
        $a = array();
        foreach ($this as $key => $val) {
            if (isset($val)) {
                $a[$key] = $val;
            }
        }
        return $a;
    }
}

/**
 * See {@see https://developer.paypal.com/docs/api/orders/v2/#orders_create PayPal's API documentation} for more details
 */
class PayPalOrder extends JsonNoEmptyFieldSerializable
{
    /**
     * @var PayPalPurchaseUnit[] (required) An array of purchase units (max 10).
     * Each purchase unit establishes a contract between a payer and the payee.
     * Each purchase unit represents either a full or partial order that the payer intends to purchase from the payee.
     */
    public $purchase_units;

    /**
     * @var string (required) The intent to either capture payment immediately or authorize a payment for an order after order creation.
     * Valid options are either "CAPTURE" or "AUTHORIZE"
     */
    public $intent;

    //payer object is deprecated and therefore not implemented
    //public $payer;

    /**
     * @var PayPalPaymentSource Used to define which payment provider used to complete the order. Also used to set
     */
    public $payment_source;

    /**
     * @deprecated all fields in this object are deprecated, use {@see $payment_source} instead. See also {@see PayPalPaymentSourceGeneric::$experience_context} or {@see PayPalPaymentSourcePayPal::$experience_context}
     */
    private $application_context;

    /**
     * @param PayPalPurchaseUnit[] $purchase_units An array of {@see PayPalPurchaseUnit}s for this order
     * @param string $intent Whether payment will be captured immediately or delayed until after the order is created
     * @param PayPalPaymentSource|null $payment_source Information about the payment source. Consider using if you want to customize the checkout experience (e.g., setting return and cancellation URLs)
     */
    public function __construct(array $purchase_units, string $intent, PayPalPaymentSource $payment_source = null)
    {
        $this->purchase_units = $purchase_units;
        $this->intent = $intent;
        $this->payment_source = $payment_source;
    }
}

/**
 * Used for confirm_order requests. Only used in more advanced transactions
 */
class PayPalOrderConfirmation extends JsonNoEmptyFieldSerializable
{
    /**
     * @var string The instruction to process an order. Defaults to NO_INSTRUCTION.
     *  Accepted values are 'ORDER_COMPLETE_ON_PAYMENT_APPROVAL' - PayPal automatically completes the order on payer approval; and
     * 'NO_INSTRUCTION' - API caller will capture or authorize payment after payer approves the order
     *
     */
    public $processing_instruction;
    /**
     * @var PayPalApplicationContext Customizes the payer confirmation experience. Not to be confused with {@see PayPalExperienceContext} which is used in the order creation process
     */
    public $application_context;

    /**
     * @var PayPalPaymentSource The payment source definition.
     */
    public $payment_source;
}

class PayPalPaymentSource extends JsonNoEmptyFieldSerializable
{
    // not including because we don't want to process the numbers
    //public $card;
    /**
     * @var PayPalToken The tokenized payment source to fund a payment.
     */
    public $token;
    /**
     * @var PayPalPaymentSourcePayPal Indicates that PayPal Wallet is the payment source. Main use of this selection is
     *  to provide additional instructions associated with this choice like vaulting
     */
    public $paypal;
    // given that we are a Texas organization, I won't implement these
//    /**
//     * @var PayPalPaymentSourceGeneric Bancontact is from Belgium
//     */
//    public $bancontact;
//    public $bilk;
//    public $eps;
//public $giropay;
//public $ideal;
// // MyBank has locations in Maryland
//public $mybank;
//public $p24;
//public $sofort;   // european
//public $trustly;  // not necessarily outside of Texas, but still not implementing

//todo: implement apple_pay google_pay and venmo objs
    public $apple_pay;
    public $google_pay;
    public $venmo;

    /**
     * Set only one of the PaymentSources
     * @param PayPalPaymentSourcePayPal|null $paypal Set this if payment will proceed through PayPal Wallet
     * @param PayPalToken|null $token Tokenized payment. Use this if the token has been generated for payment already
     * @param null $apple_pay Set this if payment will proceed through Apple Pay
     * @param null $google_pay Set this if payment will proceed through Google Pay
     * @param null $venmo Set this if payment will proceed through Venmo (which is completely separate from PayPal for some reason)
     */
    public function __construct(PayPalPaymentSourcePayPal $paypal = null, PayPalToken $token = null, $apple_pay = null, $google_pay = null, $venmo = null)
    {
        $this->google_pay = $google_pay;
        $this->apple_pay = $apple_pay;
        $this->paypal = $paypal;
        $this->token = $token;
        $this->venmo = $venmo;
    }


}

class PayPalPaymentSourceGeneric extends JsonNoEmptyFieldSerializable
{
    /**
     * @var string (3-300 chars) The name of the account holder associated with the payment
     */
    public $name;
    /**
     * @var string (2 chars) The two-character ISO 3166-1 country code.
     */
    public $country_code;
    /**
     * @var PayPalExperienceContext Customizes the payer experience during the approval process for the payment
     */
    public $experience_context;

}

class PayPalPaymentSourcePayPal extends JsonNoEmptyFieldSerializable
{
    /**
     * @var PayPalExperienceContext Customizes the payer experience during the approval process for the payment
     */
    public $experience_context;
    /**
     * @var string (2-128 chars) (alphanumeric and dashes only) The PayPal billing agreement ID. References an approved
     *  recurring payment for goods or services.
     */
    public $billing_agreement_id;
    /**
     * @var string (1-255 chars) (alphanumeric and dashes only) The PayPal-generated ID for the payment_source stored within the Vault.
     */
    public $vault_id;
    /**
     * @var string (3-254 chars) The email address of the PayPal account holder.
     */
    public $email_address;

    /**
     * @var PayPalName The name of the PayPal account holder. Supports only the {@see PayPalName::$given_name} and
     * {@see PayPalName::$surname} properties.
     */
    public $name;

    /**
     * @var PayPalPhone The phone number of the customer. Available only when you enable the Contact Telephone Number
     * option in the Profile & Settings for the merchant's PayPal account
     */
    public $phone;

    /**
     * @var string (10 chars) The birth date of the PayPal account holder in YYYY-MM-DD format.
     */
    public $birth_date;

    /**
     * @var PayPalTaxInfo The tax information of the PayPal account holder. Required only for Brazilian PayPal
     * account holder's
     */
    public $tax_info;

    /**
     * @var PayPalAddress The address of the PayPal account holder, AKA the billing address. Supports only the
     *  {@see PayPalAddress::$address_line_1}, {@see PayPalAddress::$address_line_2},
     *  {@see PayPalAddress::$admin_area_1}, {@see PayPalAddress::$admin_area_2}, {@see PayPalAddress::$postal_code},
     *  and {@see PayPalAddress::$country_code} properties.
     */
    public $address;

    /**
     * @var PayPalWalletAttributes Additional attributes associated with the use of this wallet
     */
    public $attributes;

    /**
     * @param PayPalExperienceContext $experience_context
     * @param string|null $billing_agreement_id
     * @param string|null $vault_id
     * @param string|null $email_address
     * @param PayPalName|null $name
     * @param PayPalPhone|null $phone
     * @param string|null $birth_date
     * @param PayPalTaxInfo|null $tax_info
     * @param PayPalAddress|null $address
     * @param PayPalWalletAttributes|null $attributes
     */
    public function __construct(PayPalExperienceContext $experience_context, string $billing_agreement_id = null, string $vault_id = null, string $email_address = null, PayPalName $name = null, PayPalPhone $phone = null, string $birth_date = null, PayPalTaxInfo $tax_info = null, PayPalAddress $address = null, PayPalWalletAttributes $attributes = null)
    {
        $this->experience_context = $experience_context;
        $this->billing_agreement_id = $billing_agreement_id;
        $this->vault_id = $vault_id;
        $this->email_address = $email_address;
        $this->name = $name;
        $this->phone = $phone;
        $this->birth_date = $birth_date;
        $this->tax_info = $tax_info;
        $this->address = $address;
        $this->attributes = $attributes;
    }


}

class PayPalApplicationContext extends JsonNoEmptyFieldSerializable
{

    /**
     * @var string|null (1-127 chars) Overrides the business name in PayPal
     */
    public $brand_name;

    /**
     * @var string (2-10 chars) The BCP 47-formatted locale of pages that the PayPal payment experience shows. PayPal
     *  supports a five-character code
     */
    public $locale;
    /**
     * @var string URL the customer is redirected to upon payment approval.
     *  Note: PayPal will also attach the checkout token as a GET parameter, 'token', so you may use that to check if the transaction went through
     */
    public $return_url;
    /**
     * @var string URL the customer is redirected to upon payment cancellation.
     *  Note: PayPal will also attach the checkout token as a GET parameter, 'token', so you may use that to restart the same transaction
     */
    public $cancel_url;

//    /**
//     * @var
//     */
//    public $stored_payment_source; // removed because we aren't storing this data

    /**
     * @param string|null $brand_name The brand/store/company name to show on the checkout page. Overrides the name set in PayPal
     * @param string $locale Should be in BCP 47-format (e.g., "en-US" and "en-UK")
     * @param string $return_url The URL the customer is redirected to upon payment approval
     * @param string $cancel_url The URL the customer is redirected to upon cancellation
     */
    public function __construct(?string $brand_name, string $locale, string $return_url, string $cancel_url)
    {
        $this->brand_name = $brand_name;
        $this->locale = $locale;
        $this->return_url = $return_url;
        $this->cancel_url = $cancel_url;
    }


}

/**
 * This class allows you to customize the PayPal checkout experience
 */
class PayPalExperienceContext extends PayPalApplicationContext
{
    /**
     * @var string|null (1-24 chars) The location from which the shipping address is derived. Defaults to GET_FROM_FILE.
     * Values: "GET_FROM_FILE", "NO_SHIPPING", and "SET_PROVIDED_ADDRESS".
     * Note: If GET_FROM_FILE is specified during the create_order process, an empty PayPalPaymentSource can be provided during the capture_payment process because users are also asked to approve payment with the shipping information.
     * See {@see https://developer.paypal.com/docs/api/orders/v2/#orders_create!path=payment_source/p24/experience_context/shipping_preference&t=request docs} for more details
     */
    public $shipping_preference;

    /**
     * @var string The type of landing page to show on the PayPal site for customer checkout. Defaults to 'NO_PREFERENCE'.
     * Valid values are 'LOGIN' - user is redirected to a PayPal log-in page;
     * 'GUEST_CHECKOUT' - user is redirected to a page to fill out billing and shipping information without logging in; and 'NO_PREFERENCE' - user is brought to a page asking if they want to log in or continue without logging into PayPal
     */
    public $landing_page;

    /**
     * @var string Configures a Continue or Pay Now checkout flow. Defaults to 'CONTINUE'.
     * Valid values are 'CONTINUE' and 'PAY_NOW'
     */
    public $user_action;

    /**
     * @var string The merchant-preferred payment methods. Defaults to 'UNRESTRICTED'.
     * Valid values are 'UNRESTRICTED' and 'IMMEDIATE_PAYMENT_REQUIRED' - Only accepts instantaneous payments like credit card, PayPal balance, and instant ACH
     */
    public $payment_method_preference;


    // no enums makes this so much worse... should I make a bunch of constants?

    /**
     * @param string|null $brand_name The brand/store/company name to show on the checkout page. Overrides the name set in PayPal
     * @param string|null $shipping_preference Where to get shipping information. Valid values are 'GET_FROM_FILE', 'NO_SHIPPING', and 'SET_PROVIDED_ADDRESS'. If 'SET_PROVIDED_ADDRESS', all {@see PayPalPurchaseUnit::$shipping} properties will need to be set
     * @param string $landing_page The landing page to show on PayPal. Valid values are 'LOGIN', GUEST_CHECKOUT', and 'NO_PREFERENCE'
     * @param string $user_action Whether to require payment now or continue. Valid values are 'CONTINUE' and 'PAY_NOW'
     * @param string $payment_method_preference The type of payment accepted. Valid values are 'UNRESTRICTED' and 'IMMEDIATE_PAYMENT_REQUIRED'
     * @param string $locale Should be in BCP 47-format (e.g., "en-US" and "en-UK")
     * @param string $return_url The URL the customer is redirected to upon payment approval
     * @param string $cancel_url The URL the customer is redirected to upon cancellation
     */
    public function __construct(?string $brand_name = null, ?string $shipping_preference = 'GET_FROM_FILE', string $landing_page = 'NO_PREFERENCE', string $user_action = 'CONTINUE', string $payment_method_preference = 'UNRESTRICTED', string $locale = 'en-US', string $return_url = 'https://untrobotics.com', string $cancel_url = 'https://untrobotics.com')
    {
//        $this->brand_name = $brand_name;
        $this->shipping_preference = $shipping_preference;
        $this->landing_page = $landing_page;
        $this->user_action = $user_action;
        $this->payment_method_preference = $payment_method_preference;
//        $this->locale = $locale;
//        $this->return_url = $return_url;
//        $this->cancel_url = $cancel_url;
        parent::__construct($brand_name, $locale, $return_url, $cancel_url);
    }


}

class PayPalPurchaseUnit extends JsonNoEmptyFieldSerializable
{
    /**
     * @var string (1-256 chars) Created by API client (e.g., us, not PayPal). Required if updating order through PATCH and have multiple order units
     */
    public $reference_id;
    /**
     * @var string (1-127 ASCII chars, may change based on type of chars used [e.g., Japanese characters]) Purchase description
     */
    public $description;
    /**
     * @var string (1-255 chars) Created by API client to reconcile transactions between client and PayPal. Not visible to payer
     */
    public $custom_id;
    /**
     * @var string (1-127 chars) Created by API client. Visible to payer
     */
    public $invoice_id;
    /**
     * @var string (1-22 chars) Description that appears on the payer's card statement.
     * Note: Payments made through PayPal Wallet will be shortened by 10 characters and the length of the merchant descriptor
     */
    public $soft_descriptor;
    /**
     * @var PayPalItem[] An array of items purchased
     */
    public $items;

    /**
     * @var PayPalItemsTotal (required) The total order amount with details such as the total item amount, total tax amount, shipping, handling, insurance, and discounts, if any.
     * If $amount->breakdown is specified, $amount->value must equal
     *      $amount->breakdown->item_total + $amount->breakdown->tax_total +
     *      $amount->breakdown->shipping + $amount->breakdown->handling +
     *      $amount->breakdown->insurance -
     *      $amount->breakdown->shipping_discount - $amount->breakdown->discount
     */
    public $amount;

    /**
     * @var PayPalMerchant The merchant who receives payment for this transaction
     */
    public $payee;
    /**
     * @var PayPalPaymentInstruction Any additional payment instructions to be considered during payment processing.
     *  This processing instruction is applicable for Capturing an order or Authorizing an Order
     */
    public $payment_instruction;

    /**
     * @var PayPalShipping The name and address of the person to whom to ship the items
     */
    public $shipping;
    // This field is used if we directly process card payments
    //public $supplemental_data;
    /**
     * @param PayPalItemsTotal $amount
     * @param PayPalItem[] $items
     * @param PayPalShipping|null $shipping
     * @param string|null $description
     * @param string|null $reference_id
     * @param string|null $custom_id
     * @param string|null $invoice_id
     * @param string|null $soft_descriptor
     * @param PayPalMerchant|null $payee
     * @param PayPalPaymentInstruction|null $payment_instruction
     */
    public function __construct(PayPalItemsTotal $amount, array $items, PayPalShipping $shipping = null,
                                string           $description = null, string $reference_id = null, string $custom_id = null,
                                string           $invoice_id = null, string $soft_descriptor = null,
                                PayPalMerchant   $payee = null, PayPalPaymentInstruction $payment_instruction = null)
    {
        $this->reference_id = $reference_id;
        $this->description = $description;
        $this->custom_id = $custom_id;
        $this->invoice_id = $invoice_id;
        $this->soft_descriptor = $soft_descriptor;
        $this->items = $items;
        $this->amount = $amount;
        $this->payee = $payee;
        $this->payment_instruction = $payment_instruction;
        $this->shipping = $shipping;
    }
}

class PayPalItem extends JsonNoEmptyFieldSerializable
{
    /**
     * @var string (required) (1-127 chars) The item name or title
     */
    public $name;
    /**
     * @var string (required) (0-10 chars) The item quantity. Must be an integer
     * Note: Variable type is not incorrect. PayPal wants the value sent as a string
     */
    public $quantity;
    /**
     * @var string (1-127 chars) The detailed item description
     */
    public $description;
    /**
     * @var string (1-127 chars) The SKU of the item
     */
    public $sku;
    /**
     * @var string (1-2048 chars) The URL to the purchasable item. Visible to buyer and used in buyer experience
     */
    public $url;
    /**
     * @var string (1-20 chars) The item category type. Must be one of three:
     * 1. DIGITAL_GOODS
     * 2. PHYSICAL_GOODS
     * 3. DONATION
     */
    public $category;
    /**
     * @var string (1-2048 chars) The URL of the item's image. Must be a jpg, gif, or png.
     * URL must match format "^(https:)([/|.|\w|\s|-])*\.(?:jpg|gif|png|jpeg|JPG|GIF|PNG|JPEG)"
     */
    public $image_url;
    /**
     * @var PayPalCurrencyField (required) The price per  item unit.
     * Note: If specified, purchase_units[].amount.breakdown.item_total is required and must equal unit_amount * quantity for all items
     */
    public $unit_amount;

    /**
     * @var PayPalCurrencyField The tax per item unit.
     * Note: If specified, purchase_units[].amount.breakdown.tax_total is required and must equal tax * quantity for all items
     */
    public $tax;
    /**
     * @var PayPalUPCField The UPC of the item
     */
    public $upc;

    /**
     * @param string $name
     * @param string $quantity
     * @param PayPalCurrencyField|null $unit_amount
     * @param PayPalCurrencyField|null $tax
     * @param string|null $description
     * @param string|null $sku
     * @param string|null $url
     * @param string|null $category
     * @param string|null $image_url
     * @param PayPalUPCField|null $upc
     */
    public function __construct(string              $name, string $quantity, PayPalCurrencyField $unit_amount,
                                PayPalCurrencyField $tax = null, string $description = null, string $sku = null,
                                string              $url = null, string $category = null, string $image_url = null,
                                PayPalUPCField      $upc = null)
    {
        $this->name = $name;
        $this->quantity = $quantity;
        $this->description = $description;
        $this->sku = $sku;
        $this->url = $url;
        $this->category = $category;
        $this->image_url = $image_url;
        $this->unit_amount = $unit_amount;
        $this->tax = $tax;
        $this->upc = $upc;
    }


}

/**
 * Used for any object that has currency_code and value fields
 */
class PayPalCurrencyField extends JsonNoEmptyFieldSerializable
{
    /**
     * @var string (required) (3 chars) ISO-4217 currency code
     */
    public $currency_code;

    /**
     * @var string (required) (0-32 chars) Integer or decimal
     */
    public $value;

    /**
     * @param string $currency_code
     * @param string $value
     */
    public function __construct(string $currency_code, string $value)
    {
        $this->currency_code = $currency_code;
        $this->value = $value;
    }


}

class PayPalUPCField extends JsonNoEmptyFieldSerializable
{
    /**
     * @var string (required) (1-5 chars) The UPC type.
     * UPC Type must match format "^[0-9A-Z_-]+$"
     */
    public $type;
    /**
     * @var string (required) (6-17 chars) The UPC product code of the item
     */
    public $code;
}

class PayPalItemsTotal extends PayPalCurrencyField
{
    /**
     * @var PayPalBreakdown Breakdown of the amount
     */
    public $breakdown;

    /**
     * @param string $currency_code The currency code
     * @param string $value The total of the order, including tax
     * @param PayPalBreakdown|null $breakdown Optional. Breakdown of the order. Required if {@see PayPalItem::$unit_amount} or {@see PayPalItem::$quantity} is specified for a {@see PayPalOrder}
     */
    public function __construct(string $currency_code, string $value, PayPalBreakdown $breakdown = null)
    {
        parent::__construct($currency_code, $value);
        $this->breakdown = $breakdown;
    }

}

class PayPalBreakdown extends JsonNoEmptyFieldSerializable
{
    /**
     * @var PayPalCurrencyField Subtotal for all items in the PayPalPurchaseUnit. Must equal the sum of (items[].unit_amount * items[].quantity) for all items
     */
    public $item_total;

    /**
     * @var PayPalCurrencyField The total shipping fee for all items in the PayPalPurchaseUnit
     */
    public $shipping;

    /**
     * @var PayPalCurrencyField The handling fee for all items in the PayPalPurchaseUnit
     */
    public $handling;

    /**
     * @var PayPalCurrencyField The total tax for all items in the PayPalPurchaseUnit. Must equal the sum (items[].tax * items[].quantity) for all items
     */
    public $tax_total;
    //public $insurance; // commented out because we don't provide insurance
    /**
     * @var PayPalCurrencyField The shipping discount for all items in the PayPalPurchaseUnit
     */
    public $shipping_discount;
    /**
     * @var PayPalCurrencyField The discount for all items in the PayPalPurchaseUnit
     */
    public $discount;

    /**
     * @param PayPalCurrencyField $item_total
     * @param PayPalCurrencyField|null $tax_total
     * @param PayPalCurrencyField|null $shipping
     * @param PayPalCurrencyField|null $handling
     * @param PayPalCurrencyField|null $shipping_discount
     * @param PayPalCurrencyField|null $discount
     */
    public function __construct(PayPalCurrencyField $item_total, PayPalCurrencyField $tax_total = null, PayPalCurrencyField $shipping = null, PayPalCurrencyField $handling = null, PayPalCurrencyField $shipping_discount = null, PayPalCurrencyField $discount = null)
    {
        $this->item_total = $item_total;
        $this->shipping = $shipping;
        $this->handling = $handling;
        $this->tax_total = $tax_total;
        $this->shipping_discount = $shipping_discount;
        $this->discount = $discount;
    }

}

class PayPalMerchant extends JsonNoEmptyFieldSerializable
{
    /**
     * @var string (3-254 chars) The email address of the merchant.
     * See {@see https://developer.paypal.com/docs/api/orders/v2/#orders_create PayPal's API documentation } for
     *  the specific RegEx pattern used, under Request body scheme, purchase_units.payee.email_address
     */
    public $email_address;
    /**
     * @var string (13 chars) The PayPal account ID of the merchant
     */
    public $merchant_id;
}

class PayPalPaymentInstruction extends JsonNoEmptyFieldSerializable
{
    /**
     * @var PayPalPlatformFee[] An array of various fees, commissions, tips, or donations.
     *  This field is only applicable to merchants that been enabled for PayPal Complete Payments Platform for Marketplaces and Platforms capability.
     */
    public $platform_fees;
    /**
     * @var string (1-20 chars) This field is only enabled for selected merchants/partners to use and provides the
     *  ability to trigger a specific pricing rate/plan for a payment transaction
     */
    public $payee_pricing_tier_id;
    /**
     * @var string (1-4000 chars) FX identifier generated returned by PayPal to be used for payment processing in
     *  order to honor FX rate (for eligible integrations) to be used when amount is settled/received into the payee
     *  account
     */
    public $payee_receivable_fx_rate_id;
    /**
     * @var string (1-16 chars) The funds that are held payee by the marketplace/platform. This field is only
     *  applicable to merchants that been enabled for PayPal Complete Payments Platform for Marketplaces and Platforms
     *  capability.
     * Must be of value "INSTANT" or "DELAYED". Defaults to INSTANT if not specified
     */
    public $disbursement_mode;

}

// todo may rename if used elsewhere
class PayPalPlatformFee extends JsonNoEmptyFieldSerializable
{
    /**
     * @var PayPalCurrencyField (required) The fee for this transaction
     */
    public $amount;
    /**
     * @var PayPalMerchant The recipient of the fee for this transaction. If omitted, the default is the API caller
     */
    public $payee;
}

class PayPalShipping extends JsonNoEmptyFieldSerializable
{
    /**
     * @var string (1-255 chars) A classification for the method of purchase fulfillment. Only one of $type and $options should be specified.
     * Valid values are "SHIPPING", "PICKUP_IN_PERSON", "PICKUP_IN_STORE", and "PICKUP_FROM_PERSON"
     */
    public $type;
    /**
     * @var PayPalShippingOption[] (0-10 items) An array of shipping options that the payee or merchant offers to the
     *  payer to ship or pick up their items.
     */
    public $options;
    /**
     * @var PayPalName The name of the person to whom to ship the items. Only supports {@see PayPalName::$full_name}
     */
    public $name;
    /**
     * @var PayPalPhoneNumber The phone number of the recipient of the shipped items, which may belong to either the
     *  payer, or an alternate contact, for delivery
     */
    public $phone_number;
    /**
     * @var PayPalAddress The address of the person to whom to ship the items. Supports only the
     * {@see PayPalAddress::$address_line_1}, {@see PayPalAddress::$address_line_2},
     * {@see PayPalAddress::$admin_area_1}, {@see PayPalAddress::$admin_area_2}, {@see PayPalAddress::$postal_code},
     * and {@see PayPalAddress::$country_code} properties
     */
    public $address;

    /**
     * @param PayPalName $name Only supports the {@see PayPalName::$full_name} property
     * @param PayPalPhoneNumber $phone_number Only supports the {@see PayPalAddress::$address_line_1}, {@see PayPalAddress::$address_line_2},
     *  {@see PayPalAddress::$admin_area_1}, {@see PayPalAddress::$admin_area_2}, {@see PayPalAddress::$postal_code},
     *  and {@see PayPalAddress::$country_code} properties
     * @param PayPalAddress $address
     * @param string|null $type Only set $type or $options
     * @param PayPalShippingOption[] $options Only set $type or $options
     */
    public function __construct(PayPalName $name, PayPalPhoneNumber $phone_number, PayPalAddress $address, string $type = null, array $options = null)
    {
        $this->type = $type;
        $this->options = $options;
        $this->name = $name;
        $this->phone_number = $phone_number;
        $this->address = $address;
    }

}

class PayPalShippingOption extends JsonNoEmptyFieldSerializable
{
    /**
     * @var string (required) (0-127 chars) A unique ID that identifies a payer-selected shipping option.
     */
    public $id;

    /**
     * @var string (required) (0-127 chars) A description that the payer sees, which helps them choose an
     *  appropriate shipping option (e.g., FedEx Express Saver)
     */
    public $label;
    /**
     * @var bool (required) If true,it represents the shipping option that the payee or merchant expects to be
     *  pre-selected for the payer when they first view the {@see PayPalShipping::$options} in the PayPal Checkout
     *  experience. As part of the response if a {@see PayPalShippingOption} contains selected=true, it represents
     *  the shipping option that the payer selected during the course of checkout with PayPal.
     *  Only one option can be set to selected=true
     */
    public $selected;
    /**
     * @var string A classification for the method of purchase fulfillment.
     *  Valid values are "SHIPPING", "PICKUP_IN_PERSON", "PICKUP_IN_STORE", and "PICKUP_FROM_PERSON"
     */
    public $type;
    /**
     * @var PayPalCurrencyField The shipping cost for the selected option
     */
    public $amount;
}

// todo: this object has more options
class PayPalName extends JsonNoEmptyFieldSerializable
{
    /**
     * @var string (0-300 chars) When the party is a person, the party's full name.
     */
    public $full_name;

    /**
     * @var string (0-140 chars) When the party is a person, the party's given name
     */
    public $given_name;

    /**
     * @var string (0-140 chars) When the party is a person, the party's surname or family name. Also known as the
     *  last name. Required when the party is a person. Use also to store multiple surnames including the matronymic, or
     *  mother's, surname.
     */
    public $surname;

    /**
     * @param string|null $full_name
     * @param string|null $given_name
     * @param string|null $surname
     */
    public function __construct(string $full_name = null, string $given_name = null, string $surname = null)
    {
        $this->full_name = $full_name;
        $this->given_name = $given_name;
        $this->surname = $surname;
    }

}

/**
 * Not to be confused with {@see PayPalPhoneNumber}
 */
class PayPalPhone extends JsonNoEmptyFieldSerializable
{
    /**
     * @var string Valid values: "FAX", "HOME", "MOBILE", "OTHER", "PAGER"
     */
    public $phone_type;

    /**
     * @var PayPalPhoneNumber The phone number, in its canonical international E.164 numbering plan format. Supports
     * only the {@see PayPalPhoneNumber::$national_number} property.
     */
    public $phone_number;
}

/**
 * Not to be confused with {@see PayPalPhone}, which uses this class
 */
class PayPalPhoneNumber extends JsonNoEmptyFieldSerializable
{
    /**
     * @var string (1-3 chars) In E.164 numbering plan format (numbers only)
     */
    public $country_code;
    /**
     * @var string (1-14 chars) In E.164 numbering plan format (numbers only)
     */
    public $national_number;

    /**
     * @param string $country_code
     * @param string $national_number
     */
    public function __construct(string $country_code, string $national_number)
    {
        $this->country_code = $country_code;
        $this->national_number = $national_number;
    }

}

class PayPalAddress extends JsonNoEmptyFieldSerializable
{
    /**
     * @var string (0-300 chars) The first line of the address, such as number and street, for example, 173 Drury Lane.
     *  Needed for data entry, and Compliance and Risk checks. This field needs to pass the full address
     */
    public $address_line_1;

    /**
     * @var string (0-120 chars) The second line of the address, for example, a suite or apartment number
     */
    public $address_line_2;

    /**
     * @var string (0-300 chars) The highest-level sub-division in a country, which is usually a province, state, or
     * ISO-3166-2 subdivision. This data is formatted for postal delivery, for example, CA and not California.
     */
    public $admin_area_1;

    /**
     * @var string (0-120 chars) A city, town, or village. Smaller than {@see PayPalAddress::$admin_area_1}
     */
    public $admin_area_2;
    /**
     * @var string (0-60 chars) The postal code, which is the ZIP code or equivalent. Typically required for countries
     *  with a postal code or an equivalent
     */
    public $postal_code;

    /**
     * @var string (required) (2 chars) The 2-character ISO 3166-1 code that identifies the country or region
     */
    public $country_code;

    /**
     * @param string $address_line_1
     * @param string $admin_area_1
     * @param string $country_code
     * @param string|null $postal_code
     * @param string|null $address_line_2
     * @param string|null $admin_area_2
     */
    public function __construct(string $address_line_1, string $admin_area_1, string $country_code, string $postal_code = null, string $address_line_2 = null, string $admin_area_2 = null)
    {
        $this->address_line_1 = $address_line_1;
        $this->address_line_2 = $address_line_2;
        $this->admin_area_1 = $admin_area_1;
        $this->admin_area_2 = $admin_area_2;
        $this->postal_code = $postal_code;
        $this->country_code = $country_code;
    }

}

class PayPalTaxInfo extends JsonNoEmptyFieldSerializable
{
    /**
     * @var string (required) (1-14 chars) The customer's tax ID value
     */
    public $tax_id;
    /**
     * @var string (required) (1-14 chars) The customer's tax ID type. Valid values: "BR_CPF" (individual), BR_CNPJ (business)
     */
    public $tax_id_type;
}

class PayPalWalletAttributes extends JsonNoEmptyFieldSerializable
{
    //todo: implement this
    public $customer;
    public $vault;
}

class PayPalToken extends JsonNoEmptyFieldSerializable
{
    /**
     * @var string (required) (1-255 chars) The PayPal-generated ID for the token.
     */
    public $id;
    /**
     * @var string (required) (1-255 chars) The tokenization method that generated the ID. Valid values: "BILLING_AGREEMENT"
     */
    public $type;
}
