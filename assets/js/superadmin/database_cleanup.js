        // Enable/disable submit button based on confirmation checkbox
        document.getElementById('confirm_cleanup').addEventListener('change', function() {
            document.querySelector('button[type="submit"]').disabled = !this.checked;
        });
        
        // Initialize with submit button disabled
        document.querySelector('button[type="submit"]').disabled = true;
        
        // Show confirmation when high impact options are selected
        document.querySelectorAll('.impact-high').forEach(el => {
            const checkbox = el.closest('.cleanup-option').querySelector('input[type="checkbox"]');
            checkbox.addEventListener('change', function() {
                if (this.checked) {
                    if (!confirm('Warning: This is a high impact operation that cannot be undone. Are you sure you want to proceed?')) {
                        this.checked = false;
                    }
                }
            });
        });
        
        // Show confirmation when system logs option is selected
        document.getElementById('clean_system_logs').addEventListener('change', function() {
            if (this.checked) {
                const forceDelete = document.getElementById('force_delete').checked;
                if (!forceDelete && !confirm('System logs cleanup will preserve the most recent 1000 logs or 10% of total logs (whichever is larger). Proceed?')) {
                    this.checked = false;
                }
            }
        });
        
        // Show warning when force delete is enabled
        document.getElementById('force_delete').addEventListener('change', function() {
            if (this.checked) {
                if (!confirm('WARNING: Force Delete will bypass all safety checks and delete ALL matching records without preservation. This is extremely destructive. Are you sure you want to enable this?')) {
                    this.checked = false;
                } else {
                    // If force delete is enabled, show additional warning for each high impact option
                    document.querySelectorAll('.impact-high').forEach(el => {
                        const checkbox = el.closest('.cleanup-option').querySelector('input[type="checkbox"]');
                        if (checkbox.checked) {
                            alert('Force Delete is enabled - this will completely remove ALL matching records for ' + el.closest('.cleanup-option').querySelector('label').textContent.trim());
                        }
                    });
                }
            }
        });