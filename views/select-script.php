<script>
    // Utilisation de jQuery pour l'initialisation de Select2
    $(document).ready(function() {
        // Initialisation de Select2 pour le champ produit
        $('#id_produit').select2({
            dropdownParent: $('#addEditModal'), // Permet au menu déroulant de s'afficher correctement dans la modale
            placeholder: "Tapez pour chercher un produit...",
            allowClear: true
        });
        
        // Sélecteurs d'éléments existants
        const openAddLineModalBtn = $('#openAddLineModalBtn');
        const addEditModal = $('#addEditModal');
        const closeAddEditModalBtn = $('#closeAddEditModalBtn');
        const saleForm = document.getElementById('saleForm');

        const openNewOrderModalBtn = $('#openNewOrderModalBtn'); 
        const newOrderModal = $('#newOrderModal'); 
        const closeNewOrderModalBtn = $('#closeNewOrderModalBtn');
        
        // Éléments pour le formulaire d'ajout de produit
        const idProduitSelect = $('#id_produit');
        const quantiteInput = $('#quantite');
        const prixTotalInput = document.getElementById('prix_total');
        const stockAlert = document.getElementById('stockAlert');
        const submitBtn = document.getElementById('submitBtn');

        function openModal(modal) {
            modal.removeClass('hidden').addClass('flex');
            // Réinitialise Select2 à l'ouverture de la modale
            if(modal.attr('id') === 'addEditModal') {
                $('#id_produit').val(null).trigger('change'); // Efface la sélection précédente
                $('#id_produit').select2('open'); // Ouvre immédiatement le champ de recherche
            }
        }

        function closeModal(modal) {
            modal.addClass('hidden').removeClass('flex');
        }

        // --- Logique d'ouverture/fermeture des modales ---

        // 1. Ouvrir le modal "Ajouter un produit"
        if (openAddLineModalBtn.length) {
            openAddLineModalBtn.on('click', () => {
                saleForm.reset();
                prixTotalInput.value = '';
                stockAlert.classList.add('hidden');
                submitBtn.disabled = false;
                openModal(addEditModal);
            });
        }

        // 2. Ouvrir le modal "Nouvelle Commande"
        if (openNewOrderModalBtn.length) {
            openNewOrderModalBtn.on('click', () => {
                openModal(newOrderModal);
            });
        }

        // Fermeture des modales
        closeAddEditModalBtn.on('click', () => closeModal(addEditModal));
        if (closeNewOrderModalBtn.length) {
             closeNewOrderModalBtn.on('click', () => closeModal(newOrderModal));
        }

        // --- Logique de calcul et de vérification de stock ---
        function calculateTotalPrice() {
            // Utilise l'objet Select2 pour récupérer la sélection
            const selectedOption = idProduitSelect.find(':selected');
            const prixUnitaire = selectedOption.data('prix');
            const stockDisponible = parseInt(selectedOption.data('stock'));
            const quantite = parseInt(quantiteInput.val());

            // Réinitialisation
            prixTotalInput.value = '';
            stockAlert.classList.add('hidden');
            submitBtn.disabled = false;

            if (prixUnitaire && quantite && quantite > 0) {
                // Vérification de stock
                if (quantite > stockDisponible) {
                    stockAlert.textContent = `Stock insuffisant ! (${stockDisponible} restants)`;
                    stockAlert.classList.remove('hidden');
                    submitBtn.disabled = true; 
                    return; 
                }

                // Calcul
                const total = parseFloat(prixUnitaire) * quantite;
                prixTotalInput.value = total.toFixed(2);
            }
        }

        // Écouteurs d'événements pour le calcul automatique et la vérification
        // Select2 déclenche l'événement 'change'
        idProduitSelect.on('change', calculateTotalPrice);
        quantiteInput.on('input', calculateTotalPrice);
    });
</script>