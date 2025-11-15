let ordenActual = 1;

// ==================== FUNCIONES PRINCIPALES ====================

function sumar(id, categoriaId) {
    const inputCantidad = document.getElementById('plato-' + id);
    const count = document.getElementById('count-' + id);
    const ordenInput = document.getElementById('orden-' + id);
    const ordenBadge = document.getElementById('orden-badge-' + id);

    let cantidad = parseInt(inputCantidad.value);
    cantidad++;
    inputCantidad.value = cantidad;
    count.innerText = cantidad;

    if (cantidad === 1) {
        ordenInput.value = ordenActual;
        ordenBadge.innerText = ordenActual++;
    }

    actualizarContadorCategoria(categoriaId);
}

function restar(id, categoriaId) {
    const inputCantidad = document.getElementById('plato-' + id);
    const count = document.getElementById('count-' + id);
    const ordenInput = document.getElementById('orden-' + id);
    const ordenBadge = document.getElementById('orden-badge-' + id);

    let cantidad = parseInt(inputCantidad.value);

    if (cantidad > 0) {
        cantidad--;
        inputCantidad.value = cantidad;
        count.innerText = cantidad;

        if (cantidad === 0) {
            ordenInput.value = 0;
            ordenBadge.innerText = '-';
        }

        actualizarContadorCategoria(categoriaId);
    }
}

function actualizarContadorCategoria(categoriaId) {
    let totalCat = 0;
    document.querySelectorAll(`input[data-categoria="${categoriaId}"]`).forEach(inp => {
        totalCat += parseInt(inp.value);
    });
    const contador = document.getElementById('contador-' + categoriaId);
    if (contador) contador.innerText = totalCat;
}

// ==================== VALIDACIÓN FORMULARIO ====================

function validarFormulario() {
    const mesa = document.querySelector('select[name="mesa"]').value;
    if (!mesa) {
        alert("Por favor, selecciona una mesa.");
        return false;
    }

    let total = 0;
    document.querySelectorAll('input[name^="plato["]').forEach(p => {
        total += parseInt(p.value);
    });

    if (total === 0) {
        alert("Debes seleccionar al menos un plato.");
        return false;
    }

    return true;
}

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('.plato-card').forEach(card => {
        card.addEventListener('mousedown', () => card.style.transform = 'scale(0.97)');
        card.addEventListener('mouseup', () => card.style.transform = 'scale(1.06)');
        card.addEventListener('mouseleave', () => card.style.transform = 'scale(1)');
    });

    document.querySelectorAll('.tarjeta-orden').forEach(tarjeta => {
        tarjeta.addEventListener('click', async function(e) {
            if (e.target.closest('button')) return;

            const id_mesa = this.dataset.id;
            const estado_actual = this.dataset.estado;

            try {
                const resp = await fetch("<?= BASE_URL ?>mesero/cambiarEstadoMesaAjax", {
                    method: "POST",
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `id_mesa=${id_mesa}&estado=${estado_actual}`
                });

                const res = await resp.json();

                if (res.success) {
                    alert(res.message);
                    this.dataset.estado = (estado_actual === 'libre') ? 'ocupada' : 'libre';
                    this.classList.toggle('ocupada');
                    this.classList.toggle('disponible');

                    const badge = this.querySelector('.badge-estado');
                    badge.textContent = (this.dataset.estado === 'ocupada') ? 'En prep.' : 'Libre';
                    badge.className = 'badge-estado ' + ((this.dataset.estado === 'ocupada') ? 'badge-prep' : 'badge-libre');
                } else {
                    alert(res.message);
                }
            } catch (err) {
                alert("Error al cambiar el estado de la mesa.");
                console.error(err);
            }
        });
    });
});

window.addEventListener("load", () => {
    window.sumar = sumar;
    window.restar = restar;
    window.validarFormulario = validarFormulario;
});