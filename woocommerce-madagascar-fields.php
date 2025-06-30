<?php
/**
 * Plugin Name: WooCommerce Madagascar Address & Custom Fields
 * Plugin URI:  https://e-varotra.mg
 * Description: Personnalisation des champs d'adresse pour Madagascar (Régions, Districts dépendants, Code Postal).
 * Version:     1.0.1
 * Author:      Jean claude RAKOTONARIVO
 * Author URI:  https://www.dizitalizeo.com
 * Text Domain: wc-mg-custom-fields
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * WC_Madagascar_Checkout_Customizer Class
 */
class WC_Madagascar_Checkout_Customizer {

    private $madagascar_locations = null;

    public function __construct() {
        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }

    /**
     * Initialisation des hooks et filtres.
     */
    public function init() {
        // Chargement du textdomain pour la traduction
        add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

        // Limiter le pays à Madagascar
        add_filter( 'woocommerce_countries', array( $this, 'limit_countries_to_madagascar' ) );
        add_filter( 'default_checkout_billing_country', array( $this, 'set_default_country_to_mg' ) );
        add_filter( 'default_checkout_shipping_country', array( $this, 'set_default_country_to_mg' ) );
        add_filter( 'woocommerce_default_address_fields', array( $this, 'set_address_fields_default_country' ) );

        // Personnaliser les champs de facturation et de livraison
        add_filter( 'woocommerce_checkout_fields', array( $this, 'customize_checkout_fields' ), 9999 ); // Priorité très élevée

        // Enqueue des scripts pour le checkout - seulement sur la page de paiement
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_scripts' ) );

        // Validation des champs personnalisés
        add_action( 'woocommerce_checkout_process', array( $this, 'validate_custom_fields' ) );

        // Sauvegarder les champs personnalisés
        add_action( 'woocommerce_checkout_update_order_meta', array( $this, 'save_custom_fields' ) );
        
        // Afficher les champs personnalisés dans l'admin des commandes
        add_action( 'woocommerce_admin_order_data_after_billing_address', array( $this, 'display_custom_fields_in_admin' ), 10, 1 );
        add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'display_custom_fields_in_admin' ), 10, 1 );

        // Ajouter les champs personnalisés au format de l'adresse
        add_filter( 'woocommerce_localisation_address_formats', array( $this, 'set_address_format' ) );
        add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'set_address_format_replacements' ), 10, 2 );

        // Synchroniser la région avec le champ 'state' de WooCommerce pour la compatibilité
        add_action( 'woocommerce_checkout_update_customer', array( $this, 'sync_region_to_state_for_customer' ), 10, 2 );
        
        // Forcer la devise MGA (Ariary)
        add_filter( 'woocommerce_currency', array( $this, 'force_madagascar_currency' ) );
        add_filter( 'woocommerce_currencies', array( $this, 'add_madagascar_currency_symbol' ) );
    }

    /**
     * Charge le fichier de traduction du plugin.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain( 'wc-mg-custom-fields', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * Limite la sélection du pays à Madagascar.
     *
     * @param array $countries Tableau des pays WooCommerce.
     * @return array Tableau des pays limité à Madagascar.
     */
    public function limit_countries_to_madagascar( $countries ) {
        return array( 'MG' => __( 'Madagascar', 'wc-mg-custom-fields' ) );
    }

    /**
     * Définit Madagascar comme pays par défaut pour la facturation et la livraison.
     *
     * @return string Code pays 'MG'.
     */
    public function set_default_country_to_mg() {
        return 'MG';
    }

    /**
     * Définit Madagascar comme pays par défaut dans les propriétés des champs d'adresse.
     *
     * @param array $fields Champs d'adresse WooCommerce par défaut.
     * @return array Champs d'adresse modifiés.
     */
    public function set_address_fields_default_country( $fields ) {
        if ( isset( $fields['country'] ) ) {
            $fields['country']['default'] = 'MG';
        }
        return $fields;
    }

    /**
     * Synchronise notre champ région avec le champ "state" de WooCommerce pour des raisons de compatibilité.
     *
     * @param WC_Customer $customer L'objet client.
     * @param array       $data     Les données du formulaire de paiement.
     */
    public function sync_region_to_state_for_customer( $customer, $data ) {
        if ( isset( $data['billing_region'] ) ) {
            $customer->set_billing_state( sanitize_text_field( $data['billing_region'] ) );
        }
        if ( isset( $data['shipping_region'] ) && isset( $data['ship_to_different_address'] ) && '1' === $data['ship_to_different_address'] ) {
            $customer->set_shipping_state( sanitize_text_field( $data['shipping_region'] ) );
        }
    }

    /**
     * Fournit la liste complète des régions et districts de Madagascar.
     * Utilise un cache pour éviter de recharger les données.
     *
     * @return array Tableau associatif des régions et de leurs districts.
     */
    private function get_madagascar_locations() {
        if ( $this->madagascar_locations === null ) {
            $this->madagascar_locations = array(
                'Analamanga' => array('Antananarivo I', 'Antananarivo II', 'Antananarivo III', 'Antananarivo IV', 'Antananarivo V', 'Antananarivo VI', 'Ambohidratrimo', 'Andramasina', 'Anjozorobe', 'Ankazobe', 'Manjakandriana'),
                'Vakinankaratra' => array('Ambatolampy', 'Antanifotsy', 'Antsirabe I', 'Antsirabe II', 'Betafo', 'Faratsiho', 'Mandoto'),
                'Itasy' => array('Arivonimamo', 'Miarinarivo', 'Soavinandriana'),
                'Bongolava' => array('Fenoarivobe', 'Tsiroanomandidy'),
                'Haute Matsiatra' => array('Ambalavao', 'Ambohimahasoa', 'Fianarantsoa I', 'Isandra', 'Ikalamavony', 'Vohibato'),
                'Amoron\'i Mania' => array('Ambatofinandrahana', 'Ambositra', 'Fandriana', 'Manandriana'),
                'Vatovavy' => array('Ifanadiana', 'Nosy Varika', 'Mananjary', 'Manakara'),
                'Fitovinany' => array('Vohipeno', 'Manakara', 'Ikongo'),
                'Ihorombe' => array('Ihosy', 'Ivohibe', 'Midongy-Atsimo'),
                'Atsimo-Atsinanana' => array('Farafangana', 'Vangaindrano', 'Vondrozo'),
                'Anosy' => array('Amboasary-Atsimo', 'Betroka', 'Taolanaro'),
                'Androy' => array('Ambovombe-Androy', 'Bekily', 'Beloha', 'Tsihombe'),
                'Atsimo-Andrefana' => array('Ankazoabo', 'Ampanihy', 'Benenitra', 'Beroroha', 'Betioky-Atsimo', 'Morombe', 'Sakaraha', 'Toliara I', 'Toliara II'),
                'Menabe' => array('Belo-sur-Tsiribihina', 'Mahabo', 'Manja', 'Miandrivazo', 'Morondava'),
                'Melaky' => array('Ambatomainty', 'Antsalova', 'Besalampy', 'Maintirano', 'Morafenobe'),
                'Boeny' => array('Ambato-Boeni', 'Mahajanga I', 'Mahajanga II', 'Marovoay', 'Mitsinjo', 'Soalala'),
                'Betsiboka' => array('Kandreho', 'Maevatanana', 'Tsaratanana'),
                'Sofia' => array('Analalava', 'Antsohihy', 'Bealanana', 'Befandriana-Avaratra', 'Boriziny', 'Mampikony', 'Mandritsara'),
                'Alaotra-Mangoro' => array('Ambatondrazaka', 'Amparafaravola', 'Andilamena', 'Anosibe An\'ala', 'Moramanga'),
                'Atsinanana' => array('Antanambao Manampotsy', 'Mahanoro', 'Marolambo', 'Toamasina I', 'Toamasina II', 'Vatomandry', 'Vohibinany'),
                'Analanjirofo' => array('Fenoarivo Atsinanana', 'Mananara Avaratra', 'Maroantsetra', 'Nosy Boraha', 'Soanierana Ivongo', 'Vavatenina'),
                'Diana' => array('Ambanja', 'Ambilobe', 'Antsiranana I', 'Antsiranana II', 'Nosy Be'),
                'Sava' => array('Andapa', 'Antalaha', 'Sambava', 'Vohemar')
            );
        }
        return $this->madagascar_locations;
    }

    /**
     * Personnalise les champs de facturation et de livraison.
     *
     * @param array $fields Tableau des champs de paiement WooCommerce.
     * @return array Tableau des champs modifiés.
     */
    public function customize_checkout_fields( $fields ) {
        // --- Billing Fields ---
        // Supprimer le champ 'state' par défaut si présent pour éviter les doublons
        if ( isset( $fields['billing']['billing_state'] ) ) {
            unset( $fields['billing']['billing_state'] );
        }

        // Supprimer le champ 'city' par défaut pour éviter les doublons
        if ( isset( $fields['billing']['billing_city'] ) ) {
            unset( $fields['billing']['billing_city'] );
        }

        // Renommer et ajuster le champ 'postcode'
        if ( isset( $fields['billing']['billing_postcode'] ) ) {
            $fields['billing']['billing_postcode']['label']       = __( 'Code Postal', 'wc-mg-custom-fields' );
            $fields['billing']['billing_postcode']['placeholder'] = __( 'Ex: 101', 'wc-mg-custom-fields' );
            $fields['billing']['billing_postcode']['required']    = true;
            $fields['billing']['billing_postcode']['priority']    = 80; // Après le district
            $fields['billing']['billing_postcode']['class']       = array( 'form-row-wide' ); // Assure pleine largeur
            $fields['billing']['billing_postcode']['type']        = 'text'; // S'assurer que c'est un champ texte simple
        }

        // Récupérer les régions pour le sélecteur
        $regions_data = $this->get_madagascar_locations();
        $region_options = array( '' => __( 'Sélectionnez une région...', 'wc-mg-custom-fields' ) );
        foreach ( $regions_data as $region_name => $districts ) {
            $region_options[ $region_name ] = $region_name;
        }

        // Ajouter les champs Région et District personnalisés pour la facturation
        $fields['billing']['billing_region'] = array(
            'type'        => 'select',
            'label'       => __( 'Région', 'wc-mg-custom-fields' ),
            'required'    => true,
            'class'       => array( 'form-row-wide', 'address-field', 'update_totals_on_change' ),
            'options'     => $region_options,
            'priority'    => 70, // Après la ville
        );
        $fields['billing']['billing_district'] = array(
            'type'        => 'select',
            'label'       => __( 'District / Ville', 'wc-mg-custom-fields' ),
            'required'    => true,
            'class'       => array( 'form-row-wide', 'address-field' ),
            'options'     => array( '' => __( 'Sélectionnez d\'abord une région', 'wc-mg-custom-fields' ) ),
            'priority'    => 75, // Après la région
        );


        // --- Shipping Fields ---
        // Supprimer le champ 'state' par défaut si présent pour éviter les doublons
        if ( isset( $fields['shipping']['shipping_state'] ) ) {
            unset( $fields['shipping']['shipping_state'] );
        }

        // Renommer et ajuster le champ 'city' de livraison
        if ( isset( $fields['shipping']['shipping_city'] ) ) {
            $fields['shipping']['shipping_city']['label']       = __( 'Ville', 'wc-mg-custom-fields' );
            $fields['shipping']['shipping_city']['placeholder'] = __( 'Ex: Antananarivo', 'wc-mg-custom-fields' );
            $fields['shipping']['shipping_city']['priority']    = 60;
            $fields['shipping']['shipping_city']['class']       = array( 'form-row-wide' );
        }

        // Renommer et ajuster le champ 'postcode' de livraison
        if ( isset( $fields['shipping']['shipping_postcode'] ) ) {
            $fields['shipping']['shipping_postcode']['label']       = __( 'Code Postal', 'wc-mg-custom-fields' );
            $fields['shipping']['shipping_postcode']['placeholder'] = __( 'Ex: 101', 'wc-mg-custom-fields' );
            $fields['shipping']['shipping_postcode']['required']    = true;
            $fields['shipping']['shipping_postcode']['priority']    = 80;
            $fields['shipping']['shipping_postcode']['class']       = array( 'form-row-wide' );
            $fields['shipping']['shipping_postcode']['type']        = 'text';
        }

        // Ajouter les champs Région et District personnalisés pour la livraison
        $fields['shipping']['shipping_region'] = array(
            'type'        => 'select',
            'label'       => __( 'Région', 'wc-mg-custom-fields' ),
            'required'    => true,
            'class'       => array( 'form-row-wide', 'address-field', 'update_totals_on_change' ),
            'options'     => $region_options,
            'priority'    => 70,
        );
        $fields['shipping']['shipping_district'] = array(
            'type'        => 'select',
            'label'       => __( 'District / Ville', 'wc-mg-custom-fields' ),
            'required'    => true,
            'class'       => array( 'form-row-wide', 'address-field' ),
            'options'     => array( '' => __( 'Sélectionnez d\'abord une région', 'wc-mg-custom-fields' ) ),
            'priority'    => 75,
        );

        // Réorganiser les champs par priorité après toutes les modifications
        foreach ( $fields as $field_group_key => $field_group ) {
            if ( function_exists( 'wc_checkout_fields_uasort_callback' ) ) {
                uasort( $fields[ $field_group_key ], 'wc_checkout_fields_uasort_callback' );
            }
        }

        return $fields;
    }

    /**
     * Charge le script JS pour les champs dépendants et passe les données des localités.
     * S'exécute uniquement sur la page de paiement.
     */
    public function enqueue_checkout_scripts() {
        if ( is_checkout() ) {
            wp_register_script( 'wc-mg-checkout', plugin_dir_url( __FILE__ ) . 'checkout.js', array( 'jquery', 'woocommerce', 'wc-country-select' ), '1.0.0', true );
            
            wp_localize_script( 'wc-mg-checkout', 'mwc_params', array(
                'locations'          => $this->get_madagascar_locations(),
                'select_district_text' => __( 'Sélectionnez un district...', 'wc-mg-custom-fields' ),
                'select_region_text'   => __( 'Sélectionnez une région...', 'wc-mg-custom-fields' ),
            ) );

            wp_enqueue_script( 'wc-mg-checkout' );
        }
    }

    /**
     * Valide les champs personnalisés avant de passer la commande.
     */
    public function validate_custom_fields() {
        $fields_to_validate = array( 'billing', 'shipping' );
        foreach ( $fields_to_validate as $prefix ) {
            $is_address_active = ( 'shipping' === $prefix && isset( $_POST['ship_to_different_address'] ) && '1' === $_POST['ship_to_different_address'] ) || ( 'billing' === $prefix );
            
            if ( $is_address_active ) {
                // Validation de la Région
                if ( empty( $_POST[ $prefix . '_region' ] ) ) {
                    wc_add_notice( sprintf( __( 'Veuillez sélectionner une <strong>Région</strong> pour l\'adresse de %s.', 'wc-mg-custom-fields' ), ( $prefix === 'billing' ? 'facturation' : 'livraison' ) ), 'error' );
                }

                // Validation du District
                if ( empty( $_POST[ $prefix . '_district' ] ) ) {
                    wc_add_notice( sprintf( __( 'Veuillez sélectionner un <strong>District / Ville</strong> pour l\'adresse de %s.', 'wc-mg-custom-fields' ), ( $prefix === 'billing' ? 'facturation' : 'livraison' ) ), 'error' );
                }

                // Validation du Code Postal
                if ( isset( $_POST[ $prefix . '_postcode' ] ) && ! preg_match( '/^[0-9]{3}$/', $_POST[ $prefix . '_postcode' ] ) ) {
                    wc_add_notice( sprintf( __( 'Le <strong>Code Postal</strong> malgache doit contenir exactement 3 chiffres (ex: 101) pour l\'adresse de %s.', 'wc-mg-custom-fields' ), ( $prefix === 'billing' ? 'facturation' : 'livraison' ) ), 'error' );
                }
            }
        }
    }

    /**
     * Sauvegarde les valeurs des champs personnalisés dans les métadonnées de la commande.
     *
     * @param int $order_id L'ID de la commande.
     */
    public function save_custom_fields( $order_id ) {
        $fields_to_save = array( 'billing_region', 'billing_district' ); // Champ CIN retiré
        
        if ( isset( $_POST['ship_to_different_address'] ) && '1' === $_POST['ship_to_different_address'] ) {
            $fields_to_save[] = 'shipping_region';
            $fields_to_save[] = 'shipping_district';
        }

        foreach ( $fields_to_save as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                update_post_meta( $order_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
            }
        }
    }

    /**
     * Affiche les champs personnalisés dans la page de détails de la commande (admin).
     *
     * @param WC_Order $order L'objet commande.
     */
    public function display_custom_fields_in_admin( $order ) {
        $type = ( current_filter() === 'woocommerce_admin_order_data_after_billing_address' ) ? 'billing' : 'shipping';
        
        $region = $order->get_meta( "_{$type}_region", true );
        $district = $order->get_meta( "_{$type}_district", true );

        if ( $region ) {
            echo '<p><strong>' . __( 'Région', 'wc-mg-custom-fields' ) . ':</strong> ' . esc_html( $region ) . '</p>';
        }
        if ( $district ) {
            echo '<p><strong>' . __( 'District / Ville', 'wc-mg-custom-fields' ) . ':</strong> ' . esc_html( $district ) . '</p>';
        }
        // Ligne de CIN retirée ici
    }

    /**
     * Ajoute nos champs personnalisés au format d'adresse de WooCommerce.
     *
     * @param array $formats Formats d'adresse WooCommerce.
     * @return array Formats d'adresse modifiés.
     */
    public function set_address_format( $formats ) {
        // Champ CIN retiré du format d'adresse
        $formats['MG'] = "{name}\n{company}\n{address_1}\n{address_2}\n{district}\n{city} {postcode}\n{region}\n{country}";
        return $formats;
    }

    /**
     * Définit comment remplacer les placeholders de nos champs dans le format d'adresse.
     *
     * @param array $replacements Tableau des remplacements de format d'adresse.
     * @param array $args         Arguments de l'adresse.
     * @return array Tableau des remplacements mis à jour.
     */
    public function set_address_format_replacements( $replacements, $args ) {
        $replacements['{region}']   = ! empty( $args['region'] ) ? $args['region'] : '';
        $replacements['{district}'] = ! empty( $args['district'] ) ? $args['district'] : '';
        // Ligne de remplacement CIN retirée
        return $replacements;
    }

    /**
     * Force la devise de WooCommerce à MGA (Ariary Malgache).
     *
     * @return string Code de devise MGA.
     */
    public function force_madagascar_currency() {
        return 'MGA';
    }

    /**
     * Ajoute le symbole de l'Ariary Malgache si non existant.
     *
     * @param array $currencies Tableau des devises WooCommerce.
     * @return array Tableau des devises mis à jour.
     */
    public function add_madagascar_currency_symbol( $currencies ) {
        if ( ! isset( $currencies['MGA'] ) ) {
            $currencies['MGA'] = __( 'Ariary Malgache', 'wc-mg-custom-fields' );
        }
        return $currencies;
    }
}

// Instancier le plugin
new WC_Madagascar_Checkout_Customizer();