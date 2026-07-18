# Guide de l'Utilisateur de Terrain (FIELD_USER_GUIDE)

Ce guide explique comment installer et utiliser l'App Opérationnelle Mobile MVP de **Lima Déménagement** directement sur le terrain (sur smartphone ou tablette).

---

## 1. Comment Installer le PWA (Progressive Web App)

L'application est conçue comme un PWA, ce qui signifie qu'elle s'installe directement depuis le navigateur Web, sans passer par l'App Store ou Google Play Store.

### Sur iOS (Safari / iPhone / iPad) :
1. Ouvrez **Safari** et accédez à l'URL de l'application : `https://limasolutions.ch/mobile/`.
2. Appuyez sur le bouton **Partager** (l'icône de carré avec une flèche vers le haut en bas de l'écran).
3. Faites défiler vers le bas et sélectionnez **Sur l'écran d'accueil**.
4. Validez en cliquant sur **Ajouter** en haut à droite.
5. L'icône **LIMA Operations** apparaît désormais sur votre écran d'accueil.

### Sur Android (Chrome / Samsung Internet) :
1. Ouvrez **Google Chrome** et accédez à l'URL de l'application : `https://limasolutions.ch/mobile/`.
2. Une bannière en bas de l'écran ou un indicateur dans la barre d'adresse peut vous proposer d'**Ajouter à l'écran d'accueil**.
3. Sinon, appuyez sur les **trois points verticaux** en haut à droite.
4. Sélectionnez **Installer l'application** ou **Ajouter à l'écran d'accueil**.
5. Confirmez l'installation.

---

## 2. Comment Faire Login

1. Lancez l'application depuis votre écran d'accueil.
2. Saisissez votre adresse e-mail professionnelle (ex: `chauffeur@limasolutions.ch`) et votre mot de passe.
3. Appuyez sur **Se connecter**.
4. Une fois connecté, votre jeton d'accès est stocké de manière sécurisée et vous n'aurez pas besoin de vous reconnecter, même en ouvrant l'application hors ligne.

---

## 3. Comment Ouvrir les Services Attribués

1. Une fois connecté, vous arrivez sur l'écran **Mes Services Attribués**.
2. Cette liste affiche tous les projets/déménagements qui vous sont actuellement affectés.
3. Appuyez sur le bouton **Ouvrir le service** sur la fiche d'un projet pour accéder aux détails, checklists, rapports photo, GPS et signature.

---

## 4. Comment Iniciar/Finaliser un Service (Enregistrement des heures)

1. Ouvrez le service correspondant.
2. Dans la section **Enregistrement des heures** :
   * Appuyez sur **Démarrer journée** lorsque vous commencez à charger ou à conduire. Le bouton passera en état désactivé et le suivi GPS se lancera si le tracking est actif.
   * Appuyez sur **Fin de service** à la fin de la journée ou du déchargement.
3. Les enregistrements de temps (Timesheets) sont mis en file d'attente et synchronisés automatiquement avec le serveur central.

---

## 5. Comment Travailler Offline (Hors Ligne)

L'application fonctionne en mode **Offline-First** :
* Si vous perdez la connexion réseau (zone de montagne, sous-sol, tunnel), l'application continue de fonctionner normalement.
* Toutes les actions effectuées (check-in/out, modifications de checklist, prises de photos, signatures) sont stockées localement dans la base de données interne de l'appareil (IndexedDB).
* Un badge indicateur en haut à droite affiche **Online** (Vert) ou **Offline** (Rouge) pour vous signaler l'état actuel de votre réseau.

---

## 6. Comment Capturer des Photos (Rapport de Sinistres)

Pour documenter l'état des biens et signaler tout dommage ou meuble déjà abîmé :
1. Dans la fiche du projet, faites défiler jusqu'à la section **Rapport de Photos (Sinistres)**.
2. Sélectionnez le type de photo :
   * *Avant le déménagement (Pre-move)* : pour les rayures ou défauts constatés à la prise en charge.
   * *Après le déménagement (Post-move)* : pour l'état à la livraison.
   * *Incident / Objet endommagé* : en cas de problème en cours de route.
3. Saisissez une brève description (ex: *"Rayure sur le buffet en bois"*).
4. Appuyez sur **Prendre une photo**. L'appareil photo de votre smartphone s'ouvre automatiquement.
5. Prenez la photo. L'application va automatiquement **redimensionner l'image** à 1280px de large et la **compresser en JPEG (Qualité: 0.7)** pour réduire l'impact sur votre forfait mobile de données (les photos passent de ~5 Mo à ~150 Ko).
6. La photo compressée est ensuite placée dans la file d'attente de synchronisation.

---

## 7. Comment Remplir la Checklist d'Inventaire

1. Dans la section **Checklist d'Inventaire**, vous verrez la liste de tous les biens à déménager.
2. Pour chaque élément, sélectionnez son statut réel :
   * *En attente* (Pending)
   * *Conforme* (Checked)
   * *Endommagé* (Damaged)
   * *Manquant* (Missing)
3. Appuyez sur **Enregistrer modifications** pour sauvegarder le rapport d'inventaire.

---

## 8. Comment Recueillir la Signature du Client (Décharge)

1. À la fin du service, faites signer le client pour confirmer la bonne livraison.
2. Saisissez le **Nom complet du signataire**.
3. Faites signer le client avec son doigt (ou un stylet) à l'intérieur du rectangle blanc de signature.
4. Si le tracé est incorrect, appuyez sur **Effacer** pour recommencer.
5. Appuyez sur **Confirmer Livraison** pour valider et envoyer la signature de décharge au serveur central.

---

## 9. Comment Sincroniser les Données

* La synchronisation s'effectue automatiquement en arrière-plan toutes les **30 secondes** dès que l'application détecte un accès Internet.
* Le compteur de la **File d'attente** (Sync Queue) en bas de l'écran indique le nombre d'éléments en attente d'envoi.
* **Erreurs et détails** : Vous pouvez cliquer sur le texte **File d'attente : X** pour dérouler la liste détaillée des éléments et voir les éventuels messages d'erreur de transmission.
* **Bouton « Forçar Reenvio » (Forcer la Synchronisation)** : Si des éléments ont échoué en raison d'une mauvaise connexion passée, ouvrez la file d'attente détaillée et appuyez sur **Forçar Reenvio** pour relancer manuellement l'envoi immédiat de tous les éléments bloqués.

---

## 10. Comment Limper les Données du Dispositif

Si vous partagez le smartphone avec un autre chauffeur ou si vous souhaitez nettoyer le stockage local de l'appareil en fin de mission :
* **Déconnexion (Logout)** : Appuyez sur **Déconnexion** en haut de l'écran pour vous déconnecter. Cela efface votre jeton d'authentification et nettoie le cache local des projets et des checklists pour des raisons de sécurité.
* **Bouton « Limpiar Dados deste Dispositivo » (Nettoyer l'appareil)** : Situé en haut à droite (bouton rouge à côté de Déconnexion). Ce bouton efface complètement toutes les informations locales : le jeton de connexion (`localStorage`), les projets, les checklists et la file d'attente de synchronisation IndexedDB.
  > [!CAUTION]
  > N'appuyez sur ce bouton que si la file d'attente (Sync Queue) est à **0** pour éviter de perdre définitivement des photos ou signatures non synchronisées !
