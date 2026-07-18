# Modale Graphiques de Consommation IoTaWatt

## 📊 Description

Cette modale affiche les **statistiques de consommation** de tous vos équipements IoTaWatt sur une période configurable, avec une liste détaillée permettant d'accéder à l'historique de chaque équipement.

## ✨ Fonctionnalités

### Liste des équipements
- Visualisation de tous les équipements avec consommation
- Code couleur unique par équipement
- Total de consommation sur la période pour chaque équipement
- Bouton d'accès rapide à l'historique détaillé (graphique natif Jeedom)

### Statistiques globales
Quatre cartes affichent :
- **Total sur la période** : Somme de toutes les consommations
- **Moyenne quotidienne** : Consommation moyenne par jour
- **Jour max** : Jour avec la plus forte consommation
- **Jour min** : Jour avec la plus faible consommation

### Contrôles
- **Sélecteur de période** : 7, 14, 21 ou 30 jours
- **Bouton Actualiser** : Recharge les données
- **Clic sur équipement** : Ouvre l'historique détaillé dans une modale

### Accès aux graphiques Jeedom
- Clic sur l'icône graphique pour chaque équipement
- Ouvre la modale native `cmd.history` de Jeedom
- Tous les outils d'analyse Jeedom disponibles

## 🎨 Design

- Style cohérent avec les autres modales du plugin
- Cartes de statistiques avec dégradés de couleurs
- Liste interactive des équipements
- Responsive et adaptatif
- Icônes Font Awesome
- Animations au survol

## 📁 Fichiers

- `desktop/modal/chart.php` : Interface et logique PHP
- `desktop/js/chart.js` : Non utilisé (conservé pour évolutions futures)
- `core/class/iotawatt.power.class.php` : Classes de données réutilisées

## 🔧 Utilisation

### Depuis la page du plugin
1. Cliquer sur la carte **"Graphiques"**
2. La modale s'ouvre avec 7 jours par défaut
3. Sélectionner la période souhaitée
4. Cliquer sur l'icône graphique d'un équipement pour voir son historique détaillé

### Paramètres d'URL
```
index.php?v=d&plugin=iotawatt&modal=chart&days=14
```

## 💡 Améliorations futures possibles

- [ ] Graphique empilé avec Chart.js ou bibliothèque similaire
- [ ] Export en PDF avec statistiques
- [ ] Comparaison de périodes
- [ ] Filtrage par équipement
- [ ] Prévisions basées sur l'historique
- [ ] Export des données en CSV
- [ ] Graphiques par catégorie d'équipement

## 🐛 Dépannage

### Les statistiques ne s'affichent pas
- Vérifier que les équipements ont l'historique activé
- Vérifier que des données existent pour la période sélectionnée
- Ouvrir la console JavaScript pour voir les erreurs

### Données manquantes
- S'assurer que les commandes de consommation sont bien configurées
- Vérifier que `totalConsumption` est à `true` dans la configuration
- Vérifier les statistiques Jeedom

### L'historique ne s'ouvre pas
- Vérifier que l'ID de commande est valide
- S'assurer que la commande a l'historique activé

## 📝 Notes techniques

- Utilise l'API native Jeedom pour les graphiques (`cmd.history`)
- Récupération via `getStatistique()` de Jeedom
- Calcul en Wh avec conversion automatique
- Couleurs générées algorithmiquement
- Support de 1 à 30 jours d'historique
- JavaScript vanilla (pas de dépendance jQuery)
