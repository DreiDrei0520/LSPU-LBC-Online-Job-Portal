// script.js
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form');
  
    form.addEventListener('submit', (e) => {
      const email = document.querySelector('#email').value.trim();
      const password = document.querySelector('#password').value.trim();
  
      if (!email || !password) {
        alert('Please fill in all fields');
        e.preventDefault();
      }
    });
  });
  