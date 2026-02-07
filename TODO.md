# TODO: Implement Three-Dot Menu Actions for Chat

## Backend Implementation
- [x] Add markAsRead method in ChatController.php
- [x] Add mute method in ChatController.php
- [x] Add delete method in ChatController.php
- [x] Add corresponding routes for markAsRead, mute, delete
- [x] Add is_muted and is_read fields to Chat entity
- [x] Run database migration

## Frontend Template Updates
- [x] Update index.html.twig dropdown links to buttons with data attributes (data-action, data-chat-id)

## JavaScript Implementation
- [x] Add event listeners in chat.js for three-dot menu actions
- [x] Implement AJAX POST requests for mark as read
- [x] Implement AJAX POST requests for mute (with toggle logic)
- [x] Implement AJAX POST requests for delete (with confirmation dialog)
- [x] Handle UI updates for mute (visual indicator)
- [x] Handle UI updates for delete (remove chat tab)

## Testing and Error Handling
- [ ] Test all actions: mark as read, mute, delete
- [ ] Add error handling for failed AJAX requests
- [ ] Add success/failure notifications (toasts)
