# Shareyoursteps-explorer

Plugin WordPress per visualizzare una mappa interattiva dei percorsi condivisi tramite Leaflet.

## Installazione

1. Copia la cartella `wp-content/plugins/share-your-steps` all'interno della tua installazione di WordPress.
2. Dalla bacheca di WordPress attiva **Share Your Steps** oppure sposta la cartella in `wp-content/mu-plugins` per averlo sempre attivo.
3. Inserisci lo shortcode `[share_your_steps]` nella pagina o articolo in cui vuoi mostrare la mappa.

## Dipendenze

- [Leaflet](https://leafletjs.com/) viene caricato dal plugin tramite CDN.
- Il sito deve essere servito tramite **HTTPS** per evitare problemi di contenuti misti quando vengono caricate risorse esterne.
