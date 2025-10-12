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
     * Activate the plugin and create necessary database structures
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Create tables and track results
        $tables_created = self::create_tables();
        
        // Check and add missing columns to existing tables
        self::update_existing_tables();
        
        // Create roles and capabilities
        self::create_roles_and_capabilities();
        
        // Create pages
        self::create_pages();
        
        // Set default options
        self::set_default_options();
        
        // Verify table creation and store status
        $verification = self::verify_tables();
        
        // Store activation status for admin notice
        set_transient('jgk_activation_notice', array(
            'tables_created' => $tables_created,
            'verification' => $verification,
            'timestamp' => current_time('mysql')
        ), 60);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Update existing tables with missing columns
     * This ensures backward compatibility when adding new features
     *
     * @since    1.1.0
     */
    private static function update_existing_tables() {
        global $wpdb;
        
        $members_table = $wpdb->prefix . 'jgk_members';
        
        // Check if members table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$members_table}'");
        
        if ($table_exists !== $members_table) {
            // Table doesn't exist yet, will be created by create_tables()
            return;
        }
        
        // Get existing columns
        $columns = $wpdb->get_results("SHOW COLUMNS FROM {$members_table}");
        $column_names = array();
        foreach ($columns as $column) {
            $column_names[] = $column->Field;
        }
        
        // Check and add is_public column if missing
        if (!in_array('is_public', $column_names)) {
            $wpdb->query("
                ALTER TABLE {$members_table} 
                ADD COLUMN is_public tinyint(1) NOT NULL DEFAULT 1 
                COMMENT 'Visibilité publique: 0=privé, 1=public (DEFAULT: PUBLIC)'
                AFTER parental_consent
            ");
            
            // Log the change
            error_log('JGK Activation: Added column is_public to ' . $members_table . ' with DEFAULT 1 (PUBLIC)');
            
            // Set all existing members to PUBLIC by default
            $updated = $wpdb->query("UPDATE {$members_table} SET is_public = 1 WHERE is_public = 0");
            error_log('JGK Activation: Set ' . $updated . ' existing members to PUBLIC');
        }
        
        // Check and add club_name column if missing (for backward compatibility)
        if (!in_array('club_name', $column_names)) {
            $wpdb->query("
                ALTER TABLE {$members_table} 
                ADD COLUMN club_name varchar(100) 
                COMMENT 'Nom du club de golf'
                AFTER handicap
            ");
            
            // Copy data from club_affiliation to club_name if exists
            if (in_array('club_affiliation', $column_names)) {
                $wpdb->query("UPDATE {$members_table} SET club_name = club_affiliation WHERE club_name IS NULL");
            }
            
            error_log('JGK Activation: Added column club_name to ' . $members_table);
        }
        
        // Check and add handicap_index column if missing
        if (!in_array('handicap_index', $column_names)) {
            $wpdb->query("
                ALTER TABLE {$members_table} 
                ADD COLUMN handicap_index varchar(10) 
                COMMENT 'Index de handicap'
                AFTER club_name
            ");
            
            // Copy data from handicap to handicap_index if exists
            if (in_array('handicap', $column_names)) {
                $wpdb->query("UPDATE {$members_table} SET handicap_index = handicap WHERE handicap_index IS NULL");
            }
            
            error_log('JGK Activation: Added column handicap_index to ' . $members_table);
        }
        
        // Add index on is_public for faster queries
        $indices = $wpdb->get_results("SHOW INDEX FROM {$members_table}");
        $index_names = array();
        foreach ($indices as $index) {
            $index_names[] = $index->Key_name;
        }
        
        if (!in_array('is_public', $index_names)) {
            $wpdb->query("ALTER TABLE {$members_table} ADD INDEX is_public (is_public)");
            error_log('JGK Activation: Added index on is_public column');
        }
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
            membership_number varchar(50) NOT NULL,
            membership_type varchar(50) NOT NULL,
            status varchar(32) DEFAULT 'pending_approval',
            coach_id bigint(20) UNSIGNED,
            date_joined datetime DEFAULT CURRENT_TIMESTAMP,
            date_expires datetime,
            expiry_date date,
            join_date date,
            date_of_birth date,
            gender varchar(20),
            first_name varchar(100),
            last_name varchar(100),
            phone varchar(20),
            email varchar(100),
            address text,
            biography text,
            parents_guardians text,
            profile_image_url varchar(500),
            profile_image_id bigint(20) UNSIGNED,
            consent_photography varchar(16) DEFAULT 'no',
            emergency_contact_name varchar(100),
            emergency_contact_phone varchar(20),
            club_affiliation varchar(100),
            handicap varchar(10),
            medical_conditions text,
            parental_consent tinyint(1) DEFAULT 0,
            is_public tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY membership_number (membership_number),
            KEY user_id (user_id),
            KEY coach_id (coach_id),
            KEY status (status),
            KEY is_public (is_public)
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
            auto_renew tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
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
            PRIMARY KEY  (id),
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
            PRIMARY KEY  (id),
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
            PRIMARY KEY  (id),
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
            PRIMARY KEY  (id),
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
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY member_id (member_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Capture output to prevent "headers already sent" errors
        ob_start();
        $results = array();
        $results['members'] = dbDelta($sql_members);
        $results['memberships'] = dbDelta($sql_memberships);
        $results['plans'] = dbDelta($sql_plans);
        $results['payments'] = dbDelta($sql_payments);
        $results['competition_entries'] = dbDelta($sql_competition_entries);
        $results['certifications'] = dbDelta($sql_certifications);
        $results['audit_log'] = dbDelta($sql_audit_log);
        ob_end_clean();

        // Create additional tables for roles functionality
        $additional_results = self::create_additional_tables();

        // Insert default plan
        self::insert_default_data();
        
        // Return combined results
        return array_merge($results, $additional_results);
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
        $table_coach_ratings = $wpdb->prefix . 'jgk_coach_ratings';
        $sql_coach_ratings = "CREATE TABLE $table_coach_ratings (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            coach_user_id bigint(20) UNSIGNED NOT NULL,
            member_id mediumint(9) NOT NULL,
            rating smallint(6) NOT NULL,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY coach_user_id (coach_user_id),
            KEY member_id (member_id),
            KEY rating (rating)
        ) $charset_collate;";

        // Recommendations table
        $table_recommendations = $wpdb->prefix . 'jgk_recommendations';
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
            PRIMARY KEY  (id),
            KEY recommender_user_id (recommender_user_id),
            KEY member_id (member_id),
            KEY type (type),
            KEY status (status)
        ) $charset_collate;";

        // Training schedules table
        $table_training_schedules = $wpdb->prefix . 'jgk_training_schedules';
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
            PRIMARY KEY  (id),
            KEY coach_user_id (coach_user_id),
            KEY start_datetime (start_datetime),
            KEY club_id (club_id)
        ) $charset_collate;";

        // Role requests table
        $table_role_requests = $wpdb->prefix . 'jgk_role_requests';
        $sql_role_requests = "CREATE TABLE $table_role_requests (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            requester_user_id bigint(20) UNSIGNED NOT NULL,
            requested_role varchar(64) NOT NULL,
            reason text,
            status varchar(32) DEFAULT 'pending',
            reviewed_by bigint(20) UNSIGNED,
            reviewed_at datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY requester_user_id (requester_user_id),
            KEY requested_role (requested_role),
            KEY status (status)
        ) $charset_collate;";

        // Coach profiles table
        $table_coach_profiles = $wpdb->prefix . 'jgk_coach_profiles';
        $sql_coach_profiles = "CREATE TABLE $table_coach_profiles (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            qualifications text,
            specialties text,
            bio text,
            license_docs_ref varchar(500),
            verification_status varchar(32) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id),
            KEY verification_status (verification_status)
        ) $charset_collate;";

        // Parents/Guardians table
        $table_parents_guardians = $wpdb->prefix . 'jgk_parents_guardians';
        $sql_parents_guardians = "CREATE TABLE $table_parents_guardians (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            member_id mediumint(9) NOT NULL,
            relationship varchar(50) NOT NULL,
            first_name varchar(100) NOT NULL,
            last_name varchar(100) NOT NULL,
            email varchar(100),
            phone varchar(20),
            mobile varchar(20),
            address text,
            occupation varchar(100),
            employer varchar(150),
            id_number varchar(50),
            is_primary_contact tinyint(1) DEFAULT 0,
            can_pickup tinyint(1) DEFAULT 1,
            emergency_contact tinyint(1) DEFAULT 0,
            notes text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY member_id (member_id),
            KEY is_primary_contact (is_primary_contact)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Capture output to prevent "headers already sent" errors
        ob_start();
        $results = array();
        $results['coach_ratings'] = dbDelta($sql_coach_ratings);
        $results['recommendations'] = dbDelta($sql_recommendations);
        $results['training_schedules'] = dbDelta($sql_training_schedules);
        $results['role_requests'] = dbDelta($sql_role_requests);
        $results['coach_profiles'] = dbDelta($sql_coach_profiles);
        $results['parents_guardians'] = dbDelta($sql_parents_guardians);
        ob_end_clean();
        
        return $results;
    }

    /**
     * Create custom roles and capabilities during plugin activation
     *
     * @since    1.0.0
     */
    private static function create_roles_and_capabilities() {
        // JGK Member Role (Junior Golf Kenya)
        if (!get_role('jgk_member')) {
            add_role('jgk_member', 'JGK Member', array(
                'read' => true,
                'view_member_dashboard' => true,
                'manage_own_profile' => true,
            ));
        }

        // JGK Coach Role
        if (!get_role('jgk_coach')) {
            add_role('jgk_coach', 'JGK Coach', array(
                'read' => true,
                'view_member_dashboard' => true,
                'coach_rate_player' => true,
                'coach_recommend_competition' => true,
                'coach_recommend_training' => true,
                'manage_own_profile' => true,
            ));
        }

        // JGK Staff Role
        if (!get_role('jgk_staff')) {
            add_role('jgk_staff', 'JGK Staff', array(
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
                'manage_coaches',
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
            // Dashboard Pages
            'coach-dashboard' => array(
                'title' => 'Coach Dashboard',
                'content' => '[jgk_coach_dashboard]',
                'description' => 'Dashboard for coaches to manage their members and view statistics.'
            ),
            'member-dashboard' => array(
                'title' => 'My Dashboard',
                'content' => '[jgk_member_dashboard]',
                'description' => 'Personal dashboard for members to view their profile and coaches.'
            ),
            
            // Registration & Requests
            'member-registration' => array(
                'title' => 'Become a Member',
                'content' => '[jgk_registration_form]',
                'description' => 'Register as a new member of Junior Golf Kenya.'
            ),
            'coach-role-request' => array(
                'title' => 'Apply as Coach',
                'content' => '[jgk_coach_request_form]',
                'description' => 'Submit a request to become a coach.'
            ),
            
            // Portal & Verification
            'member-portal' => array(
                'title' => 'Member Portal',
                'content' => '[jgk_member_portal]',
                'description' => 'Access member services and information.'
            ),
            'verify-membership' => array(
                'title' => 'Verify Membership',
                'content' => '[jgk_verification_widget]',
                'description' => 'Verify membership status and details.'
            )
        );

        $created_pages = array();
        
        foreach ($pages as $slug => $page_data) {
            $option_name = 'jgk_page_' . str_replace('-', '_', $slug);
            
            // First check if we already have the page ID stored in options
            $stored_page_id = get_option($option_name);
            $existing_page = null;
            
            if ($stored_page_id) {
                // Verify the stored page still exists
                $existing_page = get_post($stored_page_id);
                if (!$existing_page || $existing_page->post_type !== 'page' || $existing_page->post_status === 'trash') {
                    // Stored page no longer valid, delete the option
                    delete_option($option_name);
                    $existing_page = null;
                }
            }
            
            // If no valid stored page, search by slug
            if (!$existing_page) {
                $existing_page = get_page_by_path($slug);
            }
            
            // If still not found, search by title as last resort
            if (!$existing_page) {
                $query = new WP_Query(array(
                    'post_type' => 'page',
                    'post_status' => 'publish',
                    'title' => $page_data['title'],
                    'posts_per_page' => 1,
                    'no_found_rows' => true,
                    'update_post_meta_cache' => false,
                    'update_post_term_cache' => false
                ));
                
                if ($query->have_posts()) {
                    $existing_page = $query->posts[0];
                }
            }
            
            if (!$existing_page) {
                // Page doesn't exist, create it
                $page_id = wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_excerpt' => $page_data['description'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug,
                    'comment_status' => 'closed',
                    'ping_status' => 'closed'
                ));
                
                if ($page_id && !is_wp_error($page_id)) {
                    $created_pages[$slug] = $page_id;
                    
                    // Store page ID in options for easy reference
                    update_option($option_name, $page_id);
                    
                    error_log("JuniorGolfKenya: Created page '{$page_data['title']}' with ID {$page_id}");
                }
            } else {
                // Page exists, just update the option
                update_option($option_name, $existing_page->ID);
                
                error_log("JuniorGolfKenya: Found existing page '{$page_data['title']}' with ID {$existing_page->ID}");
            }
        }
        
        // Store creation log
        if (!empty($created_pages)) {
            $previous_pages = get_option('jgk_created_pages', array());
            $all_pages = array_merge($previous_pages, $created_pages);
            update_option('jgk_created_pages', $all_pages);
            error_log('JuniorGolfKenya: Page creation summary - ' . wp_json_encode($created_pages));
        }
        
        return $created_pages;
    }
    
    /**
     * Get coach role request page content
     *
     * @since    1.0.0
     * @return   string  HTML content for the page
     */
    private static function get_coach_role_request_content() {
        ob_start();
        ?>
<div class="jgk-coach-request-form">
    <div class="jgk-form-header">
        <h2>Apply to Become a Coach</h2>
        <p>Thank you for your interest in becoming a coach at Junior Golf Kenya. Please fill out the form below to submit your application.</p>
    </div>
    
    <?php if (is_user_logged_in()): 
        global $wpdb;
        $user_id = get_current_user_id();
        $current_user = wp_get_current_user();
        
        // Check if already a coach
        if (in_array('jgk_coach', $current_user->roles)) {
            echo '<div class="jgk-notice jgk-notice-info">You already have coach access!</div>';
            echo '<p><a href="' . home_url('/coach-dashboard/') . '" class="jgk-btn jgk-btn-primary">Go to Coach Dashboard</a></p>';
        } else {
            // Check if already submitted a request
            $role_requests_table = $wpdb->prefix . 'jgf_role_requests';
            $existing_request = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$role_requests_table} WHERE requester_user_id = %d ORDER BY created_at DESC LIMIT 1",
                $user_id
            ));
            
            if ($existing_request && $existing_request->status === 'pending') {
                echo '<div class="jgk-notice jgk-notice-warning">You have a pending coach role request. We will review it soon!</div>';
                echo '<p><strong>Submitted:</strong> ' . date('F j, Y', strtotime($existing_request->created_at)) . '</p>';
                echo '<p><strong>Status:</strong> ' . ucfirst($existing_request->status) . '</p>';
            } else {
    ?>
    
    <form method="post" id="jgk-coach-request-form" class="jgk-form">
        <?php wp_nonce_field('jgk_coach_request_action', 'jgk_coach_request_nonce'); ?>
        <input type="hidden" name="action" value="jgk_submit_coach_request">
        
        <div class="jgk-form-section">
            <h3>Personal Information</h3>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="first_name">First Name *</label>
                    <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($current_user->user_firstname); ?>" required>
                </div>
                <div class="jgk-form-field">
                    <label for="last_name">Last Name *</label>
                    <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($current_user->user_lastname); ?>" required>
                </div>
            </div>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?php echo esc_attr($current_user->user_email); ?>" readonly>
                </div>
                <div class="jgk-form-field">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" placeholder="+254..." required>
                </div>
            </div>
        </div>
        
        <div class="jgk-form-section">
            <h3>Coaching Experience</h3>
            
            <div class="jgk-form-field">
                <label for="years_experience">Years of Coaching Experience *</label>
                <select id="years_experience" name="years_experience" required>
                    <option value="">Select...</option>
                    <option value="0-1">Less than 1 year</option>
                    <option value="1-3">1-3 years</option>
                    <option value="3-5">3-5 years</option>
                    <option value="5-10">5-10 years</option>
                    <option value="10+">10+ years</option>
                </select>
            </div>
            
            <div class="jgk-form-field">
                <label for="specialization">Specialization</label>
                <input type="text" id="specialization" name="specialization" placeholder="e.g., Junior Golf, Swing Technique, Mental Game">
            </div>
            
            <div class="jgk-form-field">
                <label for="certifications">Certifications & Qualifications *</label>
                <textarea id="certifications" name="certifications" rows="4" placeholder="List your coaching certifications, PGA qualifications, or relevant training..." required></textarea>
            </div>
            
            <div class="jgk-form-field">
                <label for="experience">Coaching Experience Details *</label>
                <textarea id="experience" name="experience" rows="5" placeholder="Describe your coaching experience, achievements, and approach to coaching junior golfers..." required></textarea>
            </div>
        </div>
        
        <div class="jgk-form-section">
            <h3>References</h3>
            
            <div class="jgk-form-row">
                <div class="jgk-form-field">
                    <label for="reference_name">Reference Name</label>
                    <input type="text" id="reference_name" name="reference_name" placeholder="Full name">
                </div>
                <div class="jgk-form-field">
                    <label for="reference_contact">Reference Contact</label>
                    <input type="text" id="reference_contact" name="reference_contact" placeholder="Phone or email">
                </div>
            </div>
        </div>
        
        <div class="jgk-form-field">
            <label>
                <input type="checkbox" name="agree_terms" required>
                I agree to the Junior Golf Kenya coaching terms and conditions *
            </label>
        </div>
        
        <div class="jgk-form-actions">
            <button type="submit" class="jgk-btn jgk-btn-primary">Submit Application</button>
        </div>
    </form>
    
    <?php 
            }
        }
    else: ?>
        <div class="jgk-notice jgk-notice-warning">
            <p>You must be logged in to apply as a coach.</p>
            <p><a href="<?php echo wp_login_url(get_permalink()); ?>">Login</a> | <a href="<?php echo wp_registration_url(); ?>">Register</a></p>
        </div>
    <?php endif; ?>
</div>

<style>
.jgk-coach-request-form {
    max-width: 800px;
    margin: 40px auto;
    padding: 30px;
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.jgk-form-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
}

.jgk-form-header h2 {
    color: #2c3e50;
    margin-bottom: 10px;
}

.jgk-form-header p {
    color: #7f8c8d;
    font-size: 16px;
}

.jgk-form-section {
    margin-bottom: 30px;
}

.jgk-form-section h3 {
    color: #2c3e50;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #e9ecef;
}

.jgk-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.jgk-form-field {
    margin-bottom: 20px;
}

.jgk-form-field label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #2c3e50;
}

.jgk-form-field input[type="text"],
.jgk-form-field input[type="email"],
.jgk-form-field input[type="tel"],
.jgk-form-field select,
.jgk-form-field textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 15px;
    transition: border-color 0.3s;
}

.jgk-form-field input:focus,
.jgk-form-field select:focus,
.jgk-form-field textarea:focus {
    outline: none;
    border-color: #667eea;
}

.jgk-form-field input[readonly] {
    background: #f8f9fa;
    cursor: not-allowed;
}

.jgk-form-actions {
    margin-top: 30px;
    text-align: center;
}

.jgk-btn {
    display: inline-block;
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.3s;
}

.jgk-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.jgk-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.jgk-notice {
    padding: 15px 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.jgk-notice-info {
    background: #d1ecf1;
    color: #0c5460;
    border-left: 4px solid #0c5460;
}

.jgk-notice-warning {
    background: #fff3cd;
    color: #856404;
    border-left: 4px solid #856404;
}

@media (max-width: 768px) {
    .jgk-coach-request-form {
        padding: 20px;
    }
    
    .jgk-form-row {
        grid-template-columns: 1fr;
    }
}
</style>
        <?php
        return ob_get_clean();
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

    /**
     * Verify that all required tables were created
     *
     * @since    1.0.0
     * @return   array    Status of each table
     */
    private static function verify_tables() {
        global $wpdb;
        
        $required_tables = array(
            'jgk_members',
            'jgk_memberships',
            'jgk_plans',
            'jgk_payments',
            'jgk_competition_entries',
            'jgk_certifications',
            'jgk_audit_log',
            'jgk_coach_ratings',
            'jgk_recommendations',
            'jgk_training_schedules',
            'jgk_role_requests',
            'jgk_coach_profiles',
            'jgk_parents_guardians'
        );
        
        $verification = array(
            'success' => true,
            'missing' => array(),
            'existing' => array()
        );
        
        foreach ($required_tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
            
            if ($table_exists) {
                $verification['existing'][] = $table;
            } else {
                $verification['missing'][] = $table;
                $verification['success'] = false;
            }
        }
        
        // Log the results
        error_log('JuniorGolfKenya Activation - Tables Verification: ' . wp_json_encode($verification));
        
        return $verification;
    }
}
