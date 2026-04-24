function animarContador(id, objetivo, duracion = 1000) {
    const el = document.getElementById(id);
    if (!el) return;
    if (objetivo <= 0) { el.textContent = 0; return; }
    const pasos = Math.min(objetivo, 60);
    const incremento = objetivo / pasos;
    const intervalo  = duracion / pasos;
    let cuenta = 0;
    const temporizador = setInterval(() => {
        cuenta++;
        el.textContent = Math.min(Math.round(incremento * cuenta), objetivo);
        if (cuenta >= pasos) clearInterval(temporizador);
    }, intervalo);
}
