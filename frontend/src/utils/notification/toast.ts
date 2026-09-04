import {useToast } from 'vue-toast-notification';
const toast = useToast({
    position: 'top-right',
    duration: 3000,
    dismissible: true,
    pauseOnHover: true,
    queue: false
});

export default toast;
