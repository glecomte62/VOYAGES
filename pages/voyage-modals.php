<!-- Modals pour ajouter des éléments aux étapes -->

<!-- Modal Hébergement -->
<div class="modal" id="modal-hebergement">
    <div class="modal-content">
        <div class="modal-header">
            <h3>🏨 Ajouter un hébergement</h3>
            <button class="close-modal" onclick="closeModal('hebergement')">×</button>
        </div>
        <form id="form-hebergement" onsubmit="submitForm(event, 'hebergement')">
            <input type="hidden" name="action" value="add_hebergement">
            <input type="hidden" name="etape_id" id="hebergement_etape_id">
            
            <div class="form-group">
                <label>Type d'hébergement *</label>
                <select name="type" required>
                    <option value="hotel">Hôtel</option>
                    <option value="camping">Camping</option>
                    <option value="gite">Gîte</option>
                    <option value="chambre_hote">Chambre d'hôtes</option>
                    <option value="bivouac">Bivouac</option>
                    <option value="autre">Autre</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Nom *</label>
                <input type="text" name="nom" required placeholder="Nom de l'hébergement">
            </div>
            
            <div class="form-group">
                <label>Adresse</label>
                <textarea name="adresse" placeholder="Adresse complète"></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="tel" name="telephone" placeholder="+33...">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="contact@...">
                </div>
            </div>
            
            <div class="form-group">
                <label>Site web</label>
                <input type="url" name="site_web" placeholder="https://...">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Date d'arrivée *</label>
                    <input type="date" name="date_checkin" required>
                </div>
                <div class="form-group">
                    <label>Date de départ *</label>
                    <input type="date" name="date_checkout" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Prix total (€)</label>
                    <input type="number" name="prix_total" step="0.01" min="0" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Note (sur 5)</label>
                    <select name="note">
                        <option value="">Non noté</option>
                        <option value="1">⭐</option>
                        <option value="2">⭐⭐</option>
                        <option value="3">⭐⭐⭐</option>
                        <option value="4">⭐⭐⭐⭐</option>
                        <option value="5">⭐⭐⭐⭐⭐</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" name="reserve" id="hebergement_reserve" value="1">
                <label for="hebergement_reserve">Déjà réservé</label>
            </div>
            
            <div class="form-group">
                <label>Numéro de réservation</label>
                <input type="text" name="numero_reservation" placeholder="Référence de réservation">
            </div>
            
            <div class="form-group">
                <label>Commentaire</label>
                <textarea name="commentaire" placeholder="Votre avis, remarques..."></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Ajouter</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('hebergement')">Annuler</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ravitaillement Essence -->
<div class="modal" id="modal-essence">
    <div class="modal-content">
        <div class="modal-header">
            <h3>⛽ Ajouter un ravitaillement essence</h3>
            <button class="close-modal" onclick="closeModal('essence')">×</button>
        </div>
        <form id="form-essence" onsubmit="submitForm(event, 'essence')">
            <input type="hidden" name="action" value="add_ravitaillement_essence">
            <input type="hidden" name="etape_id" id="essence_etape_id">
            
            <div class="form-group">
                <label>Type de carburant *</label>
                <select name="type_carburant" required>
                    <option value="avgas_100ll">AVGAS 100LL</option>
                    <option value="mogas_95">MOGAS 95</option>
                    <option value="mogas_98">MOGAS 98</option>
                    <option value="ul91">UL91</option>
                    <option value="jet_a1">JET A1</option>
                    <option value="autre">Autre</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Lieu</label>
                <input type="text" name="lieu" placeholder="Nom de la station, pompe...">
            </div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" name="disponible_terrain" id="essence_terrain" value="1" checked>
                <label for="essence_terrain">Disponible sur le terrain</label>
            </div>
            
            <div class="form-group">
                <label>Fournisseur</label>
                <input type="text" name="fournisseur" placeholder="Nom du fournisseur">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Quantité (L)</label>
                    <input type="number" name="quantite" step="0.01" min="0" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Prix au litre (€)</label>
                    <input type="number" name="prix_litre" step="0.001" min="0" placeholder="0.000">
                </div>
            </div>
            
            <div class="form-group">
                <label>Prix total (€)</label>
                <input type="number" name="prix_total" step="0.01" min="0" placeholder="0.00">
            </div>
            
            <div class="form-group">
                <label>Date et heure du ravitaillement</label>
                <input type="datetime-local" name="date_ravitaillement">
            </div>
            
            <div class="form-group">
                <label>Horaires d'ouverture</label>
                <input type="text" name="horaires_ouverture" placeholder="Ex: 8h-18h">
            </div>
            
            <div class="form-group checkbox-group" style="margin-bottom: 0.5rem;">
                <input type="checkbox" name="self_service" id="essence_self" value="1">
                <label for="essence_self">Self-service</label>
            </div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" name="carte_acceptee" id="essence_carte" value="1">
                <label for="essence_carte">Carte bancaire acceptée</label>
            </div>
            
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" placeholder="Informations complémentaires..."></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Ajouter</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('essence')">Annuler</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Ravitaillement Vivres -->
<div class="modal" id="modal-vivres">
    <div class="modal-content">
        <div class="modal-header">
            <h3>🍽️ Ajouter un ravitaillement vivres</h3>
            <button class="close-modal" onclick="closeModal('vivres')">×</button>
        </div>
        <form id="form-vivres" onsubmit="submitForm(event, 'vivres')">
            <input type="hidden" name="action" value="add_ravitaillement_vivres">
            <input type="hidden" name="etape_id" id="vivres_etape_id">
            
            <div class="form-group">
                <label>Type d'établissement *</label>
                <select name="type" required>
                    <option value="restaurant">Restaurant</option>
                    <option value="supermarche">Supermarché</option>
                    <option value="marche">Marché</option>
                    <option value="boulangerie">Boulangerie</option>
                    <option value="bar">Bar/Café</option>
                    <option value="autre">Autre</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Nom *</label>
                <input type="text" name="nom" required placeholder="Nom de l'établissement">
            </div>
            
            <div class="form-group">
                <label>Adresse</label>
                <textarea name="adresse" placeholder="Adresse complète"></textarea>
            </div>
            
            <div class="form-group">
                <label>Téléphone</label>
                <input type="tel" name="telephone" placeholder="+33...">
            </div>
            
            <div class="form-group">
                <label>Date et heure de visite</label>
                <input type="datetime-local" name="date_visite">
            </div>
            
            <div class="form-group">
                <label>Horaires</label>
                <input type="text" name="horaires" placeholder="Ex: 12h-14h, 19h-22h">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Distance du terrain (km)</label>
                    <input type="number" name="distance_terrain" step="0.1" min="0" placeholder="0.0">
                </div>
                <div class="form-group checkbox-group" style="align-items: flex-end;">
                    <input type="checkbox" name="sur_terrain" id="vivres_terrain" value="1">
                    <label for="vivres_terrain">Sur le terrain</label>
                </div>
            </div>
            
            <div class="form-group">
                <label>Spécialité</label>
                <input type="text" name="specialite" placeholder="Plat spécial, produit notable...">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Prix total (€)</label>
                    <input type="number" name="prix_total" step="0.01" min="0" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Note (sur 5)</label>
                    <select name="note">
                        <option value="">Non noté</option>
                        <option value="1">⭐</option>
                        <option value="2">⭐⭐</option>
                        <option value="3">⭐⭐⭐</option>
                        <option value="4">⭐⭐⭐⭐</option>
                        <option value="5">⭐⭐⭐⭐⭐</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Commentaire</label>
                <textarea name="commentaire" placeholder="Votre avis, recommandations..."></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Ajouter</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('vivres')">Annuler</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Visite Culturelle -->
<div class="modal" id="modal-visite">
    <div class="modal-content">
        <div class="modal-header">
            <h3>🏛️ Ajouter une visite culturelle</h3>
            <button class="close-modal" onclick="closeModal('visite')">×</button>
        </div>
        <form id="form-visite" onsubmit="submitForm(event, 'visite')">
            <input type="hidden" name="action" value="add_visite">
            <input type="hidden" name="etape_id" id="visite_etape_id">
            
            <div class="form-group">
                <label>Type de visite *</label>
                <select name="type" required>
                    <option value="monument">Monument</option>
                    <option value="musee">Musée</option>
                    <option value="site_naturel">Site naturel</option>
                    <option value="ville">Ville</option>
                    <option value="evenement">Événement</option>
                    <option value="activite">Activité</option>
                    <option value="autre">Autre</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Nom *</label>
                <input type="text" name="nom" required placeholder="Nom du lieu ou de l'activité">
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" placeholder="Décrivez le lieu, l'activité..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Adresse</label>
                <textarea name="adresse" placeholder="Adresse complète"></textarea>
            </div>
            
            <div class="form-group">
                <label>Distance du terrain (km)</label>
                <input type="number" name="distance_terrain" step="0.1" min="0" placeholder="0.0">
            </div>
            
            <div class="form-group">
                <label>Date de visite</label>
                <input type="date" name="date_visite">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Heure de début</label>
                    <input type="time" name="heure_debut">
                </div>
                <div class="form-group">
                    <label>Heure de fin</label>
                    <input type="time" name="heure_fin">
                </div>
            </div>
            
            <div class="form-group">
                <label>Durée estimée (minutes)</label>
                <input type="number" name="duree_visite" min="0" placeholder="Ex: 90">
            </div>
            
            <div class="form-group">
                <label>Horaires d'ouverture</label>
                <textarea name="horaires_ouverture" placeholder="Ex: Mar-Dim 10h-18h"></textarea>
            </div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" name="gratuit" id="visite_gratuit" value="1">
                <label for="visite_gratuit">Gratuit</label>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Prix adulte (€)</label>
                    <input type="number" name="prix_adulte" step="0.01" min="0" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Prix enfant (€)</label>
                    <input type="number" name="prix_enfant" step="0.01" min="0" placeholder="0.00">
                </div>
            </div>
            
            <div class="form-group">
                <label>Prix total (€)</label>
                <input type="number" name="prix_total" step="0.01" min="0" placeholder="0.00">
            </div>
            
            <div class="form-group checkbox-group">
                <input type="checkbox" name="reservation_requise" id="visite_reservation" value="1">
                <label for="visite_reservation">Réservation requise</label>
            </div>
            
            <div class="form-group">
                <label>Numéro de réservation</label>
                <input type="text" name="numero_reservation" placeholder="Référence de réservation">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Téléphone</label>
                    <input type="tel" name="telephone" placeholder="+33...">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="contact@...">
                </div>
            </div>
            
            <div class="form-group">
                <label>Site web</label>
                <input type="url" name="site_web" placeholder="https://...">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Note (sur 5)</label>
                    <select name="note">
                        <option value="">Non noté</option>
                        <option value="1">⭐</option>
                        <option value="2">⭐⭐</option>
                        <option value="3">⭐⭐⭐</option>
                        <option value="4">⭐⭐⭐⭐</option>
                        <option value="5">⭐⭐⭐⭐⭐</option>
                    </select>
                </div>
                <div class="form-group checkbox-group" style="align-items: flex-end;">
                    <input type="checkbox" name="recommande" id="visite_recommande" value="1" checked>
                    <label for="visite_recommande">Recommandé</label>
                </div>
            </div>
            
            <div class="form-group">
                <label>Commentaire</label>
                <textarea name="commentaire" placeholder="Votre avis, conseils..."></textarea>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Ajouter</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal('visite')">Annuler</button>
            </div>
        </form>
    </div>
</div>
