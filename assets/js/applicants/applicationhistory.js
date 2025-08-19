document.addEventListener('DOMContentLoaded', function() {
  // Initialize AOS
  AOS.init({
    duration: 800,
    once: true
  });

  // Check if we should open the modal
  if (window.location.search.includes('view_id')) {
    document.getElementById('applicationModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
  }

  // Initial mobile view check
  checkMobileView();
  
  // Close modal when clicking outside
  document.getElementById('applicationModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
      closeModal();
    }
  });
});

// Check on resize
window.addEventListener('resize', checkMobileView);

// Simple search filter
function filterTable(query) {
  const filter = query.toLowerCase();
  const table = document.getElementById("appTable");
  if (!table) return;
  
  const tr = table.getElementsByTagName("tr");
  for (let i = 1; i < tr.length; i++) { // skip header row
    let positionTd = tr[i].getElementsByTagName("td")[1]; // Position column
    let deptTd = tr[i].getElementsByTagName("td")[2]; // Department column
    let shouldDisplay = false;
    
    if (positionTd && deptTd) {
      const positionText = positionTd.textContent || positionTd.innerText;
      const deptText = deptTd.textContent || deptTd.innerText;
      shouldDisplay = positionText.toLowerCase().includes(filter) || 
                     deptText.toLowerCase().includes(filter);
    }
    
    tr[i].style.display = shouldDisplay ? "" : "none";
  }
}

// Modal functions
function showApplicationDetails(appId) {
  // Redirect to the same page with view_id parameter
  window.location.href = `applicationhistory.php?view_id=${appId}`;
}

function closeModal() {
  // Remove the view_id parameter and hide modal
  const url = new URL(window.location.href);
  url.searchParams.delete('view_id');
  window.history.replaceState({}, '', url.toString());
  
  const modal = document.getElementById('applicationModal');
  if (modal) {
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
  }
}

// Show mobile toggle button only on mobile
function checkMobileView() {
  const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
  if (window.innerWidth < 992) {
    if (mobileSidebarToggle) mobileSidebarToggle.style.display = 'flex';
  } else {
    if (mobileSidebarToggle) mobileSidebarToggle.style.display = 'none';
  }
}
 // Work Experience Modal Functions
    function showWorkExperienceModal() {
      document.getElementById('workExperienceModal').style.display = 'block';
      document.body.style.overflow = 'hidden';
    }

    function closeWorkExperienceModal() {
      document.getElementById('workExperienceModal').style.display = 'none';
      document.body.style.overflow = 'auto';
    }

    // Education Modal Functions
    function showEducationModal() {
      document.getElementById('educationModal').style.display = 'block';
      document.body.style.overflow = 'hidden';
    }

    function closeEducationModal() {
      document.getElementById('educationModal').style.display = 'none';
      document.body.style.overflow = 'auto';
    }

    // Close modals when clicking outside
    document.getElementById('workExperienceModal')?.addEventListener('click', function(e) {
      if (e.target === this) {
        closeWorkExperienceModal();
      }
    });

    document.getElementById('educationModal')?.addEventListener('click', function(e) {
      if (e.target === this) {
        closeEducationModal();
      }
    });

    // Filter table function
    function filterTable(searchTerm) {
      const table = document.getElementById('appTable');
      const rows = table.getElementsByTagName('tr');
      
      searchTerm = searchTerm.toLowerCase();
      
      for (let i = 1; i < rows.length; i++) { // Start from 1 to skip header
        const cells = rows[i].getElementsByTagName('td');
        let found = false;
        
        for (let j = 0; j < cells.length; j++) {
          const cellText = cells[j].textContent || cells[j].innerText;
          if (cellText.toLowerCase().indexOf(searchTerm) > -1) {
            found = true;
            break;
          }
        }
        
        rows[i].style.display = found ? '' : 'none';
      }
    }

    // Show application details
    function showApplicationDetails(appId) {
      window.location.href = `applicationhistory.php?view_id=${appId}`;
    }

    // Close main modal
    function closeModal() {
      window.location.href = 'applicationhistory.php';
    }