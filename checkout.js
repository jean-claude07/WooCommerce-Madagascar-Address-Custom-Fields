// wp-content/plugins/votre-plugin-folder/checkout.js
jQuery(function($) {
    const mwc_params = window.mwc_params;
    const locations = mwc_params.locations;
    const selectDistrictText = mwc_params.select_district_text;

    /**
     * Met à jour les options du champ District en fonction de la Région sélectionnée.
     * @param {string} prefix - 'billing' ou 'shipping'
     * @param {string|null} initialDistrict - Le district à présélectionner après la mise à jour.
     */
    function updateDistricts(prefix, initialDistrict = null) {
        const regionSelect = $('#' + prefix + '_region');
        const districtSelect = $('#' + prefix + '_district');
        const selectedRegion = regionSelect.val();

        // Stocker la valeur actuelle du district avant de vider, pour tenter de la restaurer.
        const currentDistrictValue = districtSelect.val();

        districtSelect.empty();
        districtSelect.append($('<option></option>').attr('value', '').text(selectDistrictText));

        if (selectedRegion && locations[selectedRegion]) {
            $.each(locations[selectedRegion], function(_index, district) {
                districtSelect.append($('<option></option>').attr('value', district).text(district));
            });
        }

        // Tenter de restaurer le district initial ou le district précédemment sélectionné.
        if (initialDistrict && districtSelect.find('option[value="' + initialDistrict + '"]').length) {
            districtSelect.val(initialDistrict);
        } else if (currentDistrictValue && districtSelect.find('option[value="' + currentDistrictValue + '"]').length) {
            districtSelect.val(currentDistrictValue);
        } else {
            districtSelect.val('');
        }
    }

    // Gérer le changement de région initié par l'utilisateur
    $('body').on('change', '#billing_region, #shipping_region', function() {
        const prefix = $(this).attr('id').replace('_region', '');
        updateDistricts(prefix);
        // Déclencher la mise à jour du checkout UNIQUEMENT quand la région change par interaction utilisateur
        $(document.body).trigger('update_checkout');
    });

    // Fonction d'initialisation commune pour le chargement et les mises à jour AJAX
    function initializeAddressFields() {
        // Initialisation pour l'adresse de facturation
        const initialBillingRegion = $('#billing_region').val();
        const initialBillingDistrict = $('#billing_district').val();
        if (initialBillingRegion) {
            // Passer le district actuel au cas où il serait déjà sélectionné (par exemple, après un rafraîchissement)
            updateDistricts('billing', initialBillingDistrict);
        }

        // Initialisation pour l'adresse de livraison si elle est activée
        if ($('#shipping_region').length && $('#ship-to-different-address-checkbox').is(':checked')) {
            const initialShippingRegion = $('#shipping_region').val();
            const initialShippingDistrict = $('#shipping_district').val();
            if (initialShippingRegion) {
                updateDistricts('shipping', initialShippingDistrict);
            }
        }
    }

    // Exécuter l'initialisation au chargement complet du document
    $(document).ready(function() {
        initializeAddressFields();
    });

    // Écouter l'événement de mise à jour du checkout de WooCommerce (pour les rechargements AJAX)
    $(document.body).on('updated_checkout', function() {
        initializeAddressFields();
    });
});