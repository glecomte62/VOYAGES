# 📋 Module Voyages - Documentation

## Vue d'ensemble

Le module Voyages permet de planifier et documenter des voyages en ULM ou petit avion avec :
- ✈️ **Itinéraires multi-étapes** : Planifiez votre route avec plusieurs terrains
- 🏨 **Hébergements** : Hôtels, campings, gîtes, chambres d'hôtes
- ⛽ **Ravitaillements essence** : AVGAS, MOGAS, carburants disponibles
- 🍽️ **Ravitaillements vivres** : Restaurants, marchés, commerces
- 🏛️ **Visites culturelles** : Monuments, musées, sites naturels, activités

## Installation

### 1. Migration de la base de données

Exécutez le script de migration pour créer les tables nécessaires :

```bash
php migrate_voyages.php
```

Ou exécutez manuellement le fichier SQL :

```bash
mysql -u votre_user -p votre_base < sql/create_voyage_module.sql
```

### 2. Tables créées

- `voyage_etapes` : Les étapes de chaque voyage (terrains visités)
- `voyage_hebergements` : Les nuitées et hébergements
- `voyage_ravitaillements_essence` : Les ravitaillements en carburant
- `voyage_ravitaillements_vivres` : Les restaurants et commerces
- `voyage_visites` : Les visites culturelles et activités
- `voyage_photos` : Les photos associées aux voyages

La table `voyages` existante est enrichie avec de nouveaux champs.

## Utilisation

### Créer un nouveau voyage

1. Allez sur **Voyages** dans le menu
2. Cliquez sur **+ Nouveau Voyage**
3. Remplissez les informations :
   - Titre et description
   - Dates de début et fin
   - Aéronef utilisé
   - Nombre de passagers
   - Budget estimé
   - Visibilité (privé ou public)

### Planifier l'itinéraire

Après création du voyage, vous êtes redirigé vers le planificateur d'itinéraire :

1. **Rechercher un terrain** : Tapez le nom ou le code OACI
2. **Sélectionner** : Cliquez sur le terrain dans la liste
3. **Ajouter des détails** :
   - Dates/heures d'arrivée et départ
   - Notes spécifiques pour cette étape
4. **Répéter** pour chaque étape du voyage

Les étapes sont numérotées automatiquement dans l'ordre d'ajout.

### Ajouter des informations complémentaires

Sur la page de détail du voyage, pour chaque étape vous pouvez ajouter :

#### 🏨 Hébergements
- Type (hôtel, camping, gîte, etc.)
- Nom et coordonnées
- Dates de check-in/check-out
- Prix et réservation
- Note et commentaires

#### ⛽ Ravitaillements essence
- Type de carburant (AVGAS 100LL, MOGAS, UL91, etc.)
- Quantité et prix
- Disponibilité sur le terrain
- Horaires et modalités (self-service, CB)
- Notes pratiques

#### 🍽️ Ravitaillements vivres
- Type d'établissement (restaurant, supermarché, etc.)
- Nom et localisation
- Distance du terrain
- Spécialités
- Prix et évaluation

#### 🏛️ Visites culturelles
- Type (monument, musée, site naturel, etc.)
- Description et localisation
- Horaires et durée de visite
- Tarifs (adulte, enfant)
- Réservation requise ou non
- Recommandations et avis

### Visualiser un voyage

La page de détail affiche :
- En-tête avec les informations générales
- Chaque étape avec toutes ses informations
- Cartes organisées par type (hébergements, ravitaillements, visites)
- Notes et évaluations

### Partager un voyage

Si vous marquez un voyage comme "public", les autres membres pourront :
- Voir votre itinéraire complet
- Consulter vos recommandations
- S'inspirer pour leurs propres voyages

## Structure des fichiers

```
pages/
  ├── voyages.php              # Liste des voyages
  ├── voyage-new.php           # Création d'un voyage
  ├── voyage-planner.php       # Planification d'itinéraire
  ├── voyage-detail.php        # Affichage détaillé
  └── voyage-modals.php        # Formulaires modaux

assets/
  └── js/
      └── voyage-detail.js     # Scripts pour les modals

sql/
  └── create_voyage_module.sql # Schéma de base de données

migrate_voyages.php            # Script de migration
```

## Fonctionnalités à venir

- [ ] Carte interactive avec traçage de route
- [ ] Calcul automatique des distances et temps de vol
- [ ] Export PDF de l'itinéraire
- [ ] Import/export GPX
- [ ] Synchronisation avec calendrier
- [ ] Partage sur réseaux sociaux
- [ ] Statistiques de voyage (km parcourus, budget, etc.)
- [ ] Galerie photos améliorée
- [ ] Recherche et filtres avancés
- [ ] Duplication de voyage pour réutilisation

## API

### Endpoints disponibles (AJAX)

**POST voyage-planner.php**
- `action=add_etape` : Ajouter une étape
- `action=delete_etape` : Supprimer une étape
- `action=reorder_etapes` : Réorganiser les étapes

**POST voyage-detail.php**
- `action=add_hebergement` : Ajouter un hébergement
- `action=add_ravitaillement_essence` : Ajouter ravitaillement essence
- `action=add_ravitaillement_vivres` : Ajouter ravitaillement vivres
- `action=add_visite` : Ajouter une visite

Toutes les réponses sont au format JSON :
```json
{
  "success": true,
  "id": 123
}
```

## Support

Pour toute question ou suggestion :
- Ouvrez une issue sur GitHub
- Contactez l'équipe de développement

---

**Version:** 1.0.0  
**Date:** Décembre 2024  
**Auteur:** Projet VOYAGES ULM
