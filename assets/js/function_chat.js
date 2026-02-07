

document.addEventListener('DOMContentLoaded', function() {
  // Handle dropdown toggle for all chat conversation dropdowns
  document.querySelectorAll('[id^="chatcoversationDropdown"]').forEach(function(btn) {
    btn.addEventListener('click', function(event) {
      event.preventDefault();
      var menu = document.querySelector('[aria-labelledby="' + this.id + '"]');
      if (menu) {
        menu.classList.toggle('show');
        this.setAttribute('aria-expanded', menu.classList.contains('show'));
      }
    });
  });

  // Close dropdown when clicking outside
  document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown')) {
      document.querySelectorAll('.dropdown-menu.show').forEach(function(menu) {
        menu.classList.remove('show');
        var btn = document.querySelector('[aria-labelledby="' + menu.getAttribute('aria-labelledby') + '"]');
        if (btn) {
          btn.setAttribute('aria-expanded', 'false');
        }
      });
    }
  });

  // Function to load chat messages
  function loadChat(element, chatId) {
    // Prevent default link behavior
    event.preventDefault();

    // Update URL without reload
    history.pushState(null, '', '/chat/' + chatId);

    // Remove active class from all chat links
    document.querySelectorAll('.chat-link').forEach(link => link.classList.remove('active'));

    // Add active class to clicked link
    element.classList.add('active');

    // Fetch messages via AJAX
    fetch('/chat/' + chatId + '/messages')
      .then(response => response.json())
      .then(messages => renderMessages(messages))
      .catch(error => console.error('Error loading messages:', error));
  }

  // Function to render messages in the DOM
  function renderMessages(messages) {
    const container = document.getElementById('chat-messages');
    container.innerHTML = ''; // Clear existing messages
    messages.forEach(message => {
      const messageDiv = document.createElement('div');
      messageDiv.classList.add('message');
      messageDiv.innerHTML = `
        <strong>Sender ${message.sender_id}:</strong> ${message.content}
        <small class="text-muted">(${message.date})</small>
      `;
      container.appendChild(messageDiv);
    });
  }

  // Check URL on page load and load chat if present
  const pathParts = window.location.pathname.split('/');
  const chatIndex = pathParts.indexOf('chat');
  if (chatIndex !== -1 && pathParts[chatIndex + 1]) {
    const chatId = pathParts[chatIndex + 1];
    loadChat(null, chatId); // No element to highlight on load
  }

  // Handle file upload
  const fileInput = document.getElementById('file-upload');
  if (fileInput) {
    fileInput.addEventListener('change', function(event) {
      const file = event.target.files[0];
      if (file) {
        // Here you can add logic to handle the file, e.g., upload via AJAX
        console.log('File selected:', file.name);
        // For example, you can send the file to the server
        const formData = new FormData();
        formData.append('file', file);
        // fetch('/upload-endpoint', { method: 'POST', body: formData })
        //   .then(response => response.json())
        //   .then(data => console.log('Upload success:', data))
        //   .catch(error => console.error('Upload error:', error));
      }
    });
  }

  // Emoji Picker Functionality
  const emojiBtn = document.getElementById('emojiBtn');
  const messageInput = document.querySelector('textarea[data-autoresize]') || document.querySelector('textarea');

  if (!emojiBtn || !messageInput) {
    console.error('Emoji button or message input not found');
    return;
  }

  const emojis = [
    '😀', '😂', '😍', '😎', '😭', '👍', '❤️', '🔥', '🎉', '😡',
    '🤔', '🙏', '😴', '🥳', '😇', '😉', '😘', '😜', '😝', '🤗',
    '🤩', '😏', '😒', '😞', '😔', '😪', '🤤', '😴', '😵', '🤯',
    '🤠', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '😈', '👿',
    '💀', '☠️', '💩', '🤡', '👹', '👺', '👻', '👽', '👾', '🤖',
    '😺', '😸', '😹', '😻', '😼', '😽', '🙀', '😿', '😾'
  ];

  const emojiPicker = document.createElement('div');
  emojiPicker.classList.add('emoji-picker', 'd-none');
  emojiPicker.setAttribute('tabindex', '0');

  Object.assign(emojiPicker.style, {
    position: 'absolute',
    background: '#fff',
    border: '1px solid #ddd',
    borderRadius: '10px',
    padding: '8px',
    boxShadow: '0 5px 15px rgba(0,0,0,0.15)',
    zIndex: '1000',
    display: 'grid',
    gridTemplateColumns: 'repeat(10, 1fr)',
    gap: '5px',
    maxWidth: '300px',
    maxHeight: '200px',
    overflowY: 'auto'
  });

  let selectedIndex = -1;

  const updateSelection = () => {
    emojis.forEach((_, index) => {
      const span = emojiPicker.children[index];
      if (index === selectedIndex) {
        span.style.background = '#007bff';
        span.style.color = '#fff';
      } else {
        span.style.background = '';
        span.style.color = '';
      }
    });
  };

  emojis.forEach((emoji, index) => {
    const span = document.createElement('span');
    span.textContent = emoji;
    span.style.fontSize = '22px';
    span.style.cursor = 'pointer';
    span.style.padding = '5px';
    span.style.borderRadius = '5px';
    span.style.transition = 'background 0.2s, color 0.2s';
    span.dataset.index = index;

    span.addEventListener('click', () => {
      messageInput.value += emoji;
      emojiPicker.classList.add('d-none');
      messageInput.focus();
    });

    span.addEventListener('mouseenter', () => {
      if (selectedIndex !== index) {
        span.style.background = '#f0f0f0';
      }
    });

    span.addEventListener('mouseleave', () => {
      if (selectedIndex !== index) {
        span.style.background = '';
      }
    });

    emojiPicker.appendChild(span);
  });

  emojiPicker.addEventListener('keydown', (e) => {
    if (emojiPicker.classList.contains('d-none')) return;

    switch (e.key) {
      case 'ArrowUp':
        e.preventDefault();
        selectedIndex = Math.max(0, selectedIndex - 10);
        updateSelection();
        break;
      case 'ArrowDown':
        e.preventDefault();
        selectedIndex = Math.min(emojis.length - 1, selectedIndex + 10);
        updateSelection();
        break;
      case 'ArrowLeft':
        e.preventDefault();
        selectedIndex = Math.max(0, selectedIndex - 1);
        updateSelection();
        break;
      case 'ArrowRight':
        e.preventDefault();
        selectedIndex = Math.min(emojis.length - 1, selectedIndex + 1);
        updateSelection();
        break;
      case 'Enter':
        e.preventDefault();
        if (selectedIndex >= 0) {
          messageInput.value += emojis[selectedIndex];
          emojiPicker.classList.add('d-none');
          messageInput.focus();
        }
        break;
      case 'Escape':
        e.preventDefault();
        emojiPicker.classList.add('d-none');
        messageInput.focus();
        break;
    }
  });

  document.body.appendChild(emojiPicker);

  emojiBtn.addEventListener('click', function (e) {
    e.preventDefault();
    e.stopPropagation();

    const rect = emojiBtn.getBoundingClientRect();
    const pickerHeight = 200;
    if (rect.bottom + pickerHeight > window.innerHeight) {
      emojiPicker.style.top = (rect.top + window.scrollY - pickerHeight - 5) + 'px';
    } else {
      emojiPicker.style.top = (rect.bottom + window.scrollY + 5) + 'px';
    }
    emojiPicker.style.left = Math.max(0, rect.left) + 'px';

    emojiPicker.classList.toggle('d-none');
    if (!emojiPicker.classList.contains('d-none')) {
      emojiPicker.focus();
      selectedIndex = -1;
    }
  });

  document.addEventListener('click', function () {
    emojiPicker.classList.add('d-none');
  });

  emojiPicker.addEventListener('click', function (e) {
    e.stopPropagation();
  });
});
