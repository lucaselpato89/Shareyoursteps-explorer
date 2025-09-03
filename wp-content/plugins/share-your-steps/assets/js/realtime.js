const wsUrl = window?.shareYourSteps?.websocket_url;
let socket;

function startPolling() {
  setInterval(() => {
    fetch(window.shareYourSteps.api_url + 'routes')
      .then(r => r.json())
      .then(data => document.dispatchEvent(new CustomEvent('liveTick', { detail: data })))
      .catch(() => {});
    fetch(window.shareYourSteps.api_url + 'chat')
      .then(r => r.json())
      .then(data => document.dispatchEvent(new CustomEvent('chatTick', { detail: data })))
      .catch(() => {});
  }, 5000);
}

function connect() {
  if (wsUrl && 'WebSocket' in window) {
    socket = new WebSocket(wsUrl);
    socket.addEventListener('message', event => {
      try {
        const data = JSON.parse(event.data);
        document.dispatchEvent(new CustomEvent(data.type, { detail: data.payload }));
      } catch (e) {
        console.error(e);
      }
    });
    socket.addEventListener('close', startPolling);
    socket.addEventListener('error', startPolling);
  } else {
    startPolling();
  }
}

export function sendLiveTick(payload) {
  const message = JSON.stringify({ type: 'liveTick', payload });
  if (socket && socket.readyState === WebSocket.OPEN) {
    socket.send(message);
  } else {
    fetch(window.shareYourSteps.api_url + 'save-route', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).catch(() => {});
  }
}

export function sendChatTick(payload) {
  const message = JSON.stringify({ type: 'chatTick', payload });
  if (socket && socket.readyState === WebSocket.OPEN) {
    socket.send(message);
  } else {
    fetch(window.shareYourSteps.api_url + 'chat', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    }).catch(() => {});
  }
}

connect();
