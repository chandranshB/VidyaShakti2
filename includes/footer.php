        </div> <!-- End of content-wrapper -->
    </main>
</div>

<script>
    // Initialize Feather Icons
    feather.replace();

    // Theme Toggle Logic
    const themeToggle = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const themeText = document.getElementById('themeText');

    function updateThemeUI(theme) {
        if (theme === 'dark') {
            themeIcon.setAttribute('data-feather', 'sun');
            if(themeText) themeText.textContent = 'Light Mode';
        } else {
            themeIcon.setAttribute('data-feather', 'moon');
            if(themeText) themeText.textContent = 'Dark Mode';
        }
        feather.replace();
    }

    // Set initial UI based on what was loaded in header
    updateThemeUI(document.documentElement.getAttribute('data-theme'));

    themeToggle.addEventListener('click', () => {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'light' ? 'dark' : 'light';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateThemeUI(newTheme);
    });

    // Sidebar Collapse Logic (Desktop)
    const sidebar = document.getElementById('sidebar');
    const collapseToggle = document.getElementById('collapseToggle');
    
    // Remove init class since JS is now active
    document.documentElement.classList.remove('sidebar-collapsed-init');
    
    if (window.innerWidth > 1024) {
        collapseToggle.style.display = 'flex';
        const savedState = localStorage.getItem('sidebarState') || 'expanded';
        if (savedState === 'collapsed') {
            sidebar.classList.add('collapsed');
        }

        collapseToggle.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarState', isCollapsed ? 'collapsed' : 'expanded');
        });
    }

    // Mobile Menu Logic
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    if (mobileMenuBtn && sidebarOverlay) {
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.add('mobile-open');
            sidebarOverlay.classList.add('show');
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('mobile-open');
            sidebarOverlay.classList.remove('show');
        });
    }

    // Handle resize events to fix states
    window.addEventListener('resize', () => {
        if (window.innerWidth > 1024) {
            collapseToggle.style.display = 'flex';
            sidebar.classList.remove('mobile-open');
            if(sidebarOverlay) sidebarOverlay.classList.remove('show');
            
            if (localStorage.getItem('sidebarState') === 'collapsed') {
                sidebar.classList.add('collapsed');
            }
        } else {
            collapseToggle.style.display = 'none';
            sidebar.classList.remove('collapsed');
        }
    });
</script>
</body>
</html>
