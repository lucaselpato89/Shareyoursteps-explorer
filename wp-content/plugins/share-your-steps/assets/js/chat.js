import { sendChatTick } from './realtime.js';

export default class Chat {
  init() {
    console.log('Chat initialized');
    document.addEventListener('chatTick', e => console.log(e.detail));
    const message = 'Hello';
    sendChatTick({ message });

    fetch(window.shareYourSteps.ajax_url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'sys_handle_message',
        message,
        nonce: window.shareYourSteps.chat_nonce
      })
    })
      .then(r => r.json())
      .then(data => console.log(data))
      .catch(() => {});
  }
}
