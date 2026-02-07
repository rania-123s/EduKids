// assets/js/chat.js

document.addEventListener('DOMContentLoaded', function () {
    console.log('Chat JS loaded');

    const chatContainer = document.querySelector('.chat-conversation-content');
    const messageInput = document.querySelector('textarea[data-autoresize]');
    const sendButton = document.getElementById('sendButton');
    const smileButton = document.getElementById('emojiBtn');

    // Get data from hidden div
    const chatData = document.getElementById('chat-data');
    let currentChatId = chatData ? parseInt(chatData.dataset.chatId) : 1;
    let currentUserId = chatData ? parseInt(chatData.dataset.userId) : 1;

    console.log('Elements found:', {
        chatContainer: !!chatContainer,
        messageInput: !!messageInput,
        sendButton: !!sendButton,
        smileButton: !!smileButton,
        currentChatId: currentChatId,
        currentUserId: currentUserId
    });

    /* =========================
       EMOJI PICKER
    ========================= */

    /* =========================
   EMOJI PICKER (FIXED)
========================= */

if (!smileButton || !messageInput) {
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
emojiPicker.setAttribute('tabindex', '0'); // Make focusable for keyboard

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

    const cols = 10;
    const rows = Math.ceil(emojis.length / cols);

    switch (e.key) {
        case 'ArrowUp':
            e.preventDefault();
            selectedIndex = Math.max(0, selectedIndex - cols);
            updateSelection();
            break;
        case 'ArrowDown':
            e.preventDefault();
            selectedIndex = Math.min(emojis.length - 1, selectedIndex + cols);
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

// Toggle picker
smileButton.addEventListener('click', function (e) {
    e.preventDefault();
    e.stopPropagation();

    const rect = smileButton.getBoundingClientRect();
    const pickerHeight = 200; // Approximate height
    if (rect.bottom + pickerHeight > window.innerHeight) {
        emojiPicker.style.top = (rect.top + window.scrollY - pickerHeight - 5) + 'px';
    } else {
        emojiPicker.style.top = (rect.bottom + window.scrollY + 5) + 'px';
    }
    emojiPicker.style.left = Math.max(0, rect.left) + 'px';

    emojiPicker.classList.toggle('d-none');
    if (!emojiPicker.classList.contains('d-none')) {
        emojiPicker.focus();
        selectedIndex = -1; // Reset selection
    }
});

// Close when clicking outside
document.addEventListener('click', function () {
    emojiPicker.classList.add('d-none');
});

// Prevent close when clicking picker
emojiPicker.addEventListener('click', function (e) {
    e.stopPropagation();
});


    /* =========================
       WEBSOCKET
    ========================= */

    const ws = new WebSocket('ws://localhost:8080');

    ws.onopen = function () {
        console.log('Connected to WebSocket server');
    };

    ws.onmessage = function (event) {
        const data = JSON.parse(event.data);
        displayMessage(data);
    };

    ws.onclose = function () {
        console.log('Disconnected from WebSocket server');
    };

    ws.onerror = function (error) {
        console.error('WebSocket error:', error);
    };

    /* =========================
       SEND MESSAGE
    ========================= */

    function sendMessage() {
        const content = messageInput.value.trim();
        if (content === '' || !currentChatId) return;

        const messageData = {
            chat_id: currentChatId,
            sender_id: 1, // replace later with real user
            content: content
        };

        ws.send(JSON.stringify(messageData));

        messageInput.value = '';
    }

    function displayMessage(data) {
        const isSender = data.sender_id == currentUserId;

        const messageElement = document.createElement('div');
        messageElement.className = 'd-flex mb-1';

        if (isSender) {
            // Right-aligned for sender
            messageElement.classList.add('justify-content-end', 'text-end');
            messageElement.innerHTML = `
                <div class="w-100">
                    <div class="d-flex flex-column align-items-end">
                        <div class="bg-primary text-white p-2 px-3 rounded-2">${data.content}</div>
                        <div class="small my-2">${new Date(data.date).toLocaleTimeString()}</div>
                    </div>
                </div>
            `;
        } else {
            // Left-aligned for receiver
            messageElement.innerHTML = `
                <div class="flex-shrink-0 avatar avatar-xs me-2">
                    <img class="avatar-img rounded-circle" src="assets/images/avatar/placeholder.jpg" alt="">
                </div>
                <div class="flex-grow-1">
                    <div class="w-100">
                        <div class="d-flex flex-column align-items-start">
                            <div class="bg-light text-secondary p-2 px-3 rounded-2">${data.content}</div>
                            <div class="small my-2">${new Date(data.date).toLocaleTimeString()}</div>
                        </div>
                    </div>
                </div>
            `;
        }

        chatContainer.appendChild(messageElement);
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    sendButton.addEventListener('click', sendMessage);

    messageInput.addEventListener('keypress', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendMessage();
        }
    });

    /* =========================
       LOAD MESSAGES
    ========================= */

    // Load default chat on page load
    if (window.location.pathname.startsWith('/chat/')) {
        const chatIdFromUrl = window.location.pathname.split('/chat/')[1];
        if (chatIdFromUrl) {
            loadChat(chatIdFromUrl);
        }
    }

    function loadChat(chatId) {
        // Update current chat ID
        currentChatId = chatId;

        // Update URL without reloading
        history.pushState(null, '', `/chat/${chatId}`);

        // Remove active class from all chat links
        document.querySelectorAll('.chat-link').forEach(link => link.classList.remove('active'));

        // Add active class to selected chat link
        const activeLink = document.querySelector(`.chat-link[data-chat-id="${chatId}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }

        // Fetch messages for the selected chat
        fetch(`/msg/chat/${chatId}/messages`)
            .then(res => res.json())
            .then(messages => {
                // Clear current messages
                chatContainer.innerHTML = '';

                // Display each message
                messages.forEach(displayMessage);
            })
            .catch(err => console.error('Error loading messages:', err));
    }

    // Make loadChat function global
    window.loadChat = loadChat;

    /* =========================
       CHAT ACTIONS
    ========================= */

    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('chat-action')) {
            e.preventDefault();

            const action = e.target.dataset.action;
            const cid = e.target.dataset.chatId;

            if (action === 'mark-read') markAsRead(cid);
            if (action === 'mute') muteChat(cid, e.target);
            if (action === 'delete') deleteChat(cid);
        }
    });

    function markAsRead(chatId) {
        fetch(`/chat/${chatId}/mark-read`, { method: 'POST' })
            .then(res => res.json())
            .then(data => console.log(data.success ? 'Chat marked as read' : 'Error'))
            .catch(err => console.error(err));
    }

    function muteChat(chatId, buttonElement) {
        fetch(`/chat/${chatId}/mute`, { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                buttonElement.innerHTML = data.is_muted
                    ? '<i class="bi bi-volume-mute me-2 fw-icon"></i>Unmute conversation'
                    : '<i class="bi bi-mic-mute me-2 fw-icon"></i>Mute conversation';
            })
            .catch(err => console.error(err));
    }

    function deleteChat(chatId) {
        if (!confirm('Are you sure you want to delete this chat?')) return;

        fetch(`/chat/${chatId}/delete`, { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                if (!data.success) return;

                document.querySelector(`#chat-${chatId}-tab`)?.closest('li')?.remove();
                document.querySelector(`#chat-${chatId}`)?.remove();
            })
            .catch(err => console.error(err));
    }
});
