=== Le Scribe Audio RGAA Transcript ===
Contributors: lescribeaudio
Tags: rgaa, accessibility, transcript, subtitles, srt
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Bloc Gutenberg et shortcode [rgaa_transcript] pour afficher une transcription accessible conforme RGAA 4.1 (critères 4.1 et 4.3) depuis un fichier .SRT ou .VTT.

== Description ==

**Le Scribe Audio RGAA Transcript** permet aux collectivités territoriales (conseils municipaux, EPCI), universités, associations et créateurs de podcasts de publier facilement une transcription textuelle accessible sous leurs vidéos ou enregistrements audio, conformément à l'article 47 de la loi du 11 février 2005 et au référentiel **RGAA 4.1** (critères 4.1 et 4.3).

### Fonctionnalités principales

* **Bloc Gutenberg natif + Shortcode `[rgaa_transcript]`** : intégration en 1 clic dans tout thème WordPress (classique ou Full Site Editing).
* **Import direct `.SRT` / `.VTT` / texte horodaté** : téléversez un fichier de sous-titres dans l'éditeur ; les balises temporelles et étiquettes de locuteurs (`<v Nom>` ou `Nom :`) sont nettoyées et fusionnées automatiquement.
* **HTML5 sémantique et accessible** : accordéon `<details>` / `<summary>`, codes temporels `<time datetime="PT...S">`, contraste élevé, focus clavier visible et zone `aria-live="polite"`.
* **Recherche instantanée intégrée** : permet aux citoyens, chercheurs ou lecteurs de filtrer immédiatement les prises de parole par mot-clé ou intervenant (fonctionne progressivement même sans JavaScript).
* **Typographie française automatique** : espaces fines insécables (`\u202F`) avant `;`, `?`, `!` et espace insécable (`\u00A0`) avant `:` et dans les guillemets `« … »`, tout en préservant les URLs et horaires (`14:30`).
* **100 % local et sans pistage** : aucun appel serveur externe, aucun cookie, aucune publicité ni lien caché injecté sur les pages publiques.

### Exemple avec shortcode

`[rgaa_transcript title="Conseil municipal — Séance du 16 novembre 2026" timecodes="true" search="true"]`
`1`
`00:00:04,100 --> 00:00:11,400`
`M. Martin (Maire) : Mesdames et Messieurs, le quorum étant atteint, la séance est ouverte.`
`[/rgaa_transcript]`

### Documentation et outils complémentaires

Maintenu en logiciel libre (GPLv2+) par [Le Scribe Audio](https://lescribeaudio.com/sous-titrage-accessibilite-rgaa). Un générateur autonome sans installation et des outils open-source `.SRT` / `.VTT` sont également disponibles sur [lescribeaudio.com/outils](https://lescribeaudio.com/outils).

== Installation ==

1. Téléversez l'archive `.zip` du plugin depuis **Extensions > Ajouter > Téléverser une extension**, ou copiez le dossier `wp-rgaa-transcript-lescribeaudio` dans `/wp-content/plugins/`.
2. Activez l'extension dans le menu **Extensions** de WordPress.
3. Dans l'éditeur de blocs Gutenberg, ajoutez le bloc **Transcription Accessible RGAA 4.1 (SRT / VTT)**, importez votre fichier `.SRT` ou `.VTT`, puis publiez.

== Frequently Asked Questions ==

= Le plugin envoie-t-il des données vers un serveur externe ? =

Non. Le parsing `.SRT`/`.VTT`, la normalisation typographique et le rendu HTML sont exécutés à 100 % sur votre propre serveur WordPress et dans le navigateur de vos visiteurs.

= Le plugin injecte-t-il un lien ou un badge promotionnel sur mon site ? =

Non. Aucun lien promotionnel ni crédit caché n'est ajouté dans le rendu public, en stricte conformité avec les directives du répertoire WordPress.org.

= Puis-je l'utiliser sans Gutenberg (éditeur classique ou Elementor / Divi) ? =

Oui, utilisez simplement le shortcode `[rgaa_transcript title="..."]...[/rgaa_transcript]`.

== Changelog ==

= 1.0.0 =
* Version initiale : bloc Gutenberg natif, shortcode `[rgaa_transcript]`, import direct `.SRT` / `.VTT`, fusion des tours de parole, typographie française automatique et barre de recherche accessible `aria-live`.

== Upgrade Notice ==

= 1.0.0 =
Version initiale stable compatible WordPress 6.2+ et PHP 7.4+.
