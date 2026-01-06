document.addEventListener('DOMContentLoaded', function() {
    const dobInput = document.getElementById('date_of_birth');
    const ageInput = document.getElementById('age');

    function calculateAndDisplayAge() {
        if (!dobInput || !ageInput) return;
        const dobValue = dobInput.value;
        if (dobValue) {
            try {
                const birthDate = new Date(dobValue);
                const today = new Date();

                let diffTime = today.getTime() - birthDate.getTime();
                if (diffTime < 0) {
                    ageInput.value = '';
                    return;
                }

                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();

                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                ageInput.value = age >= 0 ? age : '';
            } catch (e) {
                ageInput.value = '';
            }
        } else {
            ageInput.value = '';
        }
    }

    if (dobInput && ageInput) {
        dobInput.addEventListener('change', calculateAndDisplayAge);
        dobInput.addEventListener('input', calculateAndDisplayAge);
        calculateAndDisplayAge();
    }

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
                    targetInput.classList.add('password-field-active');
                    eyeOpen.style.display = 'none';
                    eyeClosed.style.display = 'inline-block';
                } else {
                    targetInput.type = 'password';
                    targetInput.classList.remove('password-field-active');
                    eyeOpen.style.display = 'inline-block';
                    eyeClosed.style.display = 'none';
                }
            }
        });
    });
});