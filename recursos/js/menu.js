/* ── Paleta de verdes para gráficas ── */
const VERDES = ['#4CAF50', '#81C784', '#2b5a2b', '#a5d6a7', '#388E3C', '#66BB6A'];

/* ===================== MENÚ DE CONFIGURACIÓN ===================== */
const iconoConfig = document.getElementById('configIcon');
const menuConfig  = document.getElementById('configMenu');

iconoConfig.addEventListener('click', e => {
    e.preventDefault();
    e.stopPropagation();
    menuConfig.style.display = (menuConfig.style.display === 'block') ? 'none' : 'block';
});

document.addEventListener('click', e => {
    if (!menuConfig.contains(e.target) && e.target !== iconoConfig) {
        menuConfig.style.display = 'none';
    }
});

/* ===================== AUTOCOMPLETADO ===================== */
function autocompletar(input, tipo, contenedorID) {
    const contenedor = document.getElementById(contenedorID);
    input.addEventListener('input', function () {
        const valor = this.value.trim();
        contenedor.innerHTML = '';
        if (!valor) return;

        fetch(`../servicios/sistema.php?tipo=${tipo}&q=${encodeURIComponent(valor)}`)
            .then(res => res.json())
            .then(datos => {
                contenedor.innerHTML = '';
                datos.forEach(item => {
                    const div = document.createElement('div');
                    div.textContent = item;
                    div.addEventListener('click', () => {
                        input.value = item;
                        contenedor.innerHTML = '';
                    });
                    contenedor.appendChild(div);
                });
            })
            .catch(err => console.error(err));
    });

    document.addEventListener('click', e => {
        if (e.target !== input) contenedor.innerHTML = '';
    });
}

const inpUsuario = document.getElementById('usuario');
const inpActivo  = document.getElementById('activo');
if (inpUsuario) autocompletar(inpUsuario, 'usuario', 'usuarioList');
if (inpActivo)  autocompletar(inpActivo,  'activo',  'activoList');

/* ===================== SELECTOR PERSONALIZADO ===================== */
document.querySelectorAll('.selector-personalizado').forEach(selector => {
    const opcion  = selector.querySelector('.opcion-seleccionada');
    const lista   = selector.querySelector('.lista-opciones');

    opcion.addEventListener('click', () => {
        lista.classList.toggle('visible');
        opcion.classList.toggle('activa');
    });

    lista.querySelectorAll('div').forEach(item => {
        item.addEventListener('click', () => {
            opcion.textContent = item.textContent;
            selector.querySelector('input[type="hidden"]').value = item.textContent;
            lista.classList.remove('visible');
            opcion.classList.remove('activa');

            if (selector.id === 'selectorCantidad') {
                const cantidad = parseInt(item.textContent);
                if (!isNaN(cantidad)) generarCamposSerie(cantidad);
            }
        });
    });
});

document.addEventListener('click', e => {
    if (!e.target.closest('.selector-personalizado')) {
        document.querySelectorAll('.lista-opciones').forEach(l => l.classList.remove('visible'));
        document.querySelectorAll('.opcion-seleccionada').forEach(o => o.classList.remove('activa'));
    }
});

/* ===================== CAMPOS DE SERIE ===================== */
const contenedorSerie = document.getElementById('serieContainer');

function generarCamposSerie(cantidad) {
    contenedorSerie.innerHTML = '';
    for (let i = 1; i <= cantidad; i++) {
        const inp = document.createElement('input');
        inp.type        = 'text';
        inp.name        = 'id_producto[]';
        inp.placeholder = `Número de Serie ${i}`;
        inp.style.marginTop = '5px';
        contenedorSerie.appendChild(inp);
    }
}

/* ===================== MODALES PRINCIPALES ===================== */
const modalNuevoActivo    = document.getElementById('modalNuevoActivo');
const modalAsignarActivo  = document.getElementById('modalAsignarActivo');
const modalAgregarUsuario = document.getElementById('modalAgregarUsuario');
const modalVerUsuarios    = document.getElementById('modalVerUsuarios');
const modalCambioRol      = document.getElementById('modalCambioRol');

function abrirModal(modal) { if (modal) modal.classList.add('abierto'); }
function cerrarModal(modal) { if (modal) modal.classList.remove('abierto'); }

document.getElementById('btnNuevoActivo')?.addEventListener('click', e => {
    e.preventDefault(); abrirModal(modalNuevoActivo);
});
document.getElementById('cancelar')?.addEventListener('click', () => cerrarModal(modalNuevoActivo));

document.getElementById('btnAsignarActivo')?.addEventListener('click', e => {
    e.preventDefault(); abrirModal(modalAsignarActivo);
});
document.getElementById('cancelarAsignacion')?.addEventListener('click', () => cerrarModal(modalAsignarActivo));

document.getElementById('btnAgregarUsuario')?.addEventListener('click', e => {
    e.preventDefault(); abrirModal(modalAgregarUsuario);
});
document.getElementById('cancelarUsuario')?.addEventListener('click', () => cerrarModal(modalAgregarUsuario));

window.addEventListener('click', e => {
    [modalNuevoActivo, modalAsignarActivo, modalAgregarUsuario, modalVerUsuarios, modalCambioRol]
        .forEach(m => { if (e.target === m) cerrarModal(m); });
});

/* ===================== MODAL AVISO (solo usuario) ===================== */
const modalAviso  = document.getElementById('modalAviso');
const btnCerrarAv = document.getElementById('cerrarAviso');

if (modalAviso) {
    document.querySelectorAll('.bloqueado').forEach(btn => {
        btn.addEventListener('click', e => {
            e.preventDefault();
            modalAviso.style.display = 'flex';
        });
    });
    if (btnCerrarAv) {
        btnCerrarAv.addEventListener('click', () => { modalAviso.style.display = 'none'; });
    }
}

/* ===================== NUEVO ACTIVO (AJAX) ===================== */
const formNuevoActivo  = document.getElementById('formNuevoActivo');
const msgNuevoActivo   = document.getElementById('mensajeNuevoActivo');

document.getElementById('guardarNuevoActivo')?.addEventListener('click', () => {
    fetch('../servicios/activos.php', { method: 'POST', body: new FormData(formNuevoActivo) })
        .then(r => r.json())
        .then(datos => {
            msgNuevoActivo.style.color   = datos.exito ? 'green' : 'red';
            msgNuevoActivo.textContent   = datos.mensaje;
            if (datos.exito) {
                formNuevoActivo.reset();
                if (contenedorSerie) contenedorSerie.innerHTML = '';
            }
        })
        .catch(() => {
            msgNuevoActivo.style.color = 'red';
            msgNuevoActivo.textContent = 'Error de comunicación con el servidor';
        });
});

/* ===================== ASIGNAR ACTIVO (AJAX) ===================== */
const formAsignar   = document.getElementById('formAsignarActivo');
const msgAsignacion = document.getElementById('mensajeAsignacion');

document.getElementById('guardarAsignacion')?.addEventListener('click', () => {
    fetch('../servicios/sistema.php', { method: 'POST', body: new FormData(formAsignar) })
        .then(r => r.json())
        .then(datos => {
            if (msgAsignacion) {
                msgAsignacion.style.color = datos.exito ? 'green' : 'red';
                msgAsignacion.textContent = datos.exito ? 'Asignación realizada con éxito' : datos.mensaje;
            }
            if (datos.exito) formAsignar.reset();
        })
        .catch(() => {
            if (msgAsignacion) {
                msgAsignacion.style.color = 'red';
                msgAsignacion.textContent = 'Error al asignar el activo';
            }
        });
});

/* ===================== VER USUARIOS (solo admin) ===================== */
const btnVerUsuarios       = document.getElementById('btnVerUsuarios');
const btnCerrarVerUsuarios = document.getElementById('cerrarVerUsuarios');

if (btnVerUsuarios) {
    btnVerUsuarios.addEventListener('click', e => {
        e.preventDefault();
        abrirModal(modalVerUsuarios);
        cargarUsuariosSistema();
    });
}
if (btnCerrarVerUsuarios) {
    btnCerrarVerUsuarios.addEventListener('click', () => cerrarModal(modalVerUsuarios));
}

function cargarUsuariosSistema() {
    fetch('../servicios/sistema.php?tipo=verUsuarios')
        .then(r => r.json())
        .then(datos => {
            const tbody = document.querySelector('#tablaUsuarios tbody');
            tbody.innerHTML = '';
            datos.forEach(user => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${user.username}</td>
                    <td>${user.rol}</td>
                    <td>
                        <select class="selector-rol">
                            <option ${user.rol === 'admin' ? 'selected' : ''}>admin</option>
                            <option ${user.rol === 'user'  ? 'selected' : ''}>user</option>
                        </select>
                    </td>
                    <td><button class="boton-confirmar-rol" data-username="${user.username}">Confirmar</button></td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(err => console.error(err));
}

document.querySelector('#tablaUsuarios')?.addEventListener('click', e => {
    if (!e.target.classList.contains('boton-confirmar-rol')) return;
    const nombreUsuario = e.target.dataset.username;
    const nuevoRol      = e.target.closest('tr').querySelector('.selector-rol').value;
    if (modalCambioRol) {
        document.getElementById('cambioRolUsername').value  = nombreUsuario;
        document.getElementById('cambioRolNuevoRol').value  = nuevoRol;
        document.getElementById('mensajeCambioRol').textContent = '';
        document.getElementById('passwordAdminInput').value = '';
        abrirModal(modalCambioRol);
    }
});

/* ===================== CAMBIO DE ROL (solo admin) ===================== */
document.getElementById('confirmarCambioRol')?.addEventListener('click', () => {
    const nombreUsuario = document.getElementById('cambioRolUsername').value;
    const nuevoRol      = document.getElementById('cambioRolNuevoRol').value;
    const claveAdmin    = document.getElementById('passwordAdminInput').value;
    const msg           = document.getElementById('mensajeCambioRol');

    if (!claveAdmin) {
        msg.style.color = 'red';
        msg.textContent = 'Ingresa la contraseña de administrador';
        return;
    }

    const fd = new FormData();
    fd.append('username', nombreUsuario);
    fd.append('nuevoRol', nuevoRol);
    fd.append('passwordAdmin', claveAdmin);

    fetch('../servicios/sistema.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(datos => {
            if (datos.exito) {
                cerrarModal(modalCambioRol);
                cargarUsuariosSistema();
            } else {
                msg.style.color = 'red';
                msg.textContent = datos.mensaje;
            }
        })
        .catch(() => { msg.style.color = 'red'; msg.textContent = 'Error de comunicación'; });
});

document.getElementById('cancelarCambioRol')?.addEventListener('click', () => cerrarModal(modalCambioRol));

/* ===================== AGREGAR USUARIO (solo admin) ===================== */
const formAgregarUsuario   = document.getElementById('formAgregarUsuario');
const msgAgregarUsuario    = document.getElementById('mensajeAgregarUsuario');

document.getElementById('guardarUsuario')?.addEventListener('click', () => {
    fetch('../servicios/sistema.php', { method: 'POST', body: new FormData(formAgregarUsuario) })
        .then(r => r.json())
        .then(datos => {
            if (msgAgregarUsuario) {
                msgAgregarUsuario.style.color = datos.exito ? 'green' : 'red';
                msgAgregarUsuario.textContent = datos.exito ? 'Usuario agregado correctamente' : (datos.mensaje || 'Error al agregar usuario');
            }
            if (datos.exito) formAgregarUsuario.reset();
        })
        .catch(() => {
            if (msgAgregarUsuario) {
                msgAgregarUsuario.style.color = 'red';
                msgAgregarUsuario.textContent = 'Error al agregar usuario';
            }
        });
});

/* ===================== LOGS (solo admin) ===================== */
const btnLogs    = document.getElementById('btnLogs');
const modalLogs  = document.getElementById('modalLogs');
const cerrarLogs = document.getElementById('cerrarLogs');

if (btnLogs) {
    btnLogs.addEventListener('click', e => {
        e.preventDefault();
        abrirModal(modalLogs);
        cargarLogs();
    });
}
if (cerrarLogs) {
    cerrarLogs.addEventListener('click', () => cerrarModal(modalLogs));
}

function cargarLogs() {
    fetch('../servicios/sistema.php?tipo=logs')
        .then(r => r.json())
        .then(datos => {
            const tbody = document.querySelector('#tablaLogs tbody');
            tbody.innerHTML = '';
            datos.forEach(log => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${log.fecha}</td>
                    <td>${log.tabla}</td>
                    <td>${log.accion}</td>
                    <td>${log.registro_id}</td>
                    <td>${log.descripcion}</td>
                `;
                tbody.appendChild(tr);
            });
        })
        .catch(err => console.error(err));
}

/* ===================== SISTEMA DE DASHBOARDS ===================== */
let graficaActual   = null;
let indiceDash      = 0;

const tableros = [
    { titulo: 'ACTIVOS POR CATEGORÍA', renderizar: renderizarDonut       },
    { titulo: 'CONTADORES',            renderizar: renderizarContadores  },
    { titulo: 'POR UBICACIÓN',         renderizar: renderizarUbicaciones }
];

function destruirGrafica() {
    if (graficaActual) { graficaActual.destroy(); graficaActual = null; }
}

function alternarCanvas(mostrar) {
    document.getElementById('chartCategorias').style.display = mostrar ? 'block' : 'none';
    document.getElementById('counterDash').style.display     = mostrar ? 'none'  : 'flex';
}

function cambiarTablero(indice) {
    indiceDash = (indice + tableros.length) % tableros.length;
    document.getElementById('dashLabel').textContent = tableros[indiceDash].titulo;
    destruirGrafica();
    tableros[indiceDash].renderizar();
}

function renderizarDonut() {
    alternarCanvas(true);
    fetch('../servicios/sistema.php?tipo=categorias')
        .then(r => r.json())
        .then(datos => {
            graficaActual = new Chart(document.getElementById('chartCategorias'), {
                type: 'doughnut',
                data: {
                    labels: datos.map(d => d.categoria),
                    datasets: [{ data: datos.map(d => d.total),
                        backgroundColor: VERDES.slice(0, datos.length),
                        borderWidth: 2, borderColor: '#1f3b1f' }]
                },
                options: {
                    plugins: {
                        legend: { position: 'bottom', labels: { color: '#fff', font: { size: 11 }, boxWidth: 12 } },
                        tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` } }
                    },
                    cutout: '65%',
                    animation: { animateRotate: true, duration: 900 }
                }
            });
        }).catch(() => {});
}

function renderizarContadores() {
    alternarCanvas(false);
    fetch('../servicios/sistema.php?tipo=stats')
        .then(r => r.json())
        .then(datos => {
            animarContador('cntActivos',   datos.activos   || 0, 1000);
            if (document.getElementById('cntUsuarios')) {
                animarContador('cntUsuarios',  datos.usuarios  || 0, 1000);
            }
            animarContador('cntPrestamos', datos.prestamos || 0, 1000);
        }).catch(() => {});
}

function renderizarUbicaciones() {
    alternarCanvas(true);
    fetch('../servicios/sistema.php?tipo=ubicaciones')
        .then(r => r.json())
        .then(datos => {
            if (!datos.length) { alternarCanvas(false); return; }
            graficaActual = new Chart(document.getElementById('chartCategorias'), {
                type: 'bar',
                data: {
                    labels: datos.map(d => d.ubicacion),
                    datasets: [{ data: datos.map(d => d.total),
                        backgroundColor: VERDES,
                        borderWidth: 0, borderRadius: 6 }]
                },
                options: {
                    indexAxis: 'y',
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: ctx => ` ${ctx.parsed.x} usuarios` } }
                    },
                    scales: {
                        x: { ticks: { color: '#fff', font: { size: 10 } }, grid: { color: 'rgba(255,255,255,0.1)' } },
                        y: { ticks: { color: '#fff', font: { size: 9 }, maxRotation: 0 }, grid: { display: false } }
                    },
                    animation: { duration: 800 }
                }
            });
        }).catch(() => {});
}

document.getElementById('dashPrev').addEventListener('click', () => cambiarTablero(indiceDash - 1));
document.getElementById('dashNext').addEventListener('click', () => cambiarTablero(indiceDash + 1));

cambiarTablero(0);
