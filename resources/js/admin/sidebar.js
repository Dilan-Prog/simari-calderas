(function () {
        'use strict';
    
        const mainContent      = document.getElementById('mainContent');
        const loadingState     = document.getElementById('loadingState');
        const breadcrumbSection = document.getElementById('breadcrumbSection');
        const navItems         = document.querySelectorAll('.sidebar-nav-item[data-section]');
    
        // ── Secciones con nombres legibles ──
        const sectionLabels = {
            dashboard : 'Dashboard',
            usuarios  : 'Usuarios',
        };
    
        // ── Activar ítem del nav ──
        function setActiveNav(button) {
            navItems.forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
        }
    
        // ── Mostrar spinner ──
        function showLoading() {
            mainContent.innerHTML = '';
            loadingState.style.display = 'flex';
            mainContent.appendChild(loadingState);
        }
    
        // ── Renderizar HTML externo en el área de contenido ──
        function renderHTML(html) {
            mainContent.innerHTML = html;
            // Re-ejecutar scripts inline del contenido cargado
            mainContent.querySelectorAll('script').forEach(oldScript => {
                const newScript = document.createElement('script');
                newScript.textContent = oldScript.textContent;
                oldScript.parentNode.replaceChild(newScript, oldScript);
            });
        }
    
        // ── Sección "próximamente" ──
        function renderComingSoon(label) {
            mainContent.innerHTML = `
                <div class="admin-coming-soon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="1.5"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22C6.48 22 2 17.52 2 12S6.48 2 12 2s10 4.48 10 10-4.48 10-10 10z"/>
                        <path d="M12 8v4"/><path d="M12 16h.01"/>
                    </svg>
                    <h3>${label}</h3>
                    <p>Esta sección estará disponible próximamente.</p>
                </div>`;
        }
    
      
        // ── Carga de sección vía fetch ──
        async function loadSection(section, button, label) {
            setActiveNav(button);
            breadcrumbSection.textContent = label || sectionLabels[section] || section;
    
            if (section === 'dashboard') {
                renderDashboard();
                return;
            }
    
            if (section === 'coming-soon') {
                renderComingSoon(label);
                return;
            }
    
            if (section === 'usuarios') {
                showLoading();
                try {
                    const res = await fetch('/admin/usuarios-partial', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'text/html',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        }
                    });
    
                    if (!res.ok) throw new Error('Error ' + res.status);
    
                    const html = await res.text();
                    renderHTML(html);
                } catch (err) {
                    mainContent.innerHTML = `
                        <div class="admin-coming-soon">
                            <h3>Error al cargar</h3>
                            <p>No se pudo cargar la sección de usuarios.</p>
                        </div>`;
                }
                return;
            }
    
            renderComingSoon(label);
        }
    
        // ── Eventos de navegación ──
        navItems.forEach(btn => {
            btn.addEventListener('click', function () {
                const section = this.dataset.section;
                const label   = this.dataset.label
                                || this.querySelector('.sidebar-nav-label')?.textContent.trim()
                                || section;
                loadSection(section, this, label);
            });
        });
    
        // ── Carga inicial: Usuarios ──
        const usuariosBtn = document.querySelector('[data-section="dashboard"]');
        if (usuariosBtn) {
            loadSection('dashboard', usuariosBtn, 'Dashboard');
        }
    
    })();