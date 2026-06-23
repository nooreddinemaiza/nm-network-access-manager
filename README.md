# FreeRADIUS Captive Portal Management Platform

Plateforme web de gestion centralisée des utilisateurs d’un portail captif basée sur FreeRADIUS, développée en PHP orienté objet.

---

## 📌 Présentation

Cette plateforme permet de simplifier et centraliser la gestion des utilisateurs d’un portail captif, en remplaçant les manipulations manuelles dans la base de données FreeRADIUS par une interface web complète, sécurisée et automatisée.

Elle offre une gestion avancée des utilisateurs, groupes, politiques d’accès, ainsi qu’un système de statistiques détaillées sur l’activité réseau.

---

## 🚀 Fonctionnalités principales

### 👤 Gestion des utilisateurs
- Création, modification et suppression des utilisateurs
- Comptes permanents ou temporaires (expirables)
- Activation, suspension ou désactivation des comptes
- Changement sécurisé des mots de passe avec chiffrement

### 👥 Gestion des groupes
- Création et organisation des utilisateurs en groupes
- Application centralisée des politiques d’accès
- Héritage automatique des règles par les membres

### 🛡️ Politiques d’accès
- Création et gestion des règles d’accès réseau
- Application sur utilisateurs ou groupes
- Intégration directe avec FreeRADIUS

### 👨‍💼 Système de rôles
- Administrateur global avec accès total
- Modérateurs avec permissions limitées par groupe
- Gestion des droits fine et sécurisée

### 📊 Statistiques et monitoring
- Consommation upload / download par utilisateur et groupe
- Temps de connexion
- Utilisateurs actifs en temps réel
- Top consommateurs réseau
- Sites les plus visités

---

## ⚙️ Phase de configuration initiale

Avant utilisation, une phase de configuration est obligatoire :

### 1. Base de données FreeRADIUS
La base de données doit contenir les tables suivantes :

- `nas`
- `nasreload`
- `radacct`
- `radcheck`
- `radgroupcheck`
- `radgroupreply`
- `radpostauth`
- `radreply`
- `radusergroup`

⚠️ Si une de ces tables est absente, l’installation sera bloquée.

---

### 2. Extensions de la base de données

La plateforme ajoute automatiquement :
- nouvelles tables
- vues SQL
- procédures stockées
- événements

👉 L’utilisateur MySQL doit avoir les permissions complètes pour créer ces objets.

---

### 3. Permissions système

Le serveur web (Apache) doit avoir les droits sur :

- les fichiers de la plateforme
- le fichier `.env`
- les logs système
- les fichiers de configuration

---

## 📡 Intégration DNS / pfSense (statistiques des sites visités)

La plateforme exploite les logs DNS envoyés par pfSense ou un serveur DNS vers le serveur web.

### Scripts utilisés :

- `dns_extractor.sh`  
  → extrait les logs DNS du système Linux et les classe par date

- `dns-daily-sync.sh`  
  → traitement quotidien automatique des logs

### Fichier central :

```

pfsense_dns_today.log

````

Ce fichier doit être :
- généré quotidiennement
- accessible par l’utilisateur Apache

---

### API de traitement des logs

Le script envoie des requêtes HTTP pour déclencher les workers :

```bash
API_URL="http://192.168.0.20/cron/update-log"
````
Il faut modifier l'adresse dans le script avant de l'ajouter à votre système
---

## 🔁 Traitement des logs (Job Workers)

Les logs DNS sont traités via un système de workers :

* traitement en batch
* gestion de gros volumes de données
* optimisation des performances
* insertion en base de données analytique

---

## 🔐 Intégration FreeRADIUS

### Configuration requise :

* FreeRADIUS doit utiliser SQL
* même base de données que la plateforme

### Modification importante :

Par défaut FreeRADIUS utilise :

* `radcheck`
* `radreply`

👉 Avec cette plateforme, remplacer par :

* `radcheck_view`
* `radreply_view`

Dans :

```
/etc/freeradius/3.0/mods-enabled/sql
```

Paramètres :

* `authcheck_table`
* `authreply_table`

---

## 🔒 Sécurité

* Chiffrement complet des mots de passe
* Protection des credentials administrateur et utilisateurs
* Compatibilité PAP pour FreeRADIUS
* Synchronisation avec pfSense

---

## 📈 Avantages de la plateforme

Sans cette solution :

* gestion manuelle des utilisateurs dans FreeRADIUS
* modifications complexes en base de données
* absence de statistiques avancées

Avec la plateforme :

* interface centralisée
* gestion intuitive des utilisateurs
* application simple des politiques
* analyse complète du trafic réseau
* supervision des connexions en temps réel

---

## 👨‍💻 Rôles et permissions

### 🧑 Administrateur

* contrôle total de la plateforme
* gestion des modérateurs
* gestion des groupes et politiques
* création et suppression globale

### 🧑‍💼 Modérateur

* gestion des groupes assignés
* création et gestion des utilisateurs
* création de liens d’invitation
* application de politiques sur ses groupes

---

## 🧠 Objectif du projet

Cette plateforme vise à moderniser et simplifier la gestion des environnements FreeRADIUS en entreprise, hotspot Wi-Fi, ou ISP, en combinant :

* administration réseau
* automatisation
* sécurité
* analyse de données

---

## 🛠️ Stack technique

* PHP (OOP)
* MySQL / MariaDB
* FreeRADIUS
* pfSense
* Apache
* Bash scripting
* Linux server

---

## 👤 Auteur

**Nour-eddine MAIZA**
Développeur web & Administrateur systèmes et réseaux

Passionné par la conception de solutions hybrides combinant développement web et infrastructures réseaux.

---

## 📌 Notes

Cette plateforme est en évolution continue et peut intégrer de nouvelles fonctionnalités.

```
