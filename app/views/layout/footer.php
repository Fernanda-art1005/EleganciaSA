        </div> <!-- End of wrapper -->
    </main> <!-- End of main content area -->

    <!-- Global Mobile Menu Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Inicializa todos os ícones Lucide
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            // Menu hambúrguer em dispositivos móveis
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const sidebarMenu = document.getElementById('sidebar-menu');
            const mobileOverlay = document.getElementById('mobile-overlay');

            if (mobileMenuBtn && sidebarMenu && mobileOverlay) {
                const toggleMenu = () => {
                    const isVisible = !sidebarMenuOpen();
                    if (isVisible) {
                        sidebarOpen();
                    } else {
                        sidebarClose();
                    }
                };

                const sidebarOpen = () => {
                    sidebar.classList.remove('-translate-x-full');
                };

                mobileMenuBtn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    sidebarMenu.classList.toggle('-translate-x-full');
                    mobileOverlay.classList.toggle('hidden');
                });

                mobileOverlay.addEventListener('click', () => {
                    sidebarMenu.classList.add('-translate-x-full');
                    mobileOverlay.classList.add('hidden');
                });
            }
        });
    </script>
</body>
</html>
