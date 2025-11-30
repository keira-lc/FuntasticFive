// Smooth scrolling for navigation links
document.querySelectorAll('nav a').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        target.scrollIntoView({ behavior: 'smooth' });
    });
});

// CTA button alert (placeholder for actual functionality)
document.querySelector('.cta-button').addEventListener('click', () => {
    alert('Redirecting to shop...');
});

// Newsletter subscribe alert
document.querySelector('.newsletter button').addEventListener('click', () => {
    const email = document.querySelector('.newsletter input').value;
    if (email) {
        alert(`Subscribed with ${email}!`);
    } else {
        alert('Please enter an email.');
    }
});