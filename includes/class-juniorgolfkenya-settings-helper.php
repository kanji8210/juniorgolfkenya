<?php
/**
 * Settings Helper Functions
 *
 * Centralized access to plugin settings throughout the application
 *
 * @link       https://github.com/kanji8210/juniorgolfkenya
 * @since      1.0.0
 *
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/includes
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings Helper Class
 *
 * Provides static methods to access plugin settings with defaults
 */
class JuniorGolfKenya_Settings_Helper {
    
    /**
     * Get junior age restrictions from settings
     *
     * @since  1.0.0
     * @return array Array with 'min' and 'max' keys
     */
    public static function get_age_restrictions() {
        $settings = get_option('jgk_junior_settings', array());
        
        return array(
            'min' => isset($settings['min_age']) ? (int)$settings['min_age'] : 2,
            'max' => isset($settings['max_age']) ? (int)$settings['max_age'] : 17
        );
    }
    
    /**
     * Get minimum age for junior membership
     *
     * @since  1.0.0
     * @return int Minimum age (default: 2)
     */
    public static function get_min_age() {
        $restrictions = self::get_age_restrictions();
        return $restrictions['min'];
    }
    
    /**
     * Get maximum age for junior membership
     *
     * @since  1.0.0
     * @return int Maximum age (default: 17)
     */
    public static function get_max_age() {
        $restrictions = self::get_age_restrictions();
        return $restrictions['max'];
    }
    
    /**
     * Check if an age is valid for junior membership
     *
     * @since  1.0.0
     * @param  int $age The age to check
     * @return bool True if valid, false otherwise
     */
    public static function is_valid_age($age) {
        $restrictions = self::get_age_restrictions();
        return $age >= $restrictions['min'] && $age <= $restrictions['max'];
    }
    
    /**
     * Get pricing settings
     *
     * @since  1.0.0
     * @return array Pricing settings with defaults
     */
    public static function get_pricing_settings() {
        $default_fee = self::get_default_membership_fee();
        $defaults = array(
            'subscription_price' => $default_fee,
            'currency' => 'KSH',
            'currency_symbol' => 'KSh',
            'payment_frequency' => 'yearly'
        );
        
        return wp_parse_args(get_option('jgk_pricing_settings', array()), $defaults);
    }
    
    /**
     * Get subscription price
     *
     * @since  1.0.0
     * @return float Subscription price
     */
    public static function get_subscription_price() {
        $settings = self::get_pricing_settings();
        return (float)$settings['subscription_price'];
    }
    
    /**
     * Get currency code
     *
     * @since  1.0.0
     * @return string Currency code (e.g., 'KSH')
     */
    public static function get_currency() {
        $settings = self::get_pricing_settings();
        return $settings['currency'];
    }
    
    /**
     * Get currency symbol
     *
     * @since  1.0.0
     * @return string Currency symbol (e.g., 'KSh')
     */
    public static function get_currency_symbol() {
        $settings = self::get_pricing_settings();
        return $settings['currency_symbol'];
    }
    
    /**
     * Get formatted price
     *
     * @since  1.0.0
     * @param  float|null $price Optional custom price, uses default if not provided
     * @return string Formatted price (e.g., 'KSh 5,000.00')
     */
    public static function get_formatted_price($price = null) {
        if ($price === null) {
            $price = self::get_subscription_price();
        }
        
        $symbol = self::get_currency_symbol();
        return $symbol . ' ' . number_format($price, 2);
    }

    /**
     * Get default membership fee from general settings
     *
     * @since  1.0.0
     * @return float Default membership fee
     */
    public static function get_default_membership_fee() {
        $settings = self::get_general_settings();
        $fee = isset($settings['default_membership_fee']) ? (float)$settings['default_membership_fee'] : 0;

        return $fee > 0 ? $fee : 1050;
    }

    /**
     * Get currency code from general settings
     *
     * @since  1.0.0
     * @return string Currency code
     */
    public static function get_general_currency() {
        $settings = self::get_general_settings();
        return !empty($settings['currency']) ? $settings['currency'] : 'KSH';
    }
    
    /**
     * Get general settings
     *
     * @since  1.0.0
     * @return array General settings with defaults
     */
    public static function get_general_settings() {
        $defaults = array(
            'organization_name' => 'Junior Golf Kenya',
            'organization_email' => get_option('admin_email'),
            'organization_phone' => '',
            'organization_address' => '',
            'default_membership_fee' => 1050,
            'currency' => 'KSH',
            'timezone' => 'Africa/Nairobi'
        );
        
        return wp_parse_args(get_option('jgk_general_settings', array()), $defaults);
    }
    
    /**
     * Get organization name
     *
     * @since  1.0.0
     * @return string Organization name
     */
    public static function get_organization_name() {
        $settings = self::get_general_settings();
        return $settings['organization_name'];
    }
    
    /**
     * Get organization email
     *
     * @since  1.0.0
     * @return string Organization email
     */
    public static function get_organization_email() {
        $settings = self::get_general_settings();
        return $settings['organization_email'];
    }
    
    /**
     * Get HTML5 date max attribute for birthdate (minimum age)
     * For age validation in date inputs
     *
     * @since  1.0.0
     * @return string Date in Y-m-d format
     */
    public static function get_birthdate_max() {
        $min_age = self::get_min_age();
        return date('Y-m-d', strtotime("-{$min_age} years"));
    }
    
    /**
     * Get HTML5 date min attribute for birthdate (maximum age)
     * For age validation in date inputs
     *
     * @since  1.0.0
     * @return string Date in Y-m-d format
     */
    public static function get_birthdate_min() {
        $max_age = self::get_max_age();
        // Add 1 year because if max is 17, we want birthdates from 18 years ago forward
        return date('Y-m-d', strtotime("-" . ($max_age + 1) . " years +1 day"));
    }
    
    /**
     * Calculate age from birthdate
     *
     * @since  1.0.0
     * @param  string $birthdate Birthdate in Y-m-d format
     * @return int|false Age in years, or false on error
     */
    public static function calculate_age($birthdate) {
        if (empty($birthdate)) {
            return false;
        }
        
        $birth = new DateTime($birthdate);
        $today = new DateTime('today');
        $age = $birth->diff($today)->y;
        
        return $age;
    }
    
    /**
     * Validate birthdate against age restrictions
     *
     * @since  1.0.0
     * @param  string $birthdate Birthdate in Y-m-d format
     * @return array Array with 'valid' (bool) and 'message' (string) keys
     */
    public static function validate_birthdate($birthdate) {
        $age = self::calculate_age($birthdate);
        
        if ($age === false) {
            return array(
                'valid' => false,
                'message' => 'Invalid birthdate format.'
            );
        }
        
        $restrictions = self::get_age_restrictions();
        
        if ($age < $restrictions['min']) {
            return array(
                'valid' => false,
                'message' => sprintf(
                    'Member must be at least %d years old. Current age: %d years.',
                    $restrictions['min'],
                    $age
                )
            );
        }
        
        if ($age > $restrictions['max']) {
            return array(
                'valid' => false,
                'message' => sprintf(
                    'Member must be %d years old or younger. Current age: %d years. This system is for juniors only.',
                    $restrictions['max'],
                    $age
                )
            );
        }
        
        return array(
            'valid' => true,
            'message' => 'Age is valid for junior membership.'
        );
    }
}
