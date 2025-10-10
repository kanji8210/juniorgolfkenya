<?php

/**
 * Fired during plugin activation
 *
 * @link       https://github.com/kanji8210/juniorgolfkenya
 * @since      1.0.0
 *
 * @package    JuniorGolfKenya
 * @subpackage JuniorGolfKenya/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 */
class JuniorGolfKenya_Activator {

    /**
     * Short Description.
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate() {
        self::create_tables();
        self::create_roles_and_capabilities();
        self::create_pages();
        self::set_default_options();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables
     *
     * @since    1.0.0
     */
    private static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Members table
        $table_members = $wpdb->prefix . 'jgk_members';
        $sql_members = "CREATE TABLE $table_members (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            membership_number varchar(50) NOT NULL UNIQUE,
            membership_type varchar(50) NOT NULL,
            status varchar(20) DEFAULT 'active',
            date_joined datetime DEFAULT CURRENT_TIMESTAMP,
            date_expires datetime,
            emergency_contact_name varchar(100),
            emergency_contact_phone varchar(20),
            club_affiliation varchar(100),
            date_of_birth date,
            parental_consent boolean DEFAULT false,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY membership_number (membership_number),
            KEY status (status)
        ) $charset_collate;";

        // Memberships table (for tracking membership history)
        $table_memberships = $wpdb->prefix . 'jgk_memberships';
        $sql_memberships = "CREATE TABLE $table_memberships (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            member_id mediumint(9) NOT NULL,
            plan_id mediumint(9) NOT NULL,
            status varchar(20) DEFAULT 'active',
            start_date datetime NOT NULL,
            end_date datetime,
            auto_renew boolean DEFAULT true,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY member_id (member_id),
            KEY plan_id (plan_id),
            KEY status (status)
        ) $charset_collate;";

        // Plans table
        $table_plans = $wpdb->prefix . 'jgk_plans';
        $sql_plans = "CREATE TABLE $table_plans (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            description text,
            price decimal(10,2) NOT NULL,
            currency varchar(3) DEFAULT 'USD',
            billing_period varchar(20) DEFAULT 'yearly',
            features text,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY status (status)
        ) $charset_collate;";

        // Payments table
        $table_payments = $wpdb->prefix . 'jgk_payments';
        $sql_payments = "CREATE TABLE $table_payments (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            member_id mediumint(9) NOT NULL,
            membership_id mediumint(9),
            amount decimal(10,2) NOT NULL,
            currency varchar(3) DEFAULT 'USD',
            payment_method varchar(50),
            payment_gateway varchar(50),
            transaction_id varchar(100),
            status varchar(20) DEFAULT 'pending',
            payment_date datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY member_id (member_id),
            KEY membership_id (membership_id),
            KEY status (status),
            KEY transaction_id (transaction_id)
        ) $charset_collate;";

        // Competition entries table
        $table_competition_entries = $wpdb->prefix . 'jgk_competition_entries';
        $sql_competition_entries = "CREATE TABLE $table_competition_entries (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            member_id mediumint(9) NOT NULL,
            competition_name varchar(200) NOT NULL,
            competition_date date,
            entry_date datetime DEFAULT CURRENT_TIMESTAMP,
            result varchar(100),
            position int,
            score varchar(50),
            status varchar(20) DEFAULT 'registered',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY member_id (member_id),
            KEY competition_date (competition_date),
            KEY status (status)
        ) $charset_collate;";

        // Certifications table
        $table_certifications = $wpdb->prefix . 'jgk_certifications';
        $sql_certifications = "CREATE TABLE $table_certifications (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            member_id mediumint(9) NOT NULL,
            certification_name varchar(200) NOT NULL,
            certification_type varchar(100),
            issued_date date,
            expiry_date date,
            file_url varchar(500),
            issuing_authority varchar(200),
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY member_id (member_id),
            KEY expiry_date (expiry_date),
            KEY status (status)
        ) $charset_collate;";

        // Audit log table
        $table_audit_log = $wpdb->prefix . 'jgk_audit_log';
        $sql_audit_log = "CREATE TABLE $table_audit_log (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED,
            member_id mediumint(9),
            action varchar(100) NOT NULL,
            object_type varchar(50),
            object_id mediumint(9),
            old_values text,
            new_values text,
            ip_address varchar(45),
            user_agent text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY member_id (member_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_members);
        dbDelta($sql_memberships);
        dbDelta($sql_plans);
        dbDelta($sql_payments);
        dbDelta($sql_competition_entries);
        dbDelta($sql_certifications);
        dbDelta($sql_audit_log);

        // Create additional tables for roles functionality
        self::create_additional_tables();

        // Insert default plan
        self::insert_default_data();
    }

    /**
     * Create additional tables for roles and coaching functionality
     *
     * @since    1.0.0
     */
    private static function create_additional_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();

        // Coach ratings table
        $table_coach_ratings = $wpdb->prefix . 'jgf_coach_ratings';
        $sql_coach_ratings = "CREATE TABLE $table_coach_ratings (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            coach_user_id bigint(20) UNSIGNED NOT NULL,
            member_id mediumint(9) NOT NULL,
            rating smallint(6) NOT NULL,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY coach_user_id (coach_user_id),
            KEY member_id (member_id),
            KEY rating (rating)
        ) $charset_collate;";

        // Recommendations table
        $table_recommendations = $wpdb->prefix . 'jgf_recommendations';
        $sql_recommendations = "CREATE TABLE $table_recommendations (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            recommender_user_id bigint(20) UNSIGNED NOT NULL,
            member_id mediumint(9) NOT NULL,
            type enum('competition','training','role','other') NOT NULL,
            payload json,
            status varchar(32) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            handled_by bigint(20) UNSIGNED,
            handled_at datetime,
            PRIMARY KEY (id),
            KEY recommender_user_id (recommender_user_id),
            KEY member_id (member_id),
            KEY type (type),
            KEY status (status)
        ) $charset_collate;";

        // Training schedules table
        $table_training_schedules = $wpdb->prefix . 'jgf_training_schedules';
        $sql_training_schedules = "CREATE TABLE $table_training_schedules (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            coach_user_id bigint(20) UNSIGNED NOT NULL,
            club_id mediumint(9),
            title varchar(200) NOT NULL,
            description text,
            start_datetime datetime NOT NULL,
            end_datetime datetime NOT NULL,
            capacity int DEFAULT 20,
            location varchar(255),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY coach_user_id (coach_user_id),
            KEY start_datetime (start_datetime),
            KEY club_id (club_id)
        ) $charset_collate;";

        // Role requests table
        $table_role_requests = $wpdb->prefix . 'jgf_role_requests';
        $sql_role_requests = "CREATE TABLE $table_role_requests (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            requester_user_id bigint(20) UNSIGNED NOT NULL,
            requested_role varchar(64) NOT NULL,
            reason text,
            status varchar(32) DEFAULT 'pending',
            reviewed_by bigint(20) UNSIGNED,
            reviewed_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY requester_user_id (requester_user_id),
            KEY requested_role (requested_role),
            KEY status (status)
        ) $charset_collate;";

        // Coach profiles table
        $table_coach_profiles = $wpdb->prefix . 'jgf_coach_profiles';
        $sql_coach_profiles = "CREATE TABLE $table_coach_profiles (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL UNIQUE,
            qualifications text,
            specialties text,
            bio text,
            license_docs_ref varchar(500),
            verification_status varchar(32) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY verification_status (verification_status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_coach_ratings);
        dbDelta($sql_recommendations);
        dbDelta($sql_training_schedules);
        dbDelta($sql_role_requests);
        dbDelta($sql_coach_profiles);
    }

    /**
     * Create custom roles and capabilities during plugin activation
     *
     * @since    1.0.0
     */
    private static function create_roles_and_capabilities() {
        // JGF Member Role
        if (!get_role('jgf_member')) {
            add_role('jgf_member', 'JGF Member', array(
                'read' => true,
                'view_member_dashboard' => true,
                'manage_own_profile' => true,
            ));
        }

        // JGF Coach Role
        if (!get_role('jgf_coach')) {
            add_role('jgf_coach', 'JGF Coach', array(
                'read' => true,
                'view_member_dashboard' => true,
                'coach_rate_player' => true,
                'coach_recommend_competition' => true,
                'coach_recommend_training' => true,
                'manage_own_profile' => true,
            ));
        }

        // JGF Staff Role
        if (!get_role('jgf_staff')) {
            add_role('jgf_staff', 'JGF Staff', array(
                'read' => true,
                'view_member_dashboard' => true,
                'edit_members' => true,
                'manage_payments' => true,
                'manage_competitions' => true,
                'view_reports' => true,
                'approve_role_requests' => true,
                'manage_certifications' => true,
            ));
        }

        // Add capabilities to existing Administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $custom_caps = array(
                'view_member_dashboard',
                'edit_members',
                'manage_payments',
                'view_reports',
                'manage_competitions',
                'coach_rate_player',
                'coach_recommend_competition',
                'coach_recommend_training',
                'approve_role_requests',
                'manage_certifications'
            );
            
            foreach ($custom_caps as $cap) {
                $admin_role->add_cap($cap);
            }
        }
    }

    /**
     * Insert default data
     *
     * @since    1.0.0
     */
    private static function insert_default_data() {
        global $wpdb;

        $table_plans = $wpdb->prefix . 'jgk_plans';
        
        // Check if default plan exists
        $existing_plan = $wpdb->get_var("SELECT id FROM $table_plans WHERE name = 'Annual Membership'");
        
        if (!$existing_plan) {
            $wpdb->insert(
                $table_plans,
                array(
                    'name' => 'Annual Membership',
                    'description' => 'Standard annual membership for junior golfers',
                    'price' => 50.00,
                    'currency' => 'USD',
                    'billing_period' => 'yearly',
                    'features' => json_encode(array(
                        'Competition entry',
                        'Certification tracking',
                        'Member dashboard',
                        'Digital membership card'
                    )),
                    'status' => 'active'
                ),
                array('%s', '%s', '%f', '%s', '%s', '%s', '%s')
            );
        }
    }

    /**
     * Create required pages
     *
     * @since    1.0.0
     */
    private static function create_pages() {
        $pages = array(
            'jgk-member-portal' => array(
                'title' => 'Member Portal',
                'content' => '[jgk_member_portal]'
            ),
            'jgk-registration' => array(
                'title' => 'Become a Member',
                'content' => '[jgk_registration_form]'
            ),
            'jgk-member-verification' => array(
                'title' => 'Verify Membership',
                'content' => '[jgk_verification_widget]'
            )
        );

        foreach ($pages as $slug => $page_data) {
            $existing_page = get_page_by_path($slug);
            
            if (!$existing_page) {
                wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug
                ));
            }
        }
    }

    /**
     * Set default options
     *
     * @since    1.0.0
     */
    private static function set_default_options() {
        $default_options = array(
            'jgk_payment_gateway' => 'stripe',
            'jgk_currency' => 'KSH',
            'jgk_renewal_reminder_days' => '30,14,3',
            'jgk_grace_period_days' => '7',
            'jgk_email_notifications' => '1',
            'jgk_public_verification' => '1'
        );

        foreach ($default_options as $option_name => $option_value) {
            if (get_option($option_name) === false) {
                add_option($option_name, $option_value);
            }
        }
    }
}