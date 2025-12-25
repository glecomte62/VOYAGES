// Enregistrement du service worker pour PWA
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/service-worker.js')
      .then((registration) => {
        console.log('Service Worker enregistré avec succès:', registration.scope);
      })
      .catch((error) => {
        console.log('Échec de l\'enregistrement du Service Worker:', error);
      });
  });
}

// Détection de l'installation PWA
let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
  // Empêcher l'affichage automatique du prompt
  e.preventDefault();
  deferredPrompt = e;
  
  // Afficher un bouton d'installation personnalisé si vous le souhaitez
  showInstallButton();
});

function showInstallButton() {
  // Créer un bouton d'installation discret
  const installButton = document.createElement('button');
  installButton.id = 'pwa-install-btn';
  installButton.innerHTML = '📱 Installer l\'app';
  installButton.style.cssText = `
    position: fixed;
    bottom: 20px;
    right: 20px;
    padding: 12px 20px;
    background: linear-gradient(135deg, #0ea5e9, #14b8a6);
    color: white;
    border: none;
    border-radius: 25px;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    z-index: 9999;
    animation: slideIn 0.3s ease-out;
  `;
  
  installButton.addEventListener('click', async () => {
    if (deferredPrompt) {
      deferredPrompt.prompt();
      const { outcome } = await deferredPrompt.userChoice;
      console.log(`User response: ${outcome}`);
      deferredPrompt = null;
      installButton.remove();
    }
  });
  
  document.body.appendChild(installButton);
  
  // Masquer le bouton après 10 secondes
  setTimeout(() => {
    if (installButton.parentElement) {
      installButton.style.animation = 'slideOut 0.3s ease-out';
      setTimeout(() => installButton.remove(), 300);
    }
  }, 10000);
}

// Animation CSS pour le bouton
const style = document.createElement('style');
style.textContent = `
  @keyframes slideIn {
    from { transform: translateX(200px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
  }
  @keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(200px); opacity: 0; }
  }
`;
document.head.appendChild(style);

// Détection de l'installation réussie
window.addEventListener('appinstalled', () => {
  console.log('PWA installée avec succès');
  deferredPrompt = null;
});
