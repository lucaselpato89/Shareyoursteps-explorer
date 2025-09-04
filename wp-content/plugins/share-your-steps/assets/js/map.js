import Tracking from './tracking.js';
import Chat from './chat.js';
import LiveMode from './live-mode.js';
import Filters from './filters.js';

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.sys-map').forEach(el => {
    const lat = el.dataset.lat;
    const lng = el.dataset.lng;
    const zoom = el.dataset.zoom;
    const map = L.map(el.id).setView([lat, lng], zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution:
        '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
  });

  new Tracking().init();
  new Chat().init();
  new LiveMode().init();
  new Filters().init();
});
