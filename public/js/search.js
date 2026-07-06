/* =========================================================
 * Proyecto      : Sistema de Gestión CMDB para TFG
 * Archivo       : public/js/search.js
 * Autor         : Javier Moyano Vizcaíno
 * Curso         : 2025/2026
 * Descripción   : Interactividad de la pantalla de búsqueda.
 *
 * Incluye:
 *   - Toggle del panel de búsqueda avanzada
 *   - Detección del modo de búsqueda (CI vs Oficinas)
 *     y actualización dinámica del botón y aviso
 *   - Limpiar filtros avanzados
 *   - Toggle tabla / tarjetas con persistencia en localStorage
 *   - Navegación por clic en fila de tabla
 *   - Autocompletado AJAX con debounce y navegación por teclado
 *   - Loader de búsqueda al enviar formulario (simple o avanzado)
 * ========================================================= */

/* Ocultar el loader al cargar la página 
   (se mostrará al buscar un CI: enviar un formulario) */
const loader = document.getElementById('searchLoader');
if (loader) loader.hidden = true;

document.addEventListener('DOMContentLoaded', () => {

    /* ================================================================
       TOGGLE PANEL BÚSQUEDA AVANZADA
    ================================================================ */
    const toggleBtn = document.getElementById('toggleAdvanced');
    const advPanel  = document.getElementById('advancedPanel');

    if (toggleBtn && advPanel) {
        toggleBtn.addEventListener('click', () => {
            const isOpen = advPanel.classList.contains('open');
            advPanel.classList.toggle('open', !isOpen);
            toggleBtn.classList.toggle('active', !isOpen);
        });
    }

    /* ================================================================
       DETECCIÓN DE MODO: CI vs OFICINAS
       Observa todos los campos del formulario avanzado y actualiza
       el botón y el aviso informativo según qué secciones tienen datos.

       Lógica:
         - Solo campos ERP rellenos  → modo OFICINAS (aviso visible,
           botón cambia a "Buscar oficinas")
         - Campos CMDB rellenos      → modo CI (aviso oculto,
           botón muestra "Buscar con filtros")
         - Mezcla CMDB+ERP           → modo CI con cruce ERP
    ================================================================ */
    const advancedForm   = document.getElementById('advancedForm');
    const btnTexto       = document.getElementById('btnBuscarTexto');
    const avisoOficinas  = document.getElementById('avisoOficinas');

    /*
     * Campos de cada grupo — deben coincidir con los name=""
     * del formulario en search.php.
     */
    const camposCmdb = ['id_ci','clase','marca','modelo','numero_serie',
                        'hostname','ip','login','nombre_local','fecha_desde','fecha_hasta'];
    const camposErp  = ['id_oficina','nombre_oficina','direccion',
                        'cp','ciudad','unidad_organica'];

    function tieneValor(nombres) {
        return nombres.some(name => {
            const el = advancedForm?.querySelector(`[name="${name}"]`);
            return el && el.value.trim() !== '';
        });
    }

    function actualizarModo() {
        if (!advancedForm || !btnTexto || !avisoOficinas) return;

        const hayCmdb = tieneValor(camposCmdb);
        const hayErp  = tieneValor(camposErp);

        if (hayErp && !hayCmdb) {
            /* Solo ERP → modo Oficinas */
            btnTexto.textContent = 'Buscar oficinas';
            avisoOficinas.hidden  = false;
        } else {
            /* CMDB o mezcla → modo CI */
            btnTexto.textContent = 'Buscar con filtros';
            avisoOficinas.hidden  = true;
        }
    }

    /* Escuchar cambios en todos los inputs y selects del formulario */
    if (advancedForm) {
        advancedForm.querySelectorAll('input, select').forEach(el => {
            el.addEventListener('input',  actualizarModo);
            el.addEventListener('change', actualizarModo);
        });
        /* Evaluar estado inicial (puede venir con filtros pre-rellenados) */
        actualizarModo();
    }

    /* ================================================================
       LIMPIAR BÚSQUEDA SIMPLE
    ================================================================ */
    const clearBtn = document.getElementById('clearSearch');
    if (clearBtn) {
        clearBtn.addEventListener('click', () => {
            document.getElementById('searchInput').value = '';
            window.location.href = 'index.php?action=search';
        });
    }

    /* ================================================================
       LIMPIAR FILTROS AVANZADOS
    ================================================================ */
    const clearAdvanced = document.getElementById('clearAdvanced');
    if (clearAdvanced) {
        clearAdvanced.addEventListener('click', () => {
            advancedForm.querySelectorAll('input, select')
                .forEach(el => { el.value = ''; });
            /* Actualizar el modo tras limpiar */
            actualizarModo();
        });
    }

    /* ================================================================
       TOGGLE TABLA / TARJETAS
       Persiste la preferencia del usuario en localStorage.
    ================================================================ */
    const tableView    = document.getElementById('tableView');
    const cardsView    = document.getElementById('cardsView');
    const viewTableBtn = document.getElementById('viewTable');
    const viewCardsBtn = document.getElementById('viewCards');

    if (viewTableBtn && viewCardsBtn) {
        viewTableBtn.addEventListener('click', () => {
            tableView.hidden = false;
            cardsView.hidden = true;
            viewTableBtn.classList.add('active');
            viewCardsBtn.classList.remove('active');
            localStorage.setItem('cmdb_view', 'table');
        });

        viewCardsBtn.addEventListener('click', () => {
            tableView.hidden = true;
            cardsView.hidden = false;
            viewCardsBtn.classList.add('active');
            viewTableBtn.classList.remove('active');
            localStorage.setItem('cmdb_view', 'cards');
        });

        /* Restaurar preferencia guardada */
        if (localStorage.getItem('cmdb_view') === 'cards') {
            viewCardsBtn.click();
        }
    }

    /* ================================================================
       CLIC EN FILA DE TABLA → DETALLE CI
    ================================================================ */
    document.querySelectorAll('.result-row').forEach(row => {
        row.addEventListener('click', (e) => {
            if (e.target.closest('.btn-detail')) return; // no interferir con el botón
            const id = row.dataset.id;
            if (id) window.location.href = `index.php?action=detail&id=${id}`;
        });
    });

    /* ================================================================
       AUTOCOMPLETADO AJAX
       Busca sugerencias mientras el usuario escribe (debounce 300ms).
       Soporta navegación por teclado (↑↓, Enter, Escape).
    ================================================================ */
    const searchInput = document.getElementById('searchInput');
    const suggestions = document.getElementById('searchSuggestions');
    let debounceTimer = null;
    let lastQuery     = '';

    if (searchInput && suggestions) {

        searchInput.addEventListener('input', () => {
            const q = searchInput.value.trim();
            clearTimeout(debounceTimer);

            if (q.length < 2) { hideSuggestions(); return; }
            if (q === lastQuery) return;

            debounceTimer = setTimeout(() => {
                lastQuery = q;
                fetchSuggestions(q);
            }, 300);
        });

        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') hideSuggestions();
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const items = suggestions.querySelectorAll('.suggestion-item');
                if (items.length) items[0].focus();
            }
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-bar-form')) hideSuggestions();
        });
    }

    function fetchSuggestions(q) {
        fetch(`index.php?action=api_search&q=${encodeURIComponent(q)}`)
            .then(r => r.json())
            .then(data => {
                if (data.resultados && data.resultados.length > 0) {
                    renderSuggestions(data.resultados.slice(0, 8));
                } else {
                    hideSuggestions();
                }
            })
            .catch(() => hideSuggestions());
    }

    function renderSuggestions(items) {
        suggestions.innerHTML = '';
        items.forEach(ci => {
            const label     = [ci.marca, ci.modelo].filter(Boolean).join(' ') || ci.clase;
            const secondary = ci.nombre_local || ci.hostname || ci.direccion_ip || '';

            const div = document.createElement('div');
            div.className = 'suggestion-item';
            div.tabIndex  = 0;
            div.innerHTML = `
                <span class="suggestion-icon"><i class="fas fa-cube"></i></span>
                <span>
                    <strong>${escapeHtml(label)}</strong>
                    <span style="color:#999;font-size:.82rem;margin-left:6px;">
                        ${escapeHtml(ci.clase)}
                    </span>
                    ${secondary
                        ? `<span style="display:block;font-size:.8rem;color:#666;">
                               ${escapeHtml(secondary)}
                           </span>`
                        : ''}
                </span>
                <span style="margin-left:auto;font-size:.78rem;color:#bbb;">#${ci.id_ci}</span>
            `;

            const irADetalle = () => {
                window.location.href = `index.php?action=detail&id=${ci.id_ci}`;
            };

            div.addEventListener('click', irADetalle);
            div.addEventListener('keydown', (e) => {
                if (e.key === 'Enter')     irADetalle();
                if (e.key === 'Escape')    hideSuggestions();
                if (e.key === 'ArrowDown') { e.preventDefault(); div.nextElementSibling?.focus(); }
                if (e.key === 'ArrowUp')   {
                    e.preventDefault();
                    div.previousElementSibling
                        ? div.previousElementSibling.focus()
                        : searchInput.focus();
                }
            });

            suggestions.appendChild(div);
        });

        suggestions.hidden = false;
    }

    function hideSuggestions() {
        if (suggestions) suggestions.hidden = true;
    }

    function escapeHtml(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    }

    /* ================================================================
       LOADER DE BÚSQUEDA
       Se muestra al enviar cualquiera de los dos formularios
       (simple o avanzado) y se oculta automáticamente cuando el
       DOM ya tiene los resultados (la página nueva ha cargado).

       Nota: el loader solo se activa cuando hay algo que buscar,
       evitando mostrarlo al limpiar filtros o al enviar vacío.
    ================================================================ */
    // const loader       = document.getElementById('searchLoader');
    const searchForm   = document.getElementById('searchForm');
    const advForm      = document.getElementById('advancedForm');

    function mostrarLoader() {
        if (loader) loader.hidden = false;
    }

    /* Formulario de búsqueda simple: mostrar loader solo si hay término */
    if (searchForm) {
        searchForm.addEventListener('submit', () => {
            const q = document.getElementById('searchInput')?.value.trim();
            if (q) mostrarLoader();
        });
    }

    /* Formulario de búsqueda avanzada: mostrar loader siempre que se envíe */
    if (advForm) {
        advForm.addEventListener('submit', mostrarLoader);
    }

    /*
     * Ocultar el loader en cuanto el DOM esté listo (la página de
     * resultados ya ha cargado). Si el usuario pulsa "atrás" desde
     * los resultados, el navegador puede restaurar la página cacheada
     * con el loader visible — pageshow con persisted=true lo oculta.
     */
    // if (loader) loader.hidden = true;

    window.addEventListener('pageshow', (e) => {
        if (loader) loader.hidden = true;
    });
});
