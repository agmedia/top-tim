<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'languages' => [
        'title' => 'Languages',
        'table_title' => 'Language Name',
        'main_select' => 'Select default language',
        'new' => 'Add new language',
        'empty_list' => 'You have no languages entered...',
        'main_lang' => 'Default language',
        'edit_title' => 'Edit language',
        'input_title' => 'Title',
        'code_title' => 'Code',
        'status_title' => 'Status',
        'main_title' => 'Default lamguage',
    ],

    'geozone' => [
        'title' => 'Geo Zone',
        'new' => 'Add new',
        'edit_title' => 'Edit',
        'input_title' => 'Title',
        'status_title' => 'Status',
        'list' => 'List',
        'main_title' => 'Geo Zone edit',
        'back' => 'Back',
        'enter_title' => 'Enter title...',
        'description' => 'Description',
        'description_if_needed' => 'Optionally',
        'countries' => 'Countries',
        'list_countries' => 'List of countries within the geo zone',
        'delete' => 'Delete',
        'select_country' => 'Select Country...',
    ],

    'statuses' => [
        'title' => 'Order Statuses',
        'new' => 'Add new',
        'edit_title' => 'Edit',
        'input_title' => 'Title',
        'status_title' => 'Status',
        'list' => 'List',
        'color' => 'Color',
        'br' => 'No.',
        'sort_order' => 'Sort Order',
        'main_title' => 'Order Status',
        'back' => 'Back',
        'enter_title' => 'Enter title...',
        'delete' => 'Delete',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete_status' => 'Delete status',
        'delete_shure' => 'Are you sure you want to delete the status?',
        'select_status' => 'Select status color...',

    ],

    'tax' => [
        'title' => 'Taxes',
        'new' => 'Add new',
        'edit_title' => 'Edit',
        'input_title' => 'Title',
        'br' => 'No.',
        'status_title' => 'Status',
        'list' => 'List',
        'sort_order' => 'Sort Order',
        'main_title' => 'Tax',
        'back' => 'Back',
        'delete' => 'Delete',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete_tax' => 'Delete tax',
        'delete_shure' => 'Are you sure you want to delete tax?',


    ],

    'currency' => [
        'title' => 'Currency',
        'new' => 'Add new',
        'select_main' => 'Select default currency',
        'edit_title' => 'Edit',
        'input_title' => 'Title',
        'code' => 'Code',
        'value' => 'Value',
        'decimal' => 'Decimal places',
        'symbol_left' => 'Symbol left',
        'symbol_right' => 'Symbol right',
        'default_currency' => 'Default currency',
        'br' => 'No.',
        'status_title' => 'Status',
        'list' => 'List',
        'sort_order' => 'Sort Order',
        'main_title' => 'Tax',
        'back' => 'Back',
        'delete' => 'Delete',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete_tax' => 'Delete currency',
        'delete_shure' => 'Are you sure you want to delete currency?',


    ],

    'payments'=> [

        'title' => 'Payment methods',
        'edit_title' => 'Edit',
        'input_title' => 'Title',
        'status_title' => 'Status',
        'code' => 'Code',
        'sort_order' => 'Sort order',
        'list' => 'List',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'empty_list' => 'No payment gateways...',
        'cod' => 'COD (Cash on Delivery)',
        'bank' => 'Bank transfer',
        'wspay' => 'WSPay',
        'payway' => 'T-Com Payway',
        'corvus' => 'Corvus Pay',
        'min_order_amount' => 'Minimum order amount',
        'fee_amount' => 'Fee amount',
        'geo_zone' => 'Geo zone',
        'geo_zone_label' => '(Geo zone to which payment applies.)',
        'short_desc' => 'Short description',
        'short_desc_label' => '(Displayed when selecting payment.)',
        'long_desc' => 'Long description',
        'long_desc_label' => '(If required. Displayed if payment is selected during purchase.)',
        'chars' => 'chars',
        'select_geo' => 'Select geo zone',
    ],

    'calendar' => [
        'options' => 'Calendar Options',
        'drag' => 'Drag and drop events on the calendar',
        'from' => 'od',
        'to' => 'do',
    ],
    //
    'order' => [
        'title' => 'Order',
        'orders' => 'Orders',
        'list' => 'Orders List',
        'filter' => 'Filter',
        'all' => 'All',
        'search_placeholder' => 'Search by order number, customer, apartment or order date...',
        'no_orders' => 'You have no current orders..',
        'date' => 'Date',
        'apartment' => 'Apartment',
        'customer' => 'Customer',
        'details' => 'Details',
        //
        'edit' => 'Edit Order',
        'new' => 'Create New Order',
        'info' => 'Basic Order Info',
        'customer' => 'Customer',
        'persons' => 'Persons',
        'adults' => 'Adults',
        'children' => 'Children',
        'babies' => 'Babies',
        'regular_days' => 'Regular days',
        'weekends' => 'Weekends',
        'change_date' => 'Change date',
        'customer_registered' => 'Customer is registered',
        'customer_not_registered' => 'Customer is not registered',
        'name' => 'Name',
        'lastname' => 'Surname',
        'email' => 'Email',
        'phone' => 'Phone',
        'payments' => 'Payment',
        'select_payments' => 'Select payment method..',
        'amount' => 'Amount',
        'items_title' => 'Order Items & Total',
        'history' => 'Order History',
        'comment' => 'Comment',
        'add_comment' => 'Add Comment',
        'change_status' => 'Change Selected Statuses...',
        'no_change_status' => 'Dont Change Status',
        'paid_amount' => 'Paid Amount',
        'payment_url' => 'Order Payment URL',
        'origin_select' => 'Filter origin..',
        'status_select' => 'Filter status..',
        'selfcheckins' => 'SelfCheckins',
        'booking' => 'Booking',
        'airbnb' => 'Airbnb'
    ],

    'deposit' => [
        'title' => 'Payments',
        'new' => 'Create New Payment',
        'copy_url' => 'Copy Payment URL',
        'list' => 'Payments List',
        'scope' => 'Purpose',
        'order_number' => 'Order Number',
        'no_deposits' => 'You have no current payments..'
    ],

    // GENERALS
    'save_success' => 'Successfully saved!',
    'save_failure' => 'Oops..! There has been error with saving. Try again or contact your administrator!',
    'select_status' => 'Select Status',
    'sort' => 'Sort',
    'type_error' => 'Please, check input!',
    'total' => 'Total',
    'days' => 'Days',

    // CALENDAR
    'calendar_make_apartment_error' => 'Select Apartment!',

    'title' => 'Apartman',
    'titles' => 'Apartmani',
    'new' => 'Novi apartman',
    'all' => 'Svi apartmani',
    'edit' => 'Uredite apartman',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'my_account' => 'My Profile',

];
