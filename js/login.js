document.addEventListener('DOMContentLoaded', function() {
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetInputId = this.dataset.target;
            const targetInput = document.getElementById(targetInputId);
            const eyeOpen = this.querySelector('.eye-open');
            const eyeClosed = this.querySelector('.eye-closed');

            if (targetInput && eyeOpen && eyeClosed) {
                if (targetInput.type === 'password') {
                    targetInput.type = 'text';
                    eyeOpen.style.display = 'none';
                    eyeClosed.style.display = 'inline-block';
                } else {
                    targetInput.type = 'password';
                    eyeOpen.style.display = 'inline-block';
                    eyeClosed.style.display = 'none';
                }
            }
        });
    });
});