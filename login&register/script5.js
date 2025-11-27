const authModal = document.querySelector('.auth-modal');
const loginLink = document.querySelector('.login-link');
const registerLink = document.querySelector('.register-link');
const loginBtnModal = document.querySelector('.login-btn-modal');
const closeBtnModal = document.querySelector('.close-btn-modal');
const profileBox = document.querySelector('.profile-box');
const avatarCircle = document.querySelector('.avatar-circle');
const logoutLink = document.querySelector('.logout-link');
const alertBox = document.querySelector('.alert-box');

/* Switch to register */
if (registerLink)
registerLink.addEventListener('click', () => {
    authModal.classList.add('slide');
});

/* Switch to login */
if (loginLink)
loginLink.addEventListener('click', () => {
    authModal.classList.remove('slide');
});

/* Show login modal */
if (loginBtnModal)
loginBtnModal.addEventListener('click', () => {
    authModal.classList.add('show');
});

/* Close modal */
closeBtnModal.addEventListener('click', () => {
    authModal.classList.remove('show', 'slide');
});

if (avatarCircle) {
    avatarCircle.addEventListener('click', () => {
        profileBox.classList.toggle('show');
    });
}

/* Logout */
if (logoutLink)
logoutLink.addEventListener('click', () => {
    profileBox.style.display = 'none';
    loginBtnModal.style.display = 'inline-block';
    profileBox.classList.remove('show');
});

/* Close dropdown when clicking outside */
document.addEventListener('click', (e) => {
    if (!profileBox.contains(e.target) && e.target !== avatarCircle) {
        profileBox.classList.remove('show');
    }
});

/* Alert animation */
if (alertBox) {
    setTimeout(() => {
        alertBox.classList.add('show');
    }, 50);

    setTimeout(() => {
        alertBox.classList.remove('show');

        setTimeout(() => {
            alertBox.remove();
        }, 1000);

    }, 6000);
}
