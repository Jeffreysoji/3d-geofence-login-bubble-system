// main.js - minimal helpers for KKBank homepage

// Simple progressive enhancement - if a logo image isn't present, replace with text badge.
document.addEventListener('DOMContentLoaded', () => {
  const logo = document.querySelector('.logo-img');
  if (logo) {
    // placeholder: if file not found, hide image (browser will show broken icon)
    logo.addEventListener('error', () => {
      logo.style.display = 'none';
    });
  }
});
