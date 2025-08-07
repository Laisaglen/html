document.addEventListener('DOMContentLoaded', () => {
  const menuBtn = document.getElementById('menu-btn');
  const navLinks = document.getElementById('nav-links');

  menuBtn.addEventListener('click', () => {
    const isActive = navLinks.classList.toggle('active');
    menuBtn.setAttribute('aria-expanded', isActive ? 'true' : 'false');
    // Manage tabindex for accessibility
    const links = navLinks.querySelectorAll('a');
    links.forEach((link) => {
      link.tabIndex = isActive ? 0 : -1;
    });
  });

  // Close nav menu if clicking outside
  document.addEventListener('click', (event) => {
    if (
      navLinks.classList.contains('active') &&
      !navLinks.contains(event.target) &&
      !menuBtn.contains(event.target)
    ) {
      navLinks.classList.remove('active');
      menuBtn.setAttribute('aria-expanded', 'false');
      const links = navLinks.querySelectorAll('a');
      links.forEach((link) => (link.tabIndex = -1));
    }
  });

  // Close nav menu on pressing Escape key
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && navLinks.classList.contains('active')) {
      navLinks.classList.remove('active');
      menuBtn.setAttribute('aria-expanded', 'false');
      const links = navLinks.querySelectorAll('a');
      links.forEach((link) => (link.tabIndex = -1));
      menuBtn.focus();
    }
  });

  // Initially hide nav links from keyboard navigation
  const links = navLinks.querySelectorAll('a');
  links.forEach((link) => (link.tabIndex = -1));
});
