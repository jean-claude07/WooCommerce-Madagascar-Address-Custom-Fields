# WooCommerce Madagascar Address & Custom Fields

Personnalisez l'expérience de checkout WooCommerce pour Madagascar : régions, districts dynamiques, code postal, et devise Ariary (MGA).

---

## 🚀 Fonctionnalités

- **Pays limité à Madagascar**  
  Seul Madagascar est disponible comme pays lors du checkout.

- **Champs d’adresse adaptés**  
  - Sélecteur de **région** (22 régions malgaches)
  - Sélecteur de **district** dynamique selon la région choisie
  - **Code postal** (validation stricte à 3 chiffres)
  - Champs adaptés pour facturation et livraison

- **Devise Ariary (MGA)**  
  - Devise WooCommerce forcée à MGA
  - Symbole Ariary ajouté

- **Traduction prête**  
  Textes prêts à être traduits (`Text Domain: wc-mg-custom-fields`)

- **Affichage admin**  
  Les champs personnalisés sont visibles dans l’admin WooCommerce (commandes).

---

## 📦 Installation

1. **Téléchargez** ou clonez ce dépôt dans le dossier `wp-content/plugins/woocommerceMada/`.
2. **Activez** le plugin depuis l’admin WordPress.
3. Rendez-vous sur la page de paiement WooCommerce pour voir les champs personnalisés.

---

## 🛠️ Utilisation

- Lors du checkout, l’utilisateur doit :
  1. Sélectionner une **région** (liste déroulante)
  2. Sélectionner un **district** (liste dépendante de la région)
  3. Saisir un **code postal** (3 chiffres)
- Les mêmes champs sont disponibles pour la facturation et la livraison (si activée).
- Les champs sont **obligatoires** et validés côté serveur.

---

## 📝 Personnalisation

- **Régions/Districts** :  
  Modifiez la méthode [`get_madagascar_locations`](woocommerce-madagascar-fields.php) pour adapter la liste.
- **Traductions** :  
  Ajoutez vos fichiers `.mo/.po` dans le dossier `/languages`.

---

## 🧩 Fichiers principaux

- [`woocommerce-madagascar-fields.php`](woocommerce-madagascar-fields.php)  
  Logique principale du plugin, hooks WooCommerce, gestion des champs, validation, devise, etc.
- [`checkout.js`](checkout.js)  
  Gère l’interdépendance Région/District côté client (JavaScript).

---

## 💡 Astuces

- **Compatibilité** :  
  Le champ "Région" est synchronisé avec le champ "state" WooCommerce pour une compatibilité maximale.
- **AJAX Ready** :  
  Les champs sont réinitialisés correctement lors des mises à jour AJAX du checkout WooCommerce.

---

## 🖥️ Capture d’écran

![custom _field_woocomerce](https://github.com/user-attachments/assets/ad9ef7e4-46c6-4aff-adcc-817f63483bcb)

---

## 📚 Développement

- Basé sur les hooks et filtres WooCommerce.
- Code commenté et structuré pour faciliter l’évolution.

---

## 📝 Licence

Ce plugin est distribué sous licence GPLv2 ou ultérieure.

---

## 🙏 Remerciements

Développé par Jean Claude RAKOTONARIVO  
[e-varotra.mg](https://e-varotra.mg)

---

## ❓ Support

Pour toute question ou suggestion, ouvrez une issue ou contactez l’auteur via le site officiel.
