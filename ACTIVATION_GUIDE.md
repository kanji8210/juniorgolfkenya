# Junior Golf Kenya - Plugin Activation Documentation

## Overview
This document outlines the complete activation process for the Junior Golf Kenya membership management plugin, including roles, capabilities, database schema, and implementation guidelines.

---

## 1. USER ROLES & CAPABILITIES SYSTEM

### 1.1 Roles to Create During Activation

#### **jgf_member** - Standard Paid Member
- Primary role for paid members
- Access to member dashboard and profile management
- Can enroll in competitions and view coach recommendations

#### **jgf_coach** - Certified Coaches
- Can rate players and provide feedback
- Recommend competitions and training programs
- Manage training schedules and player development
- Requires staff approval before role assignment

#### **jgf_staff** - Operational Staff
- Tournament managers and finance personnel
- Mid-level administrative rights
- Can approve recommendations and manage day-to-day operations

#### **jgf_admin** - Full Site Administrators
- Complete access to all plugin features
- System configuration and reporting capabilities
- Member management and financial oversight

### 1.2 Capabilities Matrix

| Capability | Member | Coach | Staff | Admin |
|------------|--------|-------|-------|-------|
| `view_member_dashboard` | ✓ | ✓ | ✓ | ✓ |
| `manage_own_profile` | ✓ | ✓ | ✓ | ✓ |
| `edit_members` | ✗ | ✗ | ✓ | ✓ |
| `manage_payments` | ✗ | ✗ | ✓ | ✓ |
| `view_reports` | ✗ | ✗ | ✓ | ✓ |
| `manage_competitions` | ✗ | ✗ | ✓ | ✓ |
| `coach_rate_player` | ✗ | ✓ | ✗ | ✓ |
| `coach_recommend_competition` | ✗ | ✓ | ✗ | ✓ |
| `coach_recommend_training` | ✗ | ✓ | ✗ | ✓ |
| `approve_role_requests` | ✗ | ✗ | ✓ | ✓ |
| `manage_certifications` | ✗ | ✗ | ✓ | ✓ |

---

## 2. DATABASE SCHEMA ADDITIONS

### 2.1 Additional Tables Required

#### **jgf_coach_ratings**
```sql
CREATE TABLE {$wpdb->prefix}jgf_coach_ratings (
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
) {$charset_collate};
```

#### **jgf_recommendations**
```sql
CREATE TABLE {$wpdb->prefix}jgf_recommendations (
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
) {$charset_collate};
```

#### **jgf_training_schedules**
```sql
CREATE TABLE {$wpdb->prefix}jgf_training_schedules (
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
) {$charset_collate};
```

#### **jgf_role_requests**
```sql
CREATE TABLE {$wpdb->prefix}jgf_role_requests (
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
) {$charset_collate};
```

#### **jgf_coach_profiles** (Optional - for extended coach metadata)
```sql
CREATE TABLE {$wpdb->prefix}jgf_coach_profiles (
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
) {$charset_collate};
```

---

## 3. ACTIVATION CODE IMPLEMENTATION

### 3.1 Role Creation Function
```php
/**
 * Create custom roles and capabilities during plugin activation
 */
function jgf_create_roles_and_caps() {
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
```

### 3.2 User Registration with Role Assignment
```php
/**
 * Example: Assign role during member registration
 */
function assign_member_role_on_registration($user_id, $role = 'jgf_member') {
    $user = new WP_User($user_id);
    $user->set_role($role);
    
    // Log role assignment
    global $wpdb;
    $audit_table = $wpdb->prefix . 'jgk_audit_log';
    $wpdb->insert(
        $audit_table,
        array(
            'user_id' => $user_id,
            'action' => 'role_assigned',
            'object_type' => 'user_role',
            'new_values' => json_encode(array('role' => $role)),
            'created_at' => current_time('mysql')
        )
    );
}
```

---

## 4. FEATURE MAPPING BY ROLE

### 4.1 Member Features
- ✅ Register and pay for membership
- ✅ View personal dashboard
- ✅ Enroll in competitions
- ✅ View coach recommendations
- ✅ Manage personal profile
- ✅ Download membership card

### 4.2 Coach Features
- ✅ Access coach dashboard
- ✅ Create and modify training schedules
- ✅ Submit player ratings and feedback
- ✅ Recommend competitions and training programs
- ✅ View assigned players (with opt-in consent)
- ✅ Receive role-request notifications

### 4.3 Staff Features
- ✅ Manage member accounts
- ✅ Verify and process payments
- ✅ Approve recommendations and role requests
- ✅ Generate operational reports
- ✅ Manage competition entries
- ✅ Handle certification uploads

### 4.4 Admin Features
- ✅ Full system access and configuration
- ✅ Export comprehensive reports
- ✅ Configure payment gateways and plans
- ✅ System-wide member management
- ✅ Security and audit oversight

---

## 5. REST API ENDPOINTS

### 5.1 Coach Endpoints
```
POST /wp-json/jgf/v1/coach/{coach_id}/rate
Body: { member_id, rating, notes }
Capability: coach_rate_player

POST /wp-json/jgf/v1/recommendation
Body: { member_id, type, payload }
Capability: coach_recommend_*

GET /wp-json/jgf/v1/training?coach_id=
Capability: view_member_dashboard
```

### 5.2 Admin/Staff Endpoints
```
GET /wp-json/jgf/v1/reports/summary
Capability: view_reports

POST /wp-json/jgf/v1/role-request/approve
Body: { request_id, approved }
Capability: approve_role_requests

GET /wp-json/jgf/v1/members/export
Capability: edit_members
```

---

## 6. OPERATIONAL GUIDELINES

### 6.1 Coach Vetting Process
1. Coach submits role request through member portal
2. Staff reviews qualifications and documentation
3. Background check and certification verification
4. Staff approves/denies request
5. Approved coaches receive jgf_coach role
6. Welcome email with coach dashboard access

### 6.2 Privacy and Consent
- Members must opt-in to coach ratings and visibility
- Respect opt-out preferences in all public listings
- Secure handling of personal and payment data
- Audit trail for all sensitive operations

### 6.3 Notification System
- Coach notifications for training requests
- Staff notifications for recommendation submissions
- Member notifications for role updates
- Payment and membership renewal reminders

---

## 7. TESTING REQUIREMENTS

### 7.1 Role Testing Checklist
- [ ] Create test users for each role
- [ ] Verify capability restrictions work correctly
- [ ] Test role upgrade/downgrade processes
- [ ] Validate REST endpoint security

### 7.2 Database Testing
- [ ] Verify all tables create successfully
- [ ] Test foreign key relationships
- [ ] Validate data integrity constraints
- [ ] Performance test with sample data

### 7.3 Integration Testing
- [ ] Payment gateway integration
- [ ] Email notification delivery
- [ ] File upload and security
- [ ] Export functionality accuracy

---

## 8. NEXT STEPS FOR IMPLEMENTATION

1. **Update Activator Class**: Add new tables and role creation
2. **Create REST Endpoints**: Implement API routes with capability checks
3. **Build UI Components**: Dashboard interfaces for each role
4. **Implement Security**: Nonce verification and input sanitization
5. **Add Notification System**: Email templates and automated triggers
6. **Create Reports Module**: Data aggregation and export functionality
7. **Testing and Documentation**: Comprehensive testing and user guides

---

## 9. IMPLEMENTATION CHECKLIST

### Phase 1: Core Activation
- [ ] Add role creation to activator class
- [ ] Implement additional database tables
- [ ] Test activation and deactivation
- [ ] Verify capability checks work

### Phase 2: User Management
- [ ] Create role assignment logic
- [ ] Build role request system
- [ ] Implement coach approval workflow
- [ ] Add user profile enhancements

### Phase 3: API Development
- [ ] Create REST endpoints
- [ ] Add security middleware
- [ ] Implement data validation
- [ ] Add error handling

### Phase 4: UI Development
- [ ] Build role-specific dashboards
- [ ] Create admin management interface
- [ ] Add public-facing components
- [ ] Implement responsive design

### Phase 5: Testing & Deployment
- [ ] Unit testing for all components
- [ ] Integration testing
- [ ] Security audit
- [ ] Performance optimization
- [ ] Documentation completion