const ENDPOINT_USR = '../servicios/usuarios.php';
let usuariosGlobal = [];

/* ── Exportar Excel ── */
function exportarExcel() {
    window.location.href = `${ENDPOINT_USR}?accion=exportar_excel`;
}

/* ── Helpers de filtro ── */
function construirUbicaciones(lista) {
    const select = document.getElementById('filterUbicacion');
    if (!select) return;

    const ubicaciones = Array.from(
        new Set((lista || []).map(u => (u.ubicacion || '').trim()).filter(Boolean))
    ).sort((a, b) => a.localeCompare(b, 'es', { sensitivity: 'base' }));

    select.innerHTML =
        '<option value="">Todas las ubicaciones</option>' +
        ubicaciones.map(u => `<option value="${u}">${u}</option>`).join('');
}

function aplicarFiltros() {
    const termino = (document.getElementById('searchInput')?.value || '').toLowerCase().trim();
    const ubic    = document.getElementById('filterUbicacion')?.value || '';

    const filtrados = (usuariosGlobal || []).filter(u => {
        const coincideUbic = !ubic || (u.ubicacion || '') === ubic;
        const coincideTerm = !termino || [u.nomina, u.nombre, u.ubicacion, u.activo, u.fecha]
            .some(v => String(v || '').toLowerCase().includes(termino));
        return coincideUbic && coincideTerm;
    });
    renderizarTabla(filtrados);
}

/* ── Carga y render ── */
async function cargarUsuarios() {
    const cuerpo = document.getElementById('assets-data');
    const infoPag = document.querySelector('.pagination-info');
    cuerpo.innerHTML = '<tr><td colspan="6" class="celda-vacia">Cargando usuarios...</td></tr>';

    try {
        const datos = await realizarPeticion(ENDPOINT_USR);
        usuariosGlobal = datos.datos || [];
        construirUbicaciones(usuariosGlobal);
        renderizarTabla(usuariosGlobal);
    } catch (err) {
        console.error(err);
        cuerpo.innerHTML = '<tr><td colspan="6" class="celda-vacia" style="color:#ffdddd">Error al cargar usuarios</td></tr>';
        if (infoPag) infoPag.textContent = 'Mostrando Usuarios 0 de 0';
    }
}

function renderizarTabla(usuarios) {
    const cuerpo  = document.getElementById('assets-data');
    const infoPag = document.querySelector('.pagination-info');
    cuerpo.innerHTML = '';

    const modoEdicion = !!document.getElementById('modalEditarUsuario');
    const modoVer     = !!document.querySelector('.btn-carta');

    if (!usuarios.length) {
        const cols = modoEdicion ? 6 : (modoVer ? 6 : 5);
        cuerpo.innerHTML = `<tr><td colspan="${cols}" class="celda-vacia">No se encontraron usuarios</td></tr>`;
        if (infoPag) infoPag.textContent = 'Mostrando Usuarios 0 de 0';
        return;
    }

    usuarios.forEach(usuario => {
        const fila = document.createElement('tr');
        if (modoEdicion) {
            fila.innerHTML = `
                <td>${usuario.nomina || 'N/A'}</td>
                <td><strong>${usuario.nombre || 'N/A'}</strong></td>
                <td>${usuario.ubicacion || 'N/A'}</td>
                <td>${usuario.activo || 'N/A'}</td>
                <td>${usuario.fecha || 'N/A'}</td>
                <td class="col-editar">
                    <button class="boton-editar" title="Editar usuario" aria-label="Editar"></button>
                </td>
            `;
            fila.querySelector('.boton-editar').addEventListener('click', () => abrirFormulario(usuario));
        } else {
            fila.innerHTML = `
                <td>${usuario.nomina || 'N/A'}</td>
                <td><strong>${usuario.nombre || 'N/A'}</strong></td>
                <td>${usuario.ubicacion || 'N/A'}</td>
                <td>${usuario.activo || 'N/A'}</td>
                <td>${usuario.fecha || 'N/A'}</td>
                <td>
                    <button class="btn-exportar btn-carta"
                        data-nomina="${(usuario.nomina || '').replace(/"/g,'&quot;')}"
                        data-activo="${(usuario.activo || '').replace(/"/g,'&quot;')}">
                        Carta
                    </button>
                </td>
            `;
        }
        cuerpo.appendChild(fila);
    });

    if (infoPag) infoPag.textContent = `Mostrando Usuarios 1 a ${usuarios.length} de ${usuarios.length}`;
}

/* ── Modal edición ── */
function abrirFormulario(usuario) {
    const modal = document.getElementById('modalEditarUsuario');
    const form  = document.getElementById('formEditarUsuario');
    if (!modal || !form) return;

    form.querySelector('[name="nomina"]').value       = usuario.nomina    || '';
    document.getElementById('edit-nomina_display').value = usuario.nomina || '';
    form.querySelector('[name="nombre"]').value       = usuario.nombre    || '';
    form.querySelector('[name="ubicacion"]').value    = usuario.ubicacion || '';
    form.querySelector('[name="activo"]').value       = usuario.activo    || '';
    form.querySelector('[name="fecha"]').value        = usuario.fecha     || '';
    modal.classList.add('abierto');
}

/* ── Init ── */
document.addEventListener('DOMContentLoaded', () => {
    cargarUsuarios();

    const campoBuscar   = document.getElementById('searchInput');
    const selUbicacion  = document.getElementById('filterUbicacion');
    if (campoBuscar)  campoBuscar.addEventListener('input', aplicarFiltros);
    if (selUbicacion) selUbicacion.addEventListener('change', aplicarFiltros);

    const modalEditar   = document.getElementById('modalEditarUsuario');
    const formEditar    = document.getElementById('formEditarUsuario');
    const btnCancelar   = document.getElementById('cancelarEditar');

    if (btnCancelar && modalEditar) {
        btnCancelar.addEventListener('click', () => modalEditar.classList.remove('abierto'));
        window.addEventListener('click', e => {
            if (e.target === modalEditar) modalEditar.classList.remove('abierto');
        });
    }

    if (formEditar) {
        formEditar.addEventListener('submit', async function (e) {
            e.preventDefault();
            const boton = document.getElementById('guardarCambios');
            boton.textContent = 'Guardando...';
            boton.disabled = true;

            try {
                const datos = await realizarPeticion(ENDPOINT_USR, { method: 'POST', body: new FormData(this) });
                if (datos.exito) {
                    alert('¡Usuario actualizado correctamente!');
                    modalEditar.classList.remove('abierto');
                    cargarUsuarios();
                } else {
                    alert('Error al actualizar: ' + (datos.mensaje || 'Respuesta no válida.'));
                }
            } catch {
                alert('Ocurrió un error de conexión. Inténtalo de nuevo.');
            } finally {
                boton.textContent = 'Guardar Cambios';
                boton.disabled = false;
            }
        });
    }

    /* ── Botón carta (ver_asignado.html) ── */
    const cuerpoDatos = document.getElementById('assets-data');
    if (cuerpoDatos) {
        cuerpoDatos.addEventListener('click', e => {
            const btn = e.target.closest('.btn-carta');
            if (!btn) return;
            const nomina = btn.dataset.nomina;
            const activo = btn.dataset.activo;
            window.open(`../servicios/carta.php?nomina=${encodeURIComponent(nomina)}&activo=${encodeURIComponent(activo)}`, '_blank');
        });
    }
});
