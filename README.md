# Shareyoursteps-explorer

Plugin WordPress per visualizzare una mappa interattiva dei percorsi condivisi tramite Leaflet.

## Installazione

1. Copia la cartella `wp-content/plugins/share-your-steps` all'interno della tua installazione di WordPress.
2. Dalla bacheca di WordPress attiva **Share Your Steps** oppure sposta la cartella in `wp-content/mu-plugins` per averlo sempre attivo.
3. Inserisci lo shortcode `[share_your_steps]` nella pagina o articolo in cui vuoi mostrare la mappa.

## Dipendenze

- [Leaflet](https://leafletjs.com/) viene caricato dal plugin tramite CDN.
- Il sito deve essere servito tramite **HTTPS** per evitare problemi di contenuti misti quando vengono caricate risorse esterne.

## Build

Per rigenerare i file JavaScript minificati eseguire:

```bash
npm install
npm run build
```

I file `*.min.js` verranno generati nella cartella `wp-content/plugins/share-your-steps/assets/js`.

## Opzioni

- Dalla pagina **Settings → Share Your Steps** è possibile configurare l'endpoint WebSocket. Il valore predefinito è `ws://localhost:8080`.
- Il reindirizzamento automatico verso HTTPS può essere disabilitato aggiungendo il seguente filtro in un plugin o nel tema attivo:

```php
add_filter( 'sys_force_https_enabled', '__return_false' );
```

## Proxy

Quando il sito è dietro un proxy o un bilanciatore di carico, il plugin utilizza le intestazioni
`HTTP_X_FORWARDED_FOR` e `HTTP_CLIENT_IP` per determinare l'indirizzo IP del client ai fini del
rate limiting. Assicurarsi che il proxy inoltri correttamente tali intestazioni.

