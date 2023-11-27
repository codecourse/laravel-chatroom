import Bus from './bus'

Echo.join('chat')
    .here((users) => {
        Bus.$emit('users.here', users)
    })
    .joining((user) => {
        Bus.$emit('users.joined', user)
    })
    .leaving((user) => {
        Bus.$emit('users.left', user)
    })
    .listen('Chat.MessageCreated', (e) => {
        Bus.$emit('messages.added', e.message)
    });