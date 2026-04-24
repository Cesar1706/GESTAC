const ENDPOINT_ACT = '../servicios/activos.php';
let activosGlobal = [];

/* ── Exportar Excel ── */
function exportarExcel() {
    window.location.href = `${ENDPOINT_ACT}?accion=exportar_excel`;
}

/* ── Cargar y render ── */
async function cargarActivos() {
    const cuerpo       = document.getElementById('assets-data');
    const filtroCat    = document.getElementById('tipoFilter');

    cuerpo.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:20px">Cargando activos...</td></tr>';

    try {
        const datos = await realizarPeticion(ENDPOINT_ACT);
        activosGlobal = datos.datos || [];
        renderizarTablaActivos(activosGlobal);

        if (filtroCat) {
            const categorias = Array.from(new Set(activosGlobal.map(a => a.categoria).filter(Boolean)));
            filtroCat.innerHTML = '<option value="todos">Todos</option>' +
                categorias.map(c => `<option value="${c}">${c}</option>`).join('');
        }
    } catch (err) {
        console.error(err);
        const cols = document.getElementById('assets-data').closest('table')
            .querySelector('thead tr').children.length;
        document.getElementById('assets-data').innerHTML =
            `<tr><td colspan="${cols}" style="text-align:center;color:red">Error al cargar activos</td></tr>`;
    }
}

function renderizarTablaActivos(activos) {
    const cuerpo = document.getElementById('assets-data');
    const infoP  = document.querySelector('.pagination-info');
    cuerpo.innerHTML = '';

    const modoEdicion = !!document.getElementById('modalEditarActivo');
    const cols = modoEdicion ? 7 : 6;

    if (!activos.length) {
        cuerpo.innerHTML = `<tr><td colspan="${cols}" style="text-align:center;padding:20px">No se encontraron activos</td></tr>`;
        if (infoP) infoP.textContent = 'Mostrando Activos 0 de 0';
        return;
    }

    activos.forEach(activo => {
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td class="col-id">#${activo.id_producto}</td>
            <td class="col-titulo"><strong>${activo.nombre || 'N/A'}</strong></td>
            <td class="col-categoria">${activo.categoria || 'N/A'}</td>
            <td class="col-procesador">${activo.procesador || 'N/A'}</td>
            <td class="col-caracteristicas">${activo.ram || 'N/A'} / ${activo.almacenamiento || 'N/A'}</td>
            <td class="col-lote">${activo.lote || 'N/A'}</td>
            ${modoEdicion ? '<td class="col-editar"><button class="boton-editar" title="Editar"></button></td>' : ''}
        `;
        if (modoEdicion) {
            fila.querySelector('.boton-editar').addEventListener('click', () => abrirFormularioEdicion(activo));
        }
        cuerpo.appendChild(fila);
    });

    if (infoP) infoP.textContent = `Mostrando Activos 1 a ${activos.length} de ${activosGlobal.length}`;
}

function aplicarFiltrosActivos() {
    const catVal    = (document.getElementById('tipoFilter')?.value || 'todos').toLowerCase();
    const busqueda  = (document.getElementById('searchInput')?.value || '').toLowerCase();

    const filtrados = activosGlobal.filter(a => {
        const coincideCat = catVal === 'todos' || (a.categoria && a.categoria.toLowerCase() === catVal);
        const texto = `${a.nombre || ''} ${a.id_producto || ''} ${a.procesador || ''} ${a.ram || ''} ${a.almacenamiento || ''} ${a.lote || ''}`.toLowerCase();
        return coincideCat && texto.includes(busqueda);
    });
    renderizarTablaActivos(filtrados);
}

/* ── Edición (solo en modificar_activo.html) ── */
function abrirFormularioEdicion(activo) {
    const modal = document.getElementById('modalEditarActivo');
    const form  = document.getElementById('formEditarActivo');
    if (!modal || !form) return;

    form.querySelector('[name="id_producto"]').value           = activo.id_producto;
    form.querySelector('#edit-id_producto_display').value      = activo.id_producto;
    form.querySelector('[name="categoria"]').value             = activo.categoria || '';
    form.querySelector('[name="lote"]').value                  = activo.lote || '';
    form.querySelector('[name="nombre"]').value                = activo.nombre || '';
    form.querySelector('[name="descripcion"]').value           = activo.descripcion || '';
    form.querySelector('[name="marca"]').value                 = activo.marca || '';
    form.querySelector('[name="procesador"]').value            = activo.procesador || '';
    form.querySelector('[name="almacenamiento"]').value        = activo.almacenamiento || '';
    form.querySelector('[name="ram"]').value                   = activo.ram || '';
    modal.classList.add('abierto');
}

/* ── Importar Excel (SheetJS) ── */
function inicializarImportacion() {
    const btnPlantilla  = document.getElementById('btnDescargarPlantilla');
    const inputArchivo  = document.getElementById('importFile');

    if (btnPlantilla) {
        btnPlantilla.addEventListener('click', () => {
            const encabezados = [["#ID producto", "Modelo", "Categoria", "Procesador", "Caracteristica", "Lote"]];
            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.aoa_to_sheet(encabezados);
            ws['!cols'] = [{ wch: 15 }, { wch: 30 }, { wch: 15 }, { wch: 20 }, { wch: 25 }, { wch: 15 }];
            XLSX.utils.book_append_sheet(wb, ws, 'Plantilla_GESTAC');
            XLSX.writeFile(wb, 'Plantilla_Importacion_GESTAC.xlsx');
        });
    }

    if (inputArchivo) {
        inputArchivo.addEventListener('change', function (e) {
            const archivo = e.target.files[0];
            if (!archivo) return;

            const lector = new FileReader();
            lector.onload = function (evento) {
                const datos    = new Uint8Array(evento.target.result);
                const workbook = XLSX.read(datos, { type: 'array' });
                const jsonData = XLSX.utils.sheet_to_json(workbook.Sheets[workbook.SheetNames[0]]);

                if (!jsonData.length) return alert('El archivo está vacío.');

                if (confirm(`¿Deseas cargar ${jsonData.length} activos al sistema GESTAC?`)) {
                    realizarPeticion(ENDPOINT_ACT, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(jsonData)
                    })
                    .then(resultado => {
                        alert(resultado.mensaje);
                        if (resultado.exito) cargarActivos();
                    })
                    .catch(() => alert('Error de conexión al importar.'));
                }
            };
            lector.readAsArrayBuffer(archivo);
            this.value = '';
        });
    }
}

/* ── Init ── */
document.addEventListener('DOMContentLoaded', () => {
    cargarActivos();

    const filtroCat  = document.getElementById('tipoFilter');
    const campoBusq  = document.getElementById('searchInput');
    if (filtroCat)  filtroCat.addEventListener('change', aplicarFiltrosActivos);
    if (campoBusq)  campoBusq.addEventListener('input',  aplicarFiltrosActivos);

    inicializarImportacion();

    const btnCancelarEditar = document.getElementById('cancelarEditar');
    const modalEditar       = document.getElementById('modalEditarActivo');
    const formEditar        = document.getElementById('formEditarActivo');

    if (btnCancelarEditar && modalEditar) {
        btnCancelarEditar.addEventListener('click', () => modalEditar.classList.remove('abierto'));
        window.addEventListener('click', e => {
            if (e.target === modalEditar) modalEditar.classList.remove('abierto');
        });
    }

    if (formEditar) {
        formEditar.addEventListener('submit', async function (e) {
            e.preventDefault();
            const fd = new FormData(this);
            try {
                const datos = await realizarPeticion(ENDPOINT_ACT, { method: 'POST', body: fd });
                if (datos.exito) {
                    alert('Actualizado');
                    modalEditar.classList.remove('abierto');
                    cargarActivos();
                } else {
                    alert(datos.mensaje || 'Error al actualizar');
                }
            } catch {
                alert('Error de comunicación con el servidor');
            }
        });
    }
});
