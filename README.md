# Transcription Accessible RGAA 4.1 (SRT / VTT) — Plugin WordPress

Extension WordPress libre (`GPL-2.0-or-later`), sans aucune dépendance ni appel externe, permettant de publier sous une vidéo (conseil municipal, colloque universitaire, AG d'association, podcast) une **transcription textuelle accessible conforme RGAA 4.1 (critères 4.1 et 4.3)** à partir d'un fichier `.SRT`, `.VTT` ou texte horodaté.

- **Bloc Gutenberg natif** (`lescribeaudio/rgaa-transcript`) avec import de fichier `.SRT`/`.VTT` intégré
- **Shortcode universel** `[rgaa_transcript title="..." media_url="..." timecodes="true" search="true"]`
- **HTML5 sémantique** (`<section>`, `<details>`, `<summary>`, `<time datetime="PT...S">`, `aria-live="polite"`)
- **Typographie française automatique** (espaces insécables fines avant `; ? !`, espace insécable avant `:` et guillemets `« … »`, sans casser les URLs ni les horaires `14:30`)
- **100 % local, zéro cookie, zéro lien caché en front-end** (conforme WordPress.org Plugin Guidelines)

## Soumission WordPress.org (`DR 96`, gratuit)

1. Archive prête à téléverser : `wp-rgaa-transcript-lescribeaudio-1.0.0.zip`
2. Ouvrir : https://wordpress.org/plugins/developers/add/
3. Connecter le compte WordPress.org `lescribeaudio`, déposer le `.zip` et valider.

## Documentation & Outils associés
- Guide accessibilité vidéo & RGAA 4.1 : https://lescribeaudio.com/sous-titrage-accessibilite-rgaa
- Boîte à outils gratuite `.SRT` / `.VTT` : https://lescribeaudio.com/outils
- Librairie MIT complémentaire `srt-vtt-cleaner-fr` : https://doi.org/10.5281/zenodo.24087394
