<?php

use App\Support\FieldNames;

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Plain, friendly messages for every rule the forms use ("Enter your
    | mobile number", not "The phone field is required"). Field names come
    | from App\Support\FieldNames, in English and Bengali.
    |
    */

    'accepted' => 'Please tick :attribute.',
    'accepted_if' => 'The :attribute field must be accepted when :other is :value.',
    'active_url' => 'The :attribute field must be a valid URL.',
    'after' => ':Attribute must be after :date.',
    'after_or_equal' => ':Attribute must be :date or later.',
    'alpha' => 'The :attribute field must only contain letters.',
    'alpha_dash' => ':Attribute may only have letters, numbers, dashes and underscores.',
    'alpha_num' => 'The :attribute field must only contain letters and numbers.',
    'any_of' => 'The :attribute field is invalid.',
    'array' => ':Attribute is not in the right form.',
    'array_keys' => 'The :attribute field must only contain the following keys: :values.',
    'ascii' => 'The :attribute field must only contain single-byte alphanumeric characters and symbols.',
    'base64' => 'The :attribute field must be a valid Base64 string.',
    'before' => ':Attribute must be before :date.',
    'before_or_equal' => ':Attribute must be :date or earlier.',
    'between' => [
        'array' => ':Attribute must have :min to :max items.',
        'file' => ':Attribute must be :min to :max KB.',
        'numeric' => ':Attribute must be between :min and :max.',
        'string' => ':Attribute must be :min to :max characters.',
    ],
    'boolean' => ':Attribute must be yes or no.',
    'can' => 'The :attribute field contains an unauthorized value.',
    'confirmed' => 'The two :attribute entries do not match.',
    'contains' => 'The :attribute field is missing a required value.',
    'current_password' => 'The password is incorrect.',
    'date' => ':Attribute is not a real date.',
    'date_equals' => 'The :attribute field must be a date equal to :date.',
    'date_format' => ':Attribute must look like :format.',
    'decimal' => 'The :attribute field must have :decimal decimal places.',
    'declined' => 'The :attribute field must be declined.',
    'declined_if' => 'The :attribute field must be declined when :other is :value.',
    'different' => 'The :attribute field and :other must be different.',
    'digits' => ':Attribute must be :digits digits.',
    'digits_between' => ':Attribute must be :min to :max digits.',
    'dimensions' => 'The :attribute field has invalid image dimensions.',
    'distinct' => ':Attribute is repeated.',
    'doesnt_contain' => 'The :attribute field must not contain any of the following: :values.',
    'doesnt_end_with' => 'The :attribute field must not end with one of the following: :values.',
    'doesnt_start_with' => 'The :attribute field must not start with one of the following: :values.',
    'email' => 'Enter a real email address, like name@example.com.',
    'encoding' => 'The :attribute field must be encoded in :encoding.',
    'ends_with' => 'The :attribute field must end with one of the following: :values.',
    'enum' => 'Choose one of the options for :attribute.',
    'exists' => 'Choose one of the options for :attribute.',
    'extensions' => 'The :attribute field must have one of the following extensions: :values.',
    'file' => ':Attribute must be a file.',
    'filled' => 'The :attribute field must have a value.',
    'gt' => [
        'array' => 'The :attribute field must have more than :value items.',
        'file' => 'The :attribute field must be greater than :value kilobytes.',
        'numeric' => 'The :attribute field must be greater than :value.',
        'string' => 'The :attribute field must be greater than :value characters.',
    ],
    'gte' => [
        'array' => 'The :attribute field must have :value items or more.',
        'file' => 'The :attribute field must be greater than or equal to :value kilobytes.',
        'numeric' => 'The :attribute field must be greater than or equal to :value.',
        'string' => 'The :attribute field must be greater than or equal to :value characters.',
    ],
    'hex_color' => 'The :attribute field must be a valid hexadecimal color.',
    'image' => ':Attribute must be a photo (JPG, PNG or WebP).',
    'in' => 'Choose one of the options for :attribute.',
    'in_array' => 'The :attribute field must exist in :other.',
    'in_array_keys' => 'The :attribute field must contain at least one of the following keys: :values.',
    'integer' => ':Attribute must be a whole number.',
    'ip' => 'The :attribute field must be a valid IP address.',
    'ipv4' => 'The :attribute field must be a valid IPv4 address.',
    'ipv6' => 'The :attribute field must be a valid IPv6 address.',
    'json' => 'The :attribute field must be a valid JSON string.',
    'list' => 'The :attribute field must be a list.',
    'lowercase' => 'The :attribute field must be lowercase.',
    'lt' => [
        'array' => 'The :attribute field must have less than :value items.',
        'file' => 'The :attribute field must be less than :value kilobytes.',
        'numeric' => 'The :attribute field must be less than :value.',
        'string' => 'The :attribute field must be less than :value characters.',
    ],
    'lte' => [
        'array' => ':Attribute can have at most :value items.',
        'file' => ':Attribute must be at most :value KB.',
        'numeric' => ':Attribute can be at most :value.',
        'string' => ':Attribute can be at most :value characters.',
    ],
    'mac_address' => 'The :attribute field must be a valid MAC address.',
    'max' => [
        'array' => ':Attribute can have at most :max items.',
        'file' => ':Attribute must be smaller than :max KB.',
        'numeric' => ':Attribute can be at most :max.',
        'string' => ':Attribute can be at most :max characters.',
    ],
    'max_digits' => 'The :attribute field must not have more than :max digits.',
    'mimes' => ':Attribute must be one of these types: :values.',
    'mimetypes' => ':Attribute must be one of these types: :values.',
    'min' => [
        'array' => ':Attribute needs at least :min.',
        'file' => ':Attribute must be at least :min KB.',
        'numeric' => ':Attribute must be at least :min.',
        'string' => ':Attribute must be at least :min characters.',
    ],
    'min_digits' => 'The :attribute field must have at least :min digits.',
    'missing' => 'The :attribute field must be missing.',
    'missing_if' => 'The :attribute field must be missing when :other is :value.',
    'missing_unless' => 'The :attribute field must be missing unless :other is :value.',
    'missing_with' => 'The :attribute field must be missing when :values is present.',
    'missing_with_all' => 'The :attribute field must be missing when :values are present.',
    'multiple_of' => 'The :attribute field must be a multiple of :value.',
    'not_in' => 'The selected :attribute is invalid.',
    'not_regex' => 'The :attribute field format is invalid.',
    'numeric' => ':Attribute must be a number.',
    'password' => [
        'letters' => 'The :attribute field must contain at least one letter.',
        'mixed' => 'The :attribute field must contain at least one uppercase and one lowercase letter.',
        'numbers' => 'The :attribute field must contain at least one number.',
        'symbols' => 'The :attribute field must contain at least one symbol.',
        'uncompromised' => 'The given :attribute has appeared in a data leak. Please choose a different :attribute.',
    ],
    'present' => 'The :attribute field must be present.',
    'present_if' => 'The :attribute field must be present when :other is :value.',
    'present_unless' => 'The :attribute field must be present unless :other is :value.',
    'present_with' => 'The :attribute field must be present when :values is present.',
    'present_with_all' => 'The :attribute field must be present when :values are present.',
    'prohibited' => 'Something went wrong. Please try again.',
    'prohibited_if' => 'The :attribute field is prohibited when :other is :value.',
    'prohibited_if_accepted' => 'The :attribute field is prohibited when :other is accepted.',
    'prohibited_if_declined' => 'The :attribute field is prohibited when :other is declined.',
    'prohibited_unless' => 'The :attribute field is prohibited unless :other is in :values.',
    'prohibits' => 'The :attribute field prohibits :other from being present.',
    'regex' => ':Attribute is not in the right format.',
    'required' => 'Enter :attribute.',
    'required_array_keys' => 'The :attribute field must contain entries for: :values.',
    'required_if' => 'Enter :attribute when :other is :value.',
    'required_if_accepted' => 'The :attribute field is required when :other is accepted.',
    'required_if_declined' => 'The :attribute field is required when :other is declined.',
    'required_unless' => 'The :attribute field is required unless :other is in :values.',
    'required_with' => 'Enter :attribute too.',
    'required_with_all' => 'The :attribute field is required when :values are present.',
    'required_without' => 'Enter :attribute or :values.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same' => 'The :attribute field must match :other.',
    'size' => [
        'array' => ':Attribute must have :size items.',
        'file' => ':Attribute must be :size KB.',
        'numeric' => ':Attribute must be :size.',
        'string' => ':Attribute must be :size characters.',
    ],
    'starts_with' => 'The :attribute field must start with one of the following: :values.',
    'string' => ':Attribute must be text.',
    'timezone' => 'The :attribute field must be a valid timezone.',
    'unique' => 'This :attribute is already in use.',
    'uploaded' => ':Attribute could not be uploaded. Try a smaller file.',
    'uppercase' => 'The :attribute field must be uppercase.',
    'url' => 'Enter a full link, starting with https://.',
    'ulid' => 'The :attribute field must be a valid ULID.',
    'uuid' => 'Something went wrong. Please try again.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    // Fields picked from a list say "Choose …"
    'custom' => [
        ...FieldNames::choiceMessages('Choose :attribute.'),
        'upi_ref' => ['required_if' => 'Enter the UTR number of the UPI payment (12 digits, in the payment app).'],
        'utr' => ['required' => 'Enter the UTR number of the UPI payment (12 digits, in the payment app).'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => FieldNames::english(),

    // Words for :value in required_if messages
    'values' => [
        'mode' => ['upi' => 'UPI', 'cash' => 'cash'],
        'kind' => ['wedding' => 'a wedding day'],
        'via' => ['email' => 'email', 'wa' => 'WhatsApp'],
    ],

];
