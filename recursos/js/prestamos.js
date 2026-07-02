const ENDPOINT_PRES = '../servicios/prestamos.php';

document.addEventListener('DOMContentLoaded', async () => {
    let usuarios    = [];
    let perifericos = [];
    let prestamos   = [];

    const hoyISO   = () => new Date().toISOString().slice(0, 10);
    const normalizar = s => (s || '').toString().toLowerCase().normalize('NFD').replace(/\p{Diacritic}/gu, '');
    const mapaSerie = new Map();

    const cuerpo      = document.getElementById('prestBody');
    const resumen     = document.getElementById('resumen');
    const btnNuevo    = document.getElementById('btnNuevo');
    const btnCancelar = document.getElementById('btnCancelar');
    const btnAgregar  = document.getElementById('btnAgregar');
    const formPrest   = document.getElementById('formPrestamo');
    const msgError    = document.getElementById('msgError');
    const selUsuario  = document.getElementById('selUsuario');
    const inpEquipo   = document.getElementById('inpEquipo');
    const selSerie    = document.getElementById('selSerie');
    const inpFechaP   = document.getElementById('inpFechaPrestamo');
    const inpFechaD   = document.getElementById('inpFechaDevolucion');
    const campoBuscar = document.getElementById('txtBuscar');

    /* ── Cargar datos iniciales ── */
    try {
        const initial = await realizarPeticion(ENDPOINT_PRES);
        if (initial.exito) {
            usuarios    = Array.isArray(initial.usuarios)    ? initial.usuarios    : [];
            perifericos = Array.isArray(initial.perifericos) ? initial.perifericos : [];
        }
    } catch (err) {
        console.error('Error cargando datos:', err);
    }

    perifericos.forEach(p => {
        if (p && p.sn && p.nombre) mapaSerie.set(String(p.sn), String(p.nombre));
    });

    /* ── Poblar selects ── */
    function poblarUsuarios() {
        selUsuario.innerHTML =
            '<option value="" selected disabled>Selecciona un usuario...</option>' +
            usuarios.filter(Boolean).sort((a, b) => a.localeCompare(b, 'es'))
                .map(n => `<option value="${n}">${n}</option>`).join('');
    }

    function poblarSeries() {
        selSerie.innerHTML =
            '<option value="" selected disabled>Selecciona una serie...</option>' +
            perifericos.filter(p => p && p.sn && p.nombre)
                .sort((a, b) => String(a.sn).localeCompare(String(b.sn), 'es', { numeric: true }))
                .map(p => `<option value="${p.sn}">${p.sn} — ${p.nombre}</option>`).join('');
    }

    poblarUsuarios();
    poblarSeries();

    /* ── Cargar prestamos desde BD ── */
    async function cargarPrestamos() {
        try {
            const datos = await realizarPeticion(`${ENDPOINT_PRES}?accion=listar`);
            if (datos.exito) {
                prestamos = datos.datos || [];
            }
        } catch (err) {
            console.error('Error cargando prestamos:', err);
            prestamos = [];
        }
        renderizarPrestamos();
    }

    /* ── Render tabla ── */
    function renderizarPrestamos() {
        let lista = prestamos.slice().sort((a, b) =>
            (a.estado === b.estado) ? 0 : (a.estado === 'Activo' ? -1 : 1)
        );
        const q = normalizar(campoBuscar.value);
        if (q) {
            lista = lista.filter(p =>
                normalizar(p.usuario).includes(q) ||
                normalizar(p.equipo).includes(q) ||
                normalizar(p.serie).includes(q)
            );
        }

        if (!lista.length) {
            cuerpo.innerHTML = '<tr><td class="celda-vacia" colspan="7">Sin prestamos...</td></tr>';
        } else {
            cuerpo.innerHTML = lista.map(p => {
                const hoy     = hoyISO();
                const vencido = (p.estado === 'Activo' && p.fecha_devolucion && p.fecha_devolucion < hoy);
                const chipEstado = p.estado === 'Activo'
                    ? `<span class="chip${vencido ? ' vencido' : ''}">${vencido ? 'Vencido' : 'Activo'}</span>`
                    : '<span class="chip">Devuelto</span>';
                const btnDev = p.estado === 'Activo'
                    ? `<button class="btn-principal" style="padding:8px 12px" data-devuelve="${p.id}">Devolver</button>`
                    : '<span style="opacity:.6">—</span>';
                return `<tr>
                    <td title="${p.usuario}">${p.usuario}</td>
                    <td>${p.equipo || ''}</td>
                    <td>${p.serie || ''}</td>
                    <td>${p.fecha_prestamo || ''}</td>
                    <td>${p.fecha_devolucion || ''}</td>
                    <td>${chipEstado}</td>
                    <td>${btnDev}</td>
                </tr>`;
            }).join('');
        }

        const activos   = prestamos.filter(p => p.estado === 'Activo').length;
        const devueltos = prestamos.length - activos;
        resumen.textContent = `Prestamos: ${prestamos.length}  Activos: ${activos}  Devueltos: ${devueltos}`;

        cuerpo.querySelectorAll('[data-devuelve]').forEach(btn => {
            btn.addEventListener('click', () => devolverPrestamo(btn.getAttribute('data-devuelve')));
        });
    }

    /* ── Form ── */
    function alternarFormulario(mostrar) {
        formPrest.classList.toggle('oculto', !mostrar);
        msgError.textContent = '';
        if (mostrar) {
            selUsuario.value    = '';
            selSerie.value      = '';
            inpEquipo.value     = '';
            inpFechaP.value     = hoyISO();
            inpFechaD.value     = '';
            selUsuario.focus();
        }
    }

    function validarFormulario() {
        if (!selUsuario.value)   return 'Selecciona el usuario.';
        if (!selSerie.value)     return 'Selecciona el numero de serie.';
        if (!inpEquipo.value.trim()) return 'No se pudo obtener el equipo.';
        if (!inpFechaP.value)    return 'Indica la fecha de prestamo.';
        if (!inpFechaD.value)    return 'Indica la fecha de devolucion.';
        if (inpFechaD.value < inpFechaP.value) return 'La fecha de devolucion no puede ser anterior a la de prestamo.';
        return '';
    }

    async function agregarPrestamo() {
        const error = validarFormulario();
        if (error) { msgError.textContent = error; return; }

        btnAgregar.disabled = true;
        btnAgregar.textContent = 'Guardando...';

        try {
            const fd = new FormData();
            fd.append('accion', 'insertar');
            fd.append('usuario', selUsuario.value);
            fd.append('equipo', inpEquipo.value.trim());
            fd.append('serie', selSerie.value);
            fd.append('fecha_prestamo', inpFechaP.value);
            fd.append('fecha_devolucion', inpFechaD.value);

            const datos = await realizarPeticion(ENDPOINT_PRES, { method: 'POST', body: fd });
            if (datos.exito) {
                await cargarPrestamos();
                alternarFormulario(false);
            } else {
                msgError.textContent = datos.mensaje || 'Error al guardar';
            }
        } catch (err) {
            console.error(err);
            msgError.textContent = 'Error de conexion.';
        } finally {
            btnAgregar.disabled = false;
            btnAgregar.textContent = 'Agregar prestamo';
        }
    }

    async function devolverPrestamo(id) {
        try {
            const fd = new FormData();
            fd.append('accion', 'devolver');
            fd.append('id', id);

            const datos = await realizarPeticion(ENDPOINT_PRES, { method: 'POST', body: fd });
            if (datos.exito) {
                await cargarPrestamos();
            } else {
                alert(datos.mensaje || 'Error al devolver');
            }
        } catch (err) {
            console.error(err);
            alert('Error de conexion.');
        }
    }

    /* ── Eventos ── */
    btnNuevo.addEventListener('click', () => alternarFormulario(formPrest.classList.contains('oculto')));
    btnCancelar.addEventListener('click', () => alternarFormulario(false));
    btnAgregar.addEventListener('click', agregarPrestamo);
    campoBuscar.addEventListener('input', renderizarPrestamos);
    selSerie.addEventListener('change', () => {
        inpEquipo.value = mapaSerie.get(selSerie.value) || '';
    });

    await cargarPrestamos();
    if (!formPrest.classList.contains('oculto')) {
        inpFechaP.value = hoyISO();
    }
});
