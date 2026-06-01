
import Swal from 'sweetalert2'

export const showConfirmDialog = async (options: {
  title: string
  text?: string
  icon?: 'warning' | 'error' | 'info' | 'question'
  confirmText?: string
  cancelText?: string
}) => {
  const result = await Swal.fire({
    title: options.title,
    text: options.text,
    icon: options.icon || 'warning',
    showCancelButton: true,
    confirmButtonColor: '#00685F',
    cancelButtonColor: '#d33',
    confirmButtonText: options.confirmText || 'Yes',
    cancelButtonText: options.cancelText || 'Cancel'
  })
  return result.isConfirmed
}

export const showSuccessToast = async (message: string) => {
  await Swal.fire({
    title: 'Success!',
    text: message,
    icon: 'success',
    timer: 1500,
    showConfirmButton: false
  })
}

export const showErrorAlert = async (message: string) => {
  await Swal.fire({
    title: 'Error!',
    text: message,
    icon: 'error',
    confirmButtonColor: '#00685F'
  })
}