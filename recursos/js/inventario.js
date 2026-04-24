const ENDPOINT_INV = '../servicios/inventario.php';
let inventario = [];

const cuerpoTabla = document.querySelector('#tablaPerifericos tbody');
const formulario  = document.getElementById('formInventario');
const infoInv     = document.getElementById('infoInventario');
const campoBuscar = document.getElementById('searchInput');

function renderizarTabla(lista) {
    cuerpoTabla.innerHTML = '';
    if (!lista.length) {
        cuerpoTabla.innerHTML = '<tr><td colspan="3" class="celda-vacia">Sin resultados</td></tr>';
        infoInv.textContent = `Mostrando 0 de ${inventario.length}`;
        return;
    }
    lista.forEach(item => {
        const fila = document.createElement('tr');
        fila.innerHTML = `
            <td>${item.nombre ?? ''}</td>
            <td>${item.sn ?? ''}</td>
            <td class="col-eliminar">
                <button class="btn-eliminar" data-id="${item.id}">Eliminar</button>
            </td>
        `;
        fila.querySelector('.btn-eliminar').addEventListener('click', async (e) => {
            await eliminarElemento(e.currentTarget.getAttribute('data-id'), e.currentTarget);
        });
        cuerpoTabla.appendChild(fila);
    });
    infoInv.textContent = `Mostrando ${lista.length} de ${inventario.length}`;
}

function aplicarFiltro() {
    const termino = (campoBuscar?.value || '').toLowerCase().trim();
    if (!termino) return renderizarTabla(inventario);
    const filtrados = inventario.filter(p =>
        [p.nombre, p.sn].some(v => String(v || '').toLowerCase().includes(termino))
    );
    renderizarTabla(filtrados);
}

async function cargarInventario() {
    cuerpoTabla.innerHTML = '<tr><td colspan="3" class="celda-vacia">Cargando periféricos...</td></tr>';
    try {
        const datos = await realizarPeticion(`${ENDPOINT_INV}?accion=listar`);
        if (datos.exito) {
            inventario = datos.datos || [];
            aplicarFiltro();
        } else {
            throw new Error(datos.mensaje || 'Respuesta no válida');
        }
    } catch (err) {
        console.error(err);
        cuerpoTabla.innerHTML = '<tr><td colspan="3" class="celda-vacia" style="color:#ffdede">Error al cargar datos</td></tr>';
        infoInv.textContent = 'Mostrando 0 de 0';
    }
}

formulario.addEventListener('submit', async (e) => {
    e.preventDefault();
    const nombre = document.getElementById('nombre').value.trim();
    const sn     = document.getElementById('sn').value.trim();
    if (!nombre || !sn) return;

    const boton = document.getElementById('btnAgregar');
    boton.disabled = true;
    boton.textContent = 'Agregando...';

    try {
        const fd = new FormData();
        fd.append('accion', 'insertar');
        fd.append('nombre', nombre);
        fd.append('sn', sn);

        const datos = await realizarPeticion(ENDPOINT_INV, { method: 'POST', body: fd });
        if (datos.exito) {
            formulario.reset();
            await cargarInventario();
            alert('¡Periférico agregado con éxito!');
        } else {
            throw new Error(datos.mensaje || 'No se pudo insertar');
        }
    } catch (err) {
        console.error(err);
        alert('Ocurrió un error al insertar. Revisa la consola.');
    } finally {
        boton.disabled = false;
        boton.textContent = 'Agregar al Inventario';
    }
});

async function eliminarElemento(id, boton) {
    if (!id) return;
    if (!confirm('¿Seguro que deseas eliminar este elemento?')) return;

    const textoOriginal = boton.textContent;
    boton.disabled = true;
    boton.textContent = 'Eliminando...';

    try {
        const fd = new FormData();
        fd.append('accion', 'eliminar');
        fd.append('id', id);

        const datos = await realizarPeticion(ENDPOINT_INV, { method: 'POST', body: fd });
        if (datos.exito) {
            await cargarInventario();
        } else {
            alert('No se pudo eliminar: ' + (datos.mensaje || 'Error'));
        }
    } catch (err) {
        console.error(err);
        alert('Error de conexión al eliminar.');
    } finally {
        boton.disabled = false;
        boton.textContent = textoOriginal;
    }
}

function exportarExcel() {
    window.location.href = `${ENDPOINT_INV}?accion=exportar_excel`;
}

campoBuscar.addEventListener('input', aplicarFiltro);
document.addEventListener('DOMContentLoaded', cargarInventario);
