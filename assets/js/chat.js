// assets/js/chat.js

document.addEventListener('DOMContentLoaded', function() {
    console.log('Chat JS loaded');
    const chatContainer = document.querySelector('.chat-conversation-content');
    const messageInput = document.querySelector('textarea[data-autoresize]');
    const sendButton = document.querySelector('.btn-primary.ms-2');
    const emojiButton = document.querySelector('.card-footer button.btn-danger-soft');
    const chatId = 1; // Default chat ID since it's not in the HTML

    console.log('Elements found:', {
        chatContainer: !!chatContainer,
        messageInput: !!messageInput,
        sendButton: !!sendButton,
        emojiButton: !!emojiButton
    });

    // Connect to WebSocket server
    const ws = new WebSocket('ws://localhost:8080');

    ws.onopen = function(event) {
        console.log('Connected to WebSocket server');
    };

    ws.onmessage = function(event) {
        const data = JSON.parse(event.data);
        displayMessage(data);
    };

    ws.onclose = function(event) {
        console.log('Disconnected from WebSocket server');
    };

    ws.onerror = function(error) {
        console.error('WebSocket error:', error);
    };

    // Send message function
    function sendMessage() {
        const content = messageInput.value.trim();
        if (content === '') return;

        const messageData = {
            chat_id: chatId,
            sender_id: 1, // Replace with actual user ID
            content: content
        };

        // Send via WebSocket
        ws.send(JSON.stringify(messageData));

        // Also send via HTTP API as backup
        fetch(`/chat/${chatId}/send`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(messageData)
        })
        .then(response => response.json())
        .then(data => {
            console.log('Message sent via API:', data);
        })
        .catch(error => {
            console.error('Error sending message via API:', error);
        });

        messageInput.value = '';
    }

    // Display message in chat
    function displayMessage(data) {
        const messageElement = document.createElement('div');
        messageElement.className = 'message';
        messageElement.innerHTML = `<strong>User ${data.sender_id}:</strong> ${data.content}`;
        chatContainer.appendChild(messageElement);
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    // Emoji functionality
    let emojiPickerVisible = false;
    let emojiList = [];
    let emojisLoaded = false;
    let loadingEmojis = false;

    async function fetchEmojis() {
        if (emojisLoaded || loadingEmojis) return;
        loadingEmojis = true;
        try {
            // Use a predefined list of emojis to avoid fetch issues
            emojiList = ['😀', '😁', '😂', '🤣', '😃', '😄', '😅', '😆', '😉', '😊', '😋', '😎', '😍', '😘', '🥰', '😗', '😙', '🥲', '😚', '🙂', '🤗', '🤩', '🤔', '🤨', '😐', '😑', '😶', '🙄', '😏', '😣', '😥', '😮', '🤐', '😯', '😪', '😫', '🥱', '😴', '😌', '😛', '😜', '😝', '🤤', '😒', '😓', '😔', '😕', '🙃', '🤑', '😲', '☹️', '🙁', '😖', '😞', '😟', '😤', '😢', '😭', '😦', '😧', '😨', '😩', '🤯', '😬', '😰', '😱', '🥵', '🥶', '😳', '🤪', '😵', '🥴', '😠', '😡', '🤬', '😷', '🤒', '🤕', '🤢', '🤮', '🤧', '😇', '🥳', '🥺', '🤠', '🤡', '🤥', '🤫', '🤭', '🧐', '🤓', '😈', '👿', '👹', '👺', '💀', '👻', '👽', '🤖', '💩', '😺', '😸', '😹', '😻', '😼', '😽', '🙀', '😿', '😾'];
            emojisLoaded = true;
            console.log('Emojis loaded:', emojiList.length);
        } catch (error) {
            console.error('Error loading emojis:', error);
        } finally {
            loadingEmojis = false;
        }
    }



    function insertEmoji(emoji) {
        const start = messageInput.selectionStart;
        const end = messageInput.selectionEnd;
        const text = messageInput.value;
        const before = text.substring(0, start);
        const after = text.substring(end, text.length);
        messageInput.value = before + emoji + after;
        messageInput.selectionStart = messageInput.selectionEnd = start + emoji.length;
        messageInput.focus();
    }

    async function showEmojiPicker() {
        let picker = document.getElementById('emoji-picker');
        if (!picker) {
            picker = document.createElement('div');
            picker.id = 'emoji-picker';
            picker.style.cssText = `
                position: absolute;
                background: white;
                border: 1px solid #ccc;
                border-radius: 8px;
                padding: 10px;
                width: 320px;
                height: 200px;
                overflow-y: auto;
                z-index: 1000;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                display: none;
            `;

            const loadingDiv = document.createElement('div');
            loadingDiv.textContent = 'Loading emojis...';
            loadingDiv.style.cssText = 'text-align: center; padding: 20px;';
            picker.appendChild(loadingDiv);
            document.body.appendChild(picker);
            const rect = emojiButton.getBoundingClientRect();
            picker.style.left = rect.left + 'px';
            picker.style.top = (rect.top - 210) + 'px';
            picker.style.display = 'block';
            emojiPickerVisible = true;

            await fetchEmojis();

            picker.innerHTML = '';
            if (emojisLoaded) {
                const grid = document.createElement('div');
                grid.style.cssText = 'display: grid; grid-template-columns: repeat(8, 1fr); gap: 5px;';
                emojiList.forEach(emoji => {
                    const emojiBtn = document.createElement('button');
                    emojiBtn.textContent = emoji;
                    emojiBtn.style.cssText = `
                        background: none;
                        border: none;
                        font-size: 20px;
                        cursor: pointer;
                        padding: 5px;
                        border-radius: 4px;
                        width: 30px;
                        height: 30px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    `;
                    emojiBtn.addEventListener('click', () => {
                        insertEmoji(emoji);
                        hideEmojiPicker();
                    });
                    emojiBtn.addEventListener('mouseover', () => {
                        emojiBtn.style.background = '#f0f0f0';
                    });
                    emojiBtn.addEventListener('mouseout', () => {
                        emojiBtn.style.background = 'none';
                    });
                    grid.appendChild(emojiBtn);
                });
                picker.appendChild(grid);
            } else {
                const errorDiv = document.createElement('div');
                errorDiv.textContent = 'Failed to load emojis';
                errorDiv.style.cssText = 'text-align: center; padding: 20px;';
                picker.appendChild(errorDiv);
            }
        } else {
            picker.style.display = 'block';
            emojiPickerVisible = true;
        }
    }

    function hideEmojiPicker() {
        const picker = document.getElementById('emoji-picker');
        if (picker) {
            picker.style.display = 'none';
        }
        emojiPickerVisible = false;
    }

    // Event listeners
    sendButton.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendMessage();
        }
    });

    // Keyboard shortcut for emoji picker
    messageInput.addEventListener('keydown', function(e) {
        if (e.key === ':') {
            e.preventDefault(); // Prevent typing the :
            showEmojiPicker();
        }
    });

    if (emojiButton) {
        emojiButton.addEventListener('click', async function(e) {
            e.preventDefault();
            console.log('Emoji button clicked');
            if (emojiPickerVisible) {
                hideEmojiPicker();
            } else {
                await showEmojiPicker();
            }
        });
        console.log('Emoji button event listener added');
    } else {
        console.log('Emoji button not found');
    }

    // Hide emoji picker when clicking outside
    document.addEventListener('click', function(e) {
        if (!emojiButton.contains(e.target) && !document.getElementById('emoji-picker')?.contains(e.target)) {
            hideEmojiPicker();
        }
    });

    // Load existing messages
    fetch(`/chat/${chatId}/messages`)
        .then(response => response.json())
        .then(messages => {
            messages.forEach(displayMessage);
        })
        .catch(error => {
            console.error('Error loading messages:', error);
        });

    // Handle three-dot menu actions
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('chat-action')) {
            e.preventDefault();
            const action = e.target.getAttribute('data-action');
            const chatId = e.target.getAttribute('data-chat-id');

            if (action === 'mark-read') {
                markAsRead(chatId);
            } else if (action === 'mute') {
                muteChat(chatId, e.target);
            } else if (action === 'delete') {
                deleteChat(chatId);
            }
        }
    });

    // Mark chat as read
    function markAsRead(chatId) {
        fetch(`/chat/${chatId}/mark-read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Chat marked as read');
                // Update UI: remove unread indicators if any
                // For example, remove badge or change styling
            } else {
                console.error('Error marking chat as read');
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    // Mute or unmute chat
    function muteChat(chatId, buttonElement) {
        fetch(`/chat/${chatId}/mute`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(data.message);
                // Update button text or icon based on mute status
                const icon = buttonElement.querySelector('i');
                if (data.is_muted) {
                    icon.className = 'bi bi-volume-mute me-2 fw-icon';
                    buttonElement.innerHTML = '<i class="bi bi-volume-mute me-2 fw-icon"></i>Unmute conversation';
                } else {
                    icon.className = 'bi bi-mic-mute me-2 fw-icon';
                    buttonElement.innerHTML = '<i class="bi bi-mic-mute me-2 fw-icon"></i>Mute conversation';
                }
            } else {
                console.error('Error toggling mute');
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }

    // Delete chat
    function deleteChat(chatId) {
        if (confirm('Are you sure you want to delete this chat?')) {
            fetch(`/chat/${chatId}/delete`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Chat deleted');
                    // Remove chat from UI
                    const chatTab = document.querySelector(`#chat-${chatId}-tab`);
                    if (chatTab) {
                        chatTab.closest('li').remove();
                    }
                    const chatContent = document.querySelector(`#chat-${chatId}`);
                    if (chatContent) {
                        chatContent.remove();
                    }
                    // Optionally, switch to another chat or show a message
                } else {
                    console.error('Error deleting chat');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    }
});
