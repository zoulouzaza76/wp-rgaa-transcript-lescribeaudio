(function (wp) {
  'use strict';

  var registerBlockType = wp.blocks.registerBlockType;
  var el = wp.element.createElement;
  var Fragment = wp.element.Fragment;
  var useBlockProps = wp.blockEditor.useBlockProps;
  var InspectorControls = wp.blockEditor.InspectorControls;
  var PanelBody = wp.components.PanelBody;
  var TextControl = wp.components.TextControl;
  var TextareaControl = wp.components.TextareaControl;
  var ToggleControl = wp.components.ToggleControl;
  var __ = wp.i18n.__;

  function countPreviewTurns(raw) {
    if (!raw || !raw.trim()) return 0;
    var lines = raw.replace(/\r\n/g, '\n').split('\n');
    var count = 0;
    for (var i = 0; i < lines.length; i++) {
      var l = lines[i].trim();
      if (!l) continue;
      if (/^(WEBVTT|NOTE\b|STYLE\b|Kind:|Language:)/i.test(l)) continue;
      if (/^\d+$/.test(l)) continue;
      if (/\d{1,2}:\d{2}(?::\d{2})?(?:[.,]\d{1,3})?\s*-->/.test(l)) continue;
      count++;
    }
    return count;
  }

  registerBlockType('lescribeaudio/rgaa-transcript', {
    edit: function (props) {
      var attrs = props.attributes;
      var setAttributes = props.setAttributes;
      var blockProps = useBlockProps({ className: 'rgaa-lsa-editor-box' });

      function handleFileChange(event) {
        var file = event.target && event.target.files && event.target.files[0];
        if (!file) return;
        var reader = new FileReader();
        reader.onload = function () {
          if (typeof reader.result === 'string') {
            var next = { rawTranscript: reader.result };
            if (!attrs.title || attrs.title === 'Transcription textuelle intégrale') {
              var clean = file.name.replace(/\.(srt|vtt|txt)$/i, '').replace(/[_-]+/g, ' ').trim();
              if (clean) next.title = 'Transcription — ' + clean;
            }
            setAttributes(next);
          }
        };
        reader.readAsText(file, 'UTF-8');
      }

      var lineCount = countPreviewTurns(attrs.rawTranscript);

      return el(
        Fragment,
        null,
        el(
          InspectorControls,
          null,
          el(
            PanelBody,
            { title: __('Options d’accessibilité RGAA 4.1', 'le-scribe-audio-rgaa-transcript'), initialOpen: true },
            el(ToggleControl, {
              label: __('Déplier la transcription par défaut', 'le-scribe-audio-rgaa-transcript'),
              checked: !!attrs.defaultOpen,
              onChange: function (v) { setAttributes({ defaultOpen: !!v }); },
            }),
            el(ToggleControl, {
              label: __('Afficher les codes temporels (HH:MM:SS)', 'le-scribe-audio-rgaa-transcript'),
              checked: attrs.includeTimecodes !== false,
              onChange: function (v) { setAttributes({ includeTimecodes: !!v }); },
            }),
            el(ToggleControl, {
              label: __('Activer la barre de recherche intégrée', 'le-scribe-audio-rgaa-transcript'),
              checked: attrs.enableSearch !== false,
              onChange: function (v) { setAttributes({ enableSearch: !!v }); },
            }),
            el(ToggleControl, {
              label: __('Typographie française automatique (espaces insécables)', 'le-scribe-audio-rgaa-transcript'),
              checked: attrs.frenchTypography !== false,
              onChange: function (v) { setAttributes({ frenchTypography: !!v }); },
            }),
            el(TextControl, {
              label: __('URL de la vidéo ou de l’audio source (optionnel)', 'le-scribe-audio-rgaa-transcript'),
              value: attrs.mediaUrl || '',
              placeholder: 'https://...',
              onChange: function (v) { setAttributes({ mediaUrl: v }); },
            })
          )
        ),
        el(
          'div',
          blockProps,
          el(
            'div',
            { className: 'rgaa-lsa-editor-head' },
            el('span', { className: 'rgaa-lsa-badge' }, 'RGAA 4.1'),
            el('strong', null, __('Transcription Accessible (.SRT / .VTT)', 'le-scribe-audio-rgaa-transcript')),
            el(
              'span',
              { className: 'rgaa-lsa-editor-count' },
              lineCount > 0 ? lineCount + ' ' + __('lignes détectées', 'le-scribe-audio-rgaa-transcript') : __('Vide', 'le-scribe-audio-rgaa-transcript')
            )
          ),
          el(TextControl, {
            label: __('Titre de l’accordéon accessible', 'le-scribe-audio-rgaa-transcript'),
            value: attrs.title || '',
            onChange: function (v) { setAttributes({ title: v }); },
          }),
          el(
            'div',
            { className: 'rgaa-lsa-editor-upload' },
            el('label', { className: 'rgaa-lsa-upload-label' },
              __('Importer directement un fichier .SRT, .VTT ou .TXT : ', 'le-scribe-audio-rgaa-transcript'),
              el('input', {
                type: 'file',
                accept: '.srt,.vtt,.txt',
                onChange: handleFileChange,
              })
            )
          ),
          el(TextareaControl, {
            label: __('Contenu .SRT / .VTT ou texte horodaté', 'le-scribe-audio-rgaa-transcript'),
            help: __('Les balises temporelles et étiquettes d’intervenants (<v Nom> ou Nom :) sont automatiquement fusionnées et nettoyées en HTML sémantique.', 'le-scribe-audio-rgaa-transcript'),
            rows: 8,
            value: attrs.rawTranscript || '',
            onChange: function (v) { setAttributes({ rawTranscript: v }); },
          })
        )
      );
    },
    save: function () {
      return null; // Dynamic server-side render in PHP.
    },
  });
})(window.wp);
