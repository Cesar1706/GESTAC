document.addEventListener('DOMContentLoaded', async () => {
    /* ── Cargar datos desde el servicio JSON ── */
    let usuarios    = [];
    let perifericos = [];

    try {
        const datos = await realizarPeticion('../servicios/prestamos.php');
        if (datos.exito) {
            usuarios    = Array.isArray(datos.usuarios)    ? datos.usuarios    : [];
            perifericos = Array.isArray(datos.perifericos) ? datos.perifericos : [];
        } else {
            console.error('Error cargando datos:', datos.mensaje);
            const msgError = document.getElementById('msgError');
            if (msgError) msgError.textContent = 'Error cargando datos del servidor.';
        }
    } catch (err) {
        console.error('Error de conexión:', err);
    }

    /* ── Estado ── */
    const CLAVE_LS = 'prestamos_perifericos';
    const hoyISO   = () => new Date().toISOString().slice(0, 10);
    const normalizar = s => (s || '').toString().toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '');
    const cargarLS   = (k, f) => { try { const r = localStorage.getItem(k); return r ? JSON.parse(r) : f; } catch { return f; } };
    const guardarLS  = (k, v) => localStorage.setItem(k, JSON.stringify(v));

    let prestamos = cargarLS(CLAVE_LS, []);
    const mapaSerie = new Map(
        perifericos.filter(p => p && p.sn && p.nombre).map(p => [String(p.sn), String(p.nombre)])
    );

    /* ── Refs ── */
    const cuerpo           = document.getElementById('prestBody');
    const resumen          = document.getElementById('resumen');
    const btnNuevo         = document.getElementById('btnNuevo');
    const btnCancelar      = document.getElementById('btnCancelar');
    const btnAgregar       = document.getElementById('btnAgregar');
    const formPrestamo     = document.getElementById('formPrestamo');
    const msgError         = document.getElementById('msgError');
    const selUsuario       = document.getElementById('selUsuario');
    const inpEquipo        = document.getElementById('inpEquipo');
    const selSerie         = document.getElementById('selSerie');
    const inpFechaPrest    = document.getElementById('inpFechaPrestamo');
    const inpFechaDevol    = document.getElementById('inpFechaDevolucion');
    const campoBuscar      = document.getElementById('txtBuscar');

    /* ── Poblar selects ── */
    function poblarUsuarios() {
        if (!usuarios.length) {
            selUsuario.innerHTML = '<option value="" disabled selected>No hay usuarios</option>';
            return;
        }
        selUsuario.innerHTML =
            '<option value="" selected disabled>Selecciona un usuario…</option>' +
            usuarios.filter(Boolean).sort((a, b) => a.localeCompare(b, 'es'))
                .map(n => `<option value="${n}">${n}</option>`).join('');
    }

    function poblarSeries() {
        if (!perifericos.length) {
            selSerie.innerHTML = '<option value="" disabled selected>No hay series</option>';
            inpEquipo.value = '';
            return;
        }
        selSerie.innerHTML =
            '<option value="" selected disabled>Selecciona una serie…</option>' +
            perifericos.filter(p => p && p.sn && p.nombre)
                .sort((a, b) => String(a.sn).localeCompare(String(b.sn), 'es', { numeric: true }))
                .map(p => `<option value="${p.sn}">${p.sn} — ${p.nombre}</option>`).join('');
    }

    poblarUsuarios();
    poblarSeries();

    /* ── Render tabla ── */
    function renderizarPrestamos() {
        let lista = prestamos.slice().sort((a, b) => (a.estado === b.estado) ? 0 : (a.estado === 'Activo' ? -1 : 1));
        const q   = normalizar(campoBuscar.value);
        if (q) {
            lista = lista.filter(p =>
                normalizar(p.usuario).includes(q) ||
                normalizar(p.equipoNombre).includes(q) ||
                normalizar(p.serie).includes(q)
            );
        }

        if (!lista.length) {
            cuerpo.innerHTML = '<tr><td class="celda-vacia" colspan="7">Sin préstamos…</td></tr>';
        } else {
            cuerpo.innerHTML = lista.map(p => {
                const hoy     = hoyISO();
                const vencido = (p.estado === 'Activo' && p.fechaDevolucion && p.fechaDevolucion < hoy);
                const chipEstado = p.estado === 'Activo'
                    ? `<span class="chip${vencido ? ' vencido' : ''}">${vencido ? 'Vencido' : 'Activo'}</span>`
                    : '<span class="chip">Devuelto</span>';
                const btnDev = p.estado === 'Activo'
                    ? `<button class="btn-principal" style="padding:8px 12px" data-devuelve="${p.id}">Devolver</button>`
                    : '<span style="opacity:.6">—</span>';
                return `<tr>
                    <td title="${p.usuario}">${p.usuario}</td>
                    <td>${p.equipoNombre}</td>
                    <td>${p.serie}</td>
                    <td>${p.fechaPrestamo || ''}</td>
                    <td>${p.fechaDevolucion || ''}</td>
                    <td>${chipEstado}</td>
                    <td>${btnDev}</td>
                </tr>`;
            }).join('');
        }

        const activos    = prestamos.filter(p => p.estado === 'Activo').length;
        const devueltos  = prestamos.length - activos;
        resumen.textContent = `Préstamos: ${prestamos.length} • Activos: ${activos} • Devueltos: ${devueltos}`;

        cuerpo.querySelectorAll('[data-devuelve]').forEach(btn => {
            btn.addEventListener('click', () => devolverPrestamo(btn.getAttribute('data-devuelve')));
        });
    }

    /* ── Form ── */
    function alternarFormulario(mostrar) {
        formPrestamo.classList.toggle('oculto', !mostrar);
        msgError.textContent = '';
        if (mostrar) {
            selUsuario.value      = '';
            selSerie.value        = '';
            inpEquipo.value       = '';
            inpFechaPrest.value   = hoyISO();
            inpFechaDevol.value   = '';
            selUsuario.focus();
        }
    }

    function validarFormulario() {
        if (!selUsuario.value)   return 'Selecciona el usuario.';
        if (!selSerie.value)     return 'Selecciona el número de serie.';
        if (!inpEquipo.value.trim()) return 'No se pudo obtener el equipo (elige una serie válida).';
        if (!inpFechaPrest.value)  return 'Indica la fecha de préstamo.';
        if (!inpFechaDevol.value)  return 'Indica la fecha de devolución.';
        if (inpFechaDevol.value < inpFechaPrest.value) return 'La fecha de devolución no puede ser anterior a la de préstamo.';
        return '';
    }

    function agregarPrestamo() {
        const error = validarFormulario();
        if (error) { msgError.textContent = error; return; }

        prestamos.push({
            id:            'P' + Date.now(),
            usuario:       selUsuario.value,
            equipoNombre:  inpEquipo.value.trim(),
            serie:         selSerie.value,
            fechaPrestamo: inpFechaPrest.value,
            fechaDevolucion: inpFechaDevol.value,
            estado:        'Activo'
        });
        guardarLS(CLAVE_LS, prestamos);
        renderizarPrestamos();
        alternarFormulario(false);
    }

    function devolverPrestamo(id) {
        const p = prestamos.find(x => x.id === id);
        if (!p || p.estado !== 'Activo') return;
        p.estado = 'Devuelto';
        guardarLS(CLAVE_LS, prestamos);
        renderizarPrestamos();
    }

    /* ── Eventos ── */
    btnNuevo.addEventListener('click', () => alternarFormulario(formPrestamo.classList.contains('oculto')));
    btnCancelar.addEventListener('click', () => alternarFormulario(false));
    btnAgregar.addEventListener('click', agregarPrestamo);
    campoBuscar.addEventListener('input', renderizarPrestamos);
    selSerie.addEventListener('change', () => {
        inpEquipo.value = mapaSerie.get(selSerie.value) || '';
    });

    renderizarPrestamos();
    if (!formPrestamo.classList.contains('oculto')) {
        inpFechaPrest.value = hoyISO();
    }
});
