// Gestion des modals et formulaires pour voyage-detail.php

function openModal(type, etapeId) {
    const modal = document.getElementById('modal-' + type);
    const etapeInput = document.getElementById(type + '_etape_id');
    
    if (modal && etapeInput) {
        etapeInput.value = etapeId;
        modal.classList.add('active');
        
        // Scroll vers le haut du modal
        modal.scrollTop = 0;
    }
}

function closeModal(type) {
    const modal = document.getElementById('modal-' + type);
    if (modal) {
        modal.classList.remove('active');
        
        // Reset du formulaire
        const form = document.getElementById('form-' + type);
        if (form) {
            form.reset();
        }
    }
}

// Fermer modal en cliquant en dehors
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        const modals = ['hebergement', 'essence', 'vivres', 'visite'];
        modals.forEach(type => {
            if (e.target.id === 'modal-' + type) {
                closeModal(type);
            }
        });
    }
});

// Fermer modal avec la touche Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.active');
        modals.forEach(modal => {
            modal.classList.remove('active');
        });
    }
});

function submitForm(event, type) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Afficher un indicateur de chargement
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Ajout en cours...';
    submitBtn.disabled = true;
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recharger la page pour afficher le nouvel élément
            location.reload();
        } else {
            alert('Erreur: ' + (data.message || 'Impossible d\'ajouter l\'élément'));
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur s\'est produite. Veuillez réessayer.');
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

// Gestion automatique du calcul du prix total pour l'essence
document.addEventListener('DOMContentLoaded', function() {
    const formEssence = document.getElementById('form-essence');
    if (formEssence) {
        const quantiteInput = formEssence.querySelector('input[name="quantite"]');
        const prixLitreInput = formEssence.querySelector('input[name="prix_litre"]');
        const prixTotalInput = formEssence.querySelector('input[name="prix_total"]');
        
        function calculerPrixTotal() {
            const quantite = parseFloat(quantiteInput.value) || 0;
            const prixLitre = parseFloat(prixLitreInput.value) || 0;
            
            if (quantite > 0 && prixLitre > 0) {
                prixTotalInput.value = (quantite * prixLitre).toFixed(2);
            }
        }
        
        quantiteInput.addEventListener('input', calculerPrixTotal);
        prixLitreInput.addEventListener('input', calculerPrixTotal);
    }
    
    // Gestion du checkbox "gratuit" pour les visites
    const formVisite = document.getElementById('form-visite');
    if (formVisite) {
        const gratuitCheckbox = formVisite.querySelector('input[name="gratuit"]');
        const prixInputs = [
            formVisite.querySelector('input[name="prix_adulte"]'),
            formVisite.querySelector('input[name="prix_enfant"]'),
            formVisite.querySelector('input[name="prix_total"]')
        ];
        
        gratuitCheckbox.addEventListener('change', function() {
            prixInputs.forEach(input => {
                input.disabled = this.checked;
                if (this.checked) {
                    input.value = '';
                }
            });
        });
    }
    
    // Gestion du checkbox "sur terrain" pour les vivres
    const formVivres = document.getElementById('form-vivres');
    if (formVivres) {
        const surTerrainCheckbox = formVivres.querySelector('input[name="sur_terrain"]');
        const distanceInput = formVivres.querySelector('input[name="distance_terrain"]');
        
        surTerrainCheckbox.addEventListener('change', function() {
            if (this.checked) {
                distanceInput.value = '0';
            }
        });
    }
});
