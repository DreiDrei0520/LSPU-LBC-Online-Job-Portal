document.addEventListener('DOMContentLoaded', () => {
    const buttons = document.querySelectorAll('.position-card button');
  
    buttons.forEach(button => {
      button.addEventListener('click', () => {
        alert('This would navigate to the job listing page.');
      });
    });
  
    const uploadForm = document.querySelector('.upload-form');
    uploadForm.addEventListener('submit', e => {
      e.preventDefault();
      alert('Documents uploaded successfully!');
    });
  });
  document.addEventListener("DOMContentLoaded", () => {
    const profileIcon = document.getElementById("profileIcon");
    const profileDropdown = document.getElementById("profileDropdown");
  
    profileIcon.addEventListener("click", () => {
      profileDropdown.style.display = profileDropdown.style.display === "block" ? "none" : "block";
    });
  
    // Close dropdown if clicking outside
    window.addEventListener("click", (e) => {
      if (!profileIcon.contains(e.target) && !profileDropdown.contains(e.target)) {
        profileDropdown.style.display = "none";
      }
    });
  });
    