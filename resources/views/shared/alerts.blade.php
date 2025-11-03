@if (session('success') || session('warning') || session('error') || $errors->any())
<script>
document.addEventListener('DOMContentLoaded', function () {
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        customClass: {
            popup: 'colored-toast'
        },
        didOpen: toast => {
            toast.style.marginTop = '50px';
            toast.addEventListener('mouseenter', Swal.stopTimer)
            toast.addEventListener('mouseleave', Swal.resumeTimer)
        }
    });

    @if (session('success'))
        Toast.fire({
            icon: 'success',
            title: '{{ session('success') }}'
        });
    @endif

    @if (session('warning'))
        Toast.fire({
            icon: 'warning',
            title: '{{ session('warning') }}'
        });
    @endif

    @if (session('error'))
        Toast.fire({
            icon: 'error',
            title: '{{ session('error') }}'
        });
    @endif

    @if ($errors->any())
        Toast.fire({
            icon: 'error',
            title: 'Vui lòng kiểm tra lại thông tin.'
        });
    @endif
});
</script>

<style>
.colored-toast.swal2-popup.swal2-toast {
    background: #28a745 !important;
    color: #fff !important;
    font-weight: 500;
}
.colored-toast .swal2-title {
    color: #fff !important;
}
.colored-toast .swal2-timer-progress-bar {
    background: rgba(255, 255, 255, 0.8);
}
</style>
@endif
