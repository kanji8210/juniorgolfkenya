# Exemple Pratique d'Utilisation - Système de Paramètres

## 🎯 Scénario : Différentes Organisations

Votre système Junior Golf Kenya peut maintenant être utilisé par différentes organisations avec des règles différentes !

### Organisation 1 : Junior Golf Kenya (Original)
```
Âge : 2 à 17 ans
Prix : KSh 5,000 / an
Devise : Shilling Kenyan (KSh)
```

### Organisation 2 : Youth Golf USA
```
Âge : 5 à 16 ans
Prix : $100 / an
Devise : Dollar US ($)
```

### Organisation 3 : European Junior Golf
```
Âge : 3 à 18 ans
Prix : €75 / an
Devise : Euro (€)
```

**Configuration via Settings - Aucune modification de code nécessaire !**

---

## 📝 Exemple 1 : Validation d'Inscription

### Code de Validation Moderne (Recommandé)

```php
<?php
/**
 * Fichier: public/partials/juniorgolfkenya-registration-form.php
 * Validation d'une inscription de membre junior
 */

// Inclure le helper (une seule fois en haut du fichier)
require_once plugin_dir_path(__FILE__) . '../../includes/class-juniorgolfkenya-settings-helper.php';

// Récupérer les données du formulaire
$birthdate = sanitize_text_field($_POST['birthdate']);
$errors = array();

// Validation simple avec le helper
$validation = JuniorGolfKenya_Settings_Helper::validate_birthdate($birthdate);

if (!$validation['valid']) {
    $errors[] = $validation['message'];
    // Exemples de messages automatiques :
    // "Member must be at least 2 years old. Current age: 1 years."
    // "Member must be 17 years old or younger. Current age: 18 years. This system is for juniors only."
}

// Si pas d'erreurs, continuer l'inscription
if (empty($errors)) {
    $age = JuniorGolfKenya_Settings_Helper::calculate_age($birthdate);
    echo "Inscription validée pour un junior de {$age} ans !";
} else {
    foreach ($errors as $error) {
        echo "<p class='error'>{$error}</p>";
    }
}
?>
```

### Affichage HTML avec Contraintes Dynamiques

```php
<?php
// Récupérer les limites pour l'interface
$min_age = JuniorGolfKenya_Settings_Helper::get_min_age();
$max_age = JuniorGolfKenya_Settings_Helper::get_max_age();
$max_date = JuniorGolfKenya_Settings_Helper::get_birthdate_max();
$min_date = JuniorGolfKenya_Settings_Helper::get_birthdate_min();
?>

<div class="form-group">
    <label for="birthdate">
        Date of Birth 
        <span class="hint">(Must be between <?php echo $min_age; ?> and <?php echo $max_age; ?> years old)</span>
    </label>
    
    <input type="date" 
           id="birthdate" 
           name="birthdate" 
           max="<?php echo $max_date; ?>"
           min="<?php echo $min_date; ?>"
           data-min-age="<?php echo $min_age; ?>"
           data-max-age="<?php echo $max_age; ?>"
           required>
    
    <small class="help-text">
        <?php 
        echo sprintf(
            'Juniors aged %d to %d years are eligible for membership.',
            $min_age,
            $max_age
        );
        ?>
    </small>
</div>

<!-- Validation JavaScript en temps réel (optionnel) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const birthdateInput = document.getElementById('birthdate');
    const minAge = parseInt(birthdateInput.dataset.minAge);
    const maxAge = parseInt(birthdateInput.dataset.maxAge);
    
    birthdateInput.addEventListener('change', function() {
        const birthDate = new Date(this.value);
        const today = new Date();
        const age = Math.floor((today - birthDate) / (365.25 * 24 * 60 * 60 * 1000));
        
        if (age < minAge) {
            alert(`Member must be at least ${minAge} years old. Current age: ${age} years.`);
            this.value = '';
        } else if (age > maxAge) {
            alert(`Member must be ${maxAge} years old or younger. Current age: ${age} years.`);
            this.value = '';
        }
    });
});
</script>
```

---

## 💰 Exemple 2 : Affichage des Prix

### Page de Tarification

```php
<?php
/**
 * Fichier: public/partials/juniorgolfkenya-pricing-page.php
 * Affichage des tarifs d'adhésion
 */

// Récupérer les paramètres de prix
$pricing = JuniorGolfKenya_Settings_Helper::get_pricing_settings();
$formatted_price = JuniorGolfKenya_Settings_Helper::get_formatted_price();
$org_name = JuniorGolfKenya_Settings_Helper::get_organization_name();
?>

<div class="pricing-section">
    <h2><?php echo esc_html($org_name); ?> Membership</h2>
    
    <div class="pricing-card">
        <div class="price">
            <span class="amount"><?php echo esc_html($formatted_price); ?></span>
            <span class="frequency">/ <?php echo esc_html($pricing['payment_frequency']); ?></span>
        </div>
        
        <div class="price-details">
            <p>Currency: <?php echo esc_html($pricing['currency']); ?></p>
            <p>Payment: <?php echo esc_html(ucfirst($pricing['payment_frequency'])); ?></p>
        </div>
        
        <a href="/register" class="btn btn-primary">Join Now</a>
    </div>
</div>

<style>
.pricing-card {
    border: 2px solid #28a745;
    border-radius: 8px;
    padding: 30px;
    text-align: center;
    max-width: 400px;
    margin: 0 auto;
}

.price .amount {
    font-size: 48px;
    font-weight: bold;
    color: #28a745;
}

.price .frequency {
    font-size: 18px;
    color: #666;
}
</style>
```

### Email de Confirmation avec Prix

```php
<?php
/**
 * Envoyer un email de confirmation d'inscription
 */

function send_membership_confirmation($member_id, $email) {
    $price = JuniorGolfKenya_Settings_Helper::get_formatted_price();
    $frequency = JuniorGolfKenya_Settings_Helper::get_pricing_settings()['payment_frequency'];
    $org_name = JuniorGolfKenya_Settings_Helper::get_organization_name();
    $org_email = JuniorGolfKenya_Settings_Helper::get_organization_email();
    
    $subject = "Welcome to {$org_name}!";
    
    $message = "
        <h2>Thank you for joining {$org_name}!</h2>
        
        <p>Your membership has been confirmed.</p>
        
        <h3>Membership Details:</h3>
        <ul>
            <li><strong>Membership Fee:</strong> {$price}</li>
            <li><strong>Payment Frequency:</strong> " . ucfirst($frequency) . "</li>
            <li><strong>Member ID:</strong> {$member_id}</li>
        </ul>
        
        <p>If you have any questions, please contact us at {$org_email}.</p>
        
        <p>Welcome to the family!</p>
        <p>The {$org_name} Team</p>
    ";
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    wp_mail($email, $subject, $message, $headers);
}
?>
```

---

## 🧪 Exemple 3 : Génération de Données de Test

### Générer 10 Membres pour Test

```php
<?php
/**
 * Script de test : Peupler la base avec des données de test
 * À exécuter uniquement en développement !
 */

// Vérifier qu'on est en environnement de développement
if (defined('WP_DEBUG') && WP_DEBUG === true) {
    
    // Générer 10 membres de test
    $result = JuniorGolfKenya_Test_Data::generate_test_members(10);
    
    // Afficher les résultats
    echo "<h2>Test Data Generation Report</h2>";
    
    if (!empty($result['errors'])) {
        echo "<div class='error'>";
        echo "<h3>Errors encountered:</h3>";
        echo "<ul>";
        foreach ($result['errors'] as $error) {
            echo "<li>{$error}</li>";
        }
        echo "</ul>";
        echo "</div>";
    }
    
    echo "<div class='success'>";
    echo "<h3>Successfully generated:</h3>";
    echo "<ul>";
    echo "<li><strong>" . count($result['members']) . "</strong> members</li>";
    echo "<li><strong>" . count($result['users']) . "</strong> user accounts</li>";
    echo "<li><strong>" . count($result['parents']) . "</strong> parent/guardian records</li>";
    echo "</ul>";
    
    echo "<h4>Member IDs:</h4>";
    echo "<ul>";
    foreach ($result['members'] as $member_id) {
        echo "<li>Member ID: {$member_id}</li>";
    }
    echo "</ul>";
    echo "</div>";
    
    echo "<p><a href='/wp-admin/admin.php?page=juniorgolfkenya-members'>View Members List</a></p>";
    
} else {
    die('Test data generation is only allowed in development mode!');
}
?>
```

### Vérifier l'Existence de Données de Test

```php
<?php
/**
 * Afficher un avertissement si des données de test existent
 * Utile dans le dashboard admin
 */

if (JuniorGolfKenya_Test_Data::has_test_data()) {
    $counts = JuniorGolfKenya_Test_Data::count_test_data();
    ?>
    
    <div class="notice notice-warning is-dismissible">
        <h3>⚠️ Test Data Detected!</h3>
        <p>
            Your database contains <strong><?php echo $counts['members']; ?> test member(s)</strong>.
        </p>
        <p>
            <a href="/wp-admin/admin.php?page=juniorgolfkenya-settings&tab=test-data" class="button button-primary">
                Go to Settings → Manage Test Data
            </a>
        </p>
    </div>
    
    <?php
}
?>
```

### Nettoyage Automatique Avant Déploiement

```php
<?php
/**
 * Hook de déploiement : Nettoyer les données de test automatiquement
 * À ajouter dans le processus de déploiement
 */

function cleanup_test_data_before_production() {
    // Vérifier qu'on passe en production (variable d'environnement)
    if (getenv('APP_ENV') === 'production' && JuniorGolfKenya_Test_Data::has_test_data()) {
        
        // Supprimer toutes les données de test
        $result = JuniorGolfKenya_Test_Data::delete_all_test_data();
        
        // Logger le nettoyage
        error_log(sprintf(
            'Production cleanup: Deleted %d test users, %d test members, %d test parents',
            $result['users_deleted'],
            $result['members_deleted'],
            $result['parents_deleted']
        ));
        
        // Notifier l'admin
        $admin_email = get_option('admin_email');
        wp_mail(
            $admin_email,
            'Test Data Cleanup Report',
            "Test data has been automatically cleaned during production deployment.\n\n" .
            "Details:\n" .
            "- Users deleted: {$result['users_deleted']}\n" .
            "- Members deleted: {$result['members_deleted']}\n" .
            "- Parents deleted: {$result['parents_deleted']}\n"
        );
    }
}

// Exécuter lors de l'activation du plugin en production
register_activation_hook(__FILE__, 'cleanup_test_data_before_production');
?>
```

---

## 🔄 Exemple 4 : Mise à Jour Dynamique

### Widget d'Information Adhésion

```php
<?php
/**
 * Widget WordPress : Informations d'adhésion
 * S'adapte automatiquement aux paramètres
 */

class JGK_Membership_Info_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'jgk_membership_info',
            'JGK Membership Info',
            array('description' => 'Display membership information')
        );
    }
    
    public function widget($args, $instance) {
        // Récupérer les paramètres
        $age_restrictions = JuniorGolfKenya_Settings_Helper::get_age_restrictions();
        $price = JuniorGolfKenya_Settings_Helper::get_formatted_price();
        $org_name = JuniorGolfKenya_Settings_Helper::get_organization_name();
        
        echo $args['before_widget'];
        echo $args['before_title'] . 'Join ' . esc_html($org_name) . $args['after_title'];
        ?>
        
        <div class="jgk-membership-widget">
            <div class="age-info">
                <h4>Who Can Join?</h4>
                <p>
                    Juniors aged <strong><?php echo $age_restrictions['min']; ?></strong> to 
                    <strong><?php echo $age_restrictions['max']; ?></strong> years old
                </p>
            </div>
            
            <div class="price-info">
                <h4>Membership Fee</h4>
                <p class="price"><?php echo esc_html($price); ?></p>
            </div>
            
            <a href="/register" class="btn-register">Register Now</a>
        </div>
        
        <?php
        echo $args['after_widget'];
    }
}

// Enregistrer le widget
function register_jgk_widgets() {
    register_widget('JGK_Membership_Info_Widget');
}
add_action('widgets_init', 'register_jgk_widgets');
?>
```

---

## 🎨 Exemple 5 : Shortcode Personnalisé

### Shortcode [jgk_membership_info]

```php
<?php
/**
 * Shortcode : Afficher les informations d'adhésion
 * Utilisation : [jgk_membership_info]
 */

function jgk_membership_info_shortcode($atts) {
    // Attributs par défaut
    $atts = shortcode_atts(array(
        'show_age' => 'yes',
        'show_price' => 'yes',
        'show_button' => 'yes',
    ), $atts);
    
    // Récupérer les paramètres
    $age_restrictions = JuniorGolfKenya_Settings_Helper::get_age_restrictions();
    $price = JuniorGolfKenya_Settings_Helper::get_formatted_price();
    $org_name = JuniorGolfKenya_Settings_Helper::get_organization_name();
    
    // Construire l'affichage
    ob_start();
    ?>
    
    <div class="jgk-membership-shortcode">
        <h3><?php echo esc_html($org_name); ?> Membership</h3>
        
        <?php if ($atts['show_age'] === 'yes'): ?>
        <div class="age-requirement">
            <strong>Age Requirement:</strong>
            <?php echo $age_restrictions['min']; ?> to <?php echo $age_restrictions['max']; ?> years old
        </div>
        <?php endif; ?>
        
        <?php if ($atts['show_price'] === 'yes'): ?>
        <div class="membership-price">
            <strong>Membership Fee:</strong>
            <span class="price"><?php echo esc_html($price); ?></span>
        </div>
        <?php endif; ?>
        
        <?php if ($atts['show_button'] === 'yes'): ?>
        <div class="register-action">
            <a href="/register" class="button">Join Now</a>
        </div>
        <?php endif; ?>
    </div>
    
    <?php
    return ob_get_clean();
}
add_shortcode('jgk_membership_info', 'jgk_membership_info_shortcode');

/**
 * Exemples d'utilisation dans les pages WordPress :
 * 
 * [jgk_membership_info]
 * [jgk_membership_info show_button="no"]
 * [jgk_membership_info show_age="yes" show_price="no"]
 */
?>
```

---

## 📊 Exemple 6 : Rapport Statistique

### Rapport par Tranche d'Âge

```php
<?php
/**
 * Générer un rapport des membres par tranche d'âge
 * Utilise les limites configurées
 */

function generate_age_distribution_report() {
    global $wpdb;
    
    $age_restrictions = JuniorGolfKenya_Settings_Helper::get_age_restrictions();
    $members_table = $wpdb->prefix . 'jgk_members';
    
    // Récupérer tous les membres
    $members = $wpdb->get_results("SELECT birthdate FROM {$members_table} WHERE status = 'active'");
    
    // Compter par âge
    $age_distribution = array();
    for ($age = $age_restrictions['min']; $age <= $age_restrictions['max']; $age++) {
        $age_distribution[$age] = 0;
    }
    
    foreach ($members as $member) {
        $age = JuniorGolfKenya_Settings_Helper::calculate_age($member->birthdate);
        if (isset($age_distribution[$age])) {
            $age_distribution[$age]++;
        }
    }
    
    // Afficher le rapport
    ?>
    <div class="age-distribution-report">
        <h2>Member Age Distribution</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>Age</th>
                    <th>Number of Members</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $total = array_sum($age_distribution);
                foreach ($age_distribution as $age => $count):
                    $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                ?>
                <tr>
                    <td><?php echo $age; ?> years</td>
                    <td><?php echo $count; ?></td>
                    <td>
                        <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                        <?php echo $percentage; ?>%
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th><?php echo $total; ?></th>
                    <th>100%</th>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php
}
?>
```

---

## 🔧 Exemple 7 : Configuration Multi-Environnements

### Paramètres par Environnement

```php
<?php
/**
 * Initialiser les paramètres selon l'environnement
 * À exécuter lors de l'activation du plugin
 */

function initialize_environment_settings() {
    $environment = getenv('APP_ENV') ?: 'production';
    
    switch ($environment) {
        case 'development':
            // Configuration pour développement
            $settings = array(
                'junior_settings' => array(
                    'min_age' => 2,
                    'max_age' => 17
                ),
                'pricing_settings' => array(
                    'subscription_price' => 100,
                    'currency' => 'KSH',
                    'currency_symbol' => 'KSh',
                    'payment_frequency' => 'yearly'
                ),
                'general_settings' => array(
                    'organization_name' => 'JGK Development',
                    'organization_email' => 'dev@test.local',
                    'timezone' => 'Africa/Nairobi'
                )
            );
            
            // Générer des données de test automatiquement
            if (!JuniorGolfKenya_Test_Data::has_test_data()) {
                JuniorGolfKenya_Test_Data::generate_test_members(20);
            }
            break;
            
        case 'staging':
            // Configuration pour pré-production
            $settings = array(
                'junior_settings' => array(
                    'min_age' => 2,
                    'max_age' => 17
                ),
                'pricing_settings' => array(
                    'subscription_price' => 5000,
                    'currency' => 'KSH',
                    'currency_symbol' => 'KSh',
                    'payment_frequency' => 'yearly'
                ),
                'general_settings' => array(
                    'organization_name' => 'JGK Staging',
                    'organization_email' => 'staging@juniorgolfkenya.org',
                    'timezone' => 'Africa/Nairobi'
                )
            );
            
            // Nettoyer les données de test
            if (JuniorGolfKenya_Test_Data::has_test_data()) {
                JuniorGolfKenya_Test_Data::delete_all_test_data();
            }
            break;
            
        case 'production':
        default:
            // Configuration pour production
            $settings = array(
                'junior_settings' => array(
                    'min_age' => 2,
                    'max_age' => 17
                ),
                'pricing_settings' => array(
                    'subscription_price' => 5000,
                    'currency' => 'KSH',
                    'currency_symbol' => 'KSh',
                    'payment_frequency' => 'yearly'
                ),
                'general_settings' => array(
                    'organization_name' => 'Junior Golf Kenya',
                    'organization_email' => 'info@juniorgolfkenya.org',
                    'organization_phone' => '+254700000000',
                    'organization_address' => 'Nairobi, Kenya',
                    'timezone' => 'Africa/Nairobi'
                )
            );
            
            // S'assurer qu'il n'y a pas de données de test
            if (JuniorGolfKenya_Test_Data::has_test_data()) {
                JuniorGolfKenya_Test_Data::delete_all_test_data();
            }
            break;
    }
    
    // Appliquer les paramètres
    foreach ($settings as $option_name => $option_value) {
        update_option("jgk_{$option_name}", $option_value);
    }
    
    // Logger l'initialisation
    error_log("JGK Settings initialized for environment: {$environment}");
}

// Hook d'activation
register_activation_hook(__FILE__, 'initialize_environment_settings');
?>
```

---

## 🎉 Résumé

Ces exemples montrent comment :

✅ **Valider les âges** avec des paramètres configurables  
✅ **Afficher les prix** dynamiquement selon la devise  
✅ **Générer des données de test** pour le développement  
✅ **Créer des widgets et shortcodes** adaptatifs  
✅ **Générer des rapports** basés sur les limites d'âge  
✅ **Configurer différents environnements** automatiquement  

**Tout cela sans jamais modifier le code en dur - juste en utilisant Settings !**

---

**Ces exemples sont prêts à l'emploi et peuvent être copiés directement dans votre plugin.**
