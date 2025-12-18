-- =============================================
-- Nettoyage et correction des données de test
-- À exécuter dans phpMyAdmin
-- =============================================

USE kica7829_voyages;

-- Supprimer les destinations de test avec photo incorrecte
DELETE FROM destinations WHERE photo_principale = 'Array';

-- Supprimer toutes les destinations de test existantes
DELETE FROM destinations WHERE code_oaci = 'LFQJ';

-- Réinsérer proprement la destination de test
INSERT INTO destinations (
    code_oaci, nom, aerodrome, ville, pays, latitude, longitude,
    type_piste, longueur_piste_m, frequence_radio,
    carburant, restaurant, hebergement,
    acces_ulm, acces_avion,
    description, points_interet, photo_principale,
    created_by, actif, created_at
) VALUES (
    'LFQJ', 
    'Test Destination - Île-de-France', 
    'Aérodrome de Test', 
    'Paris', 
    'France', 
    48.8566, 
    2.3522,
    'dur', 
    800, 
    '123.50',
    1, 1, 1,
    1, 1,
    'Destination de test pour vérifier l\'affichage des photos et toutes les fonctionnalités de l\'application.',
    '🏨 Hôtel Test - Hôtel 3 étoiles
📍 12 Avenue de la République, 75000 Paris
🌍 GPS: 48.8566, 2.3522
📞 +33 1 23 45 67 89
💰 80-120€/nuit
🚶 5 min à pied de l\'aérodrome

🍽️ Restaurant Le Test - Restaurant traditionnel
📍 5 Rue de Test, 75000 Paris  
🌍 GPS: 48.8570, 2.3530
📞 +33 1 98 76 54 32
💰 Menu 25-40€
🚶 10 min à pied',
    'test.jpg',
    1, 
    1, 
    NOW()
);

-- Vérifier que l'insertion est correcte
SELECT id, code_oaci, nom, photo_principale, LENGTH(photo_principale) as photo_length
FROM destinations 
WHERE code_oaci = 'LFQJ';
