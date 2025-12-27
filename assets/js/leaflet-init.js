// assets/js/leaflet-init.js
// Initialise une carte Leaflet et place les étapes du guide
function initGuideMap(etapes) {
    if (!window.L || !etapes || etapes.length === 0) return;
    var map = L.map('guideMap').setView([46.5, 2.5], 6);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 18
    }).addTo(map);
    var bounds = [];
    etapes.forEach(function(etape, idx) {
        if (etape.lat && etape.lng) {
            var marker = L.marker([etape.lat, etape.lng]).addTo(map);
            marker.bindPopup('<b>' + etape.titre + '</b><br>' + etape.type_etape);
            bounds.push([etape.lat, etape.lng]);
        }
    });
    if (bounds.length > 0) map.fitBounds(bounds, {padding: [30,30]});
}
