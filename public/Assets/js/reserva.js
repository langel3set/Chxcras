// reserva.js

// ––––– Variables globales –––––
let currentStep   = 'main-options';
let selectedDate  = null;
let selectedTime  = null;
let currentMonth  = new Date().getMonth();
let currentYear   = new Date().getFullYear();
let calendarOpen  = false;

// Meses en español
const months = [
    'Enero','Febrero','Marzo','Abril','Mayo','Junio',
    'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
];

// Slots cada 30m de 08:00 a 22:30
const timeSlots = Array.from({ length: ((21 - 8) * 2) }, (_, i) => {
    const h = 8 + Math.floor(i / 2);
    const m = (i % 2) * 30;
    return `${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
});

// ——— Helpers de UI ———
function showStep(stepId) {
    document.querySelectorAll('.step-block').forEach(el => el.style.display = 'none');
    document.getElementById('progress-bar').style.display = (stepId !== 'main-options' ? 'block' : 'none');
    const block = document.getElementById(stepId);
    if (block) block.style.display = 'block';
    currentStep = stepId;
    updateProgressIndicator(stepId);
    scrollToTop();
}

function updateProgressIndicator(stepId) {
    const raw = stepId.replace(/^step-/, '');
    const steps = Array.from(document.querySelectorAll('.progress-step'));
    const idx   = steps.findIndex(el => el.dataset.step === raw);
    const pct   = idx >= 0 ? ((idx + 1) / steps.length) * 100 : 0;
    const line  = document.getElementById('progress-line');
    if (line) line.style.setProperty('--progress-fill', pct + '%');
    steps.forEach((el, i) => {
        el.classList.toggle('active',    i === idx);
        el.classList.toggle('completed', i < idx);
    });
}

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ——— Pasos ———
function showRealizarReserva() { showStep('step-tipo'); }
function showVerReservas()      { showStep('ver-reservas'); }
function goBackToMain()         { showStep('main-options'); }

function toStep(stepId) {
    // Ocultar todos los pasos
    document.querySelectorAll('.step-block').forEach(el => el.style.display = 'none');

    // Mostrar el paso seleccionado
    const step = document.getElementById('step-' + stepId);
    if (step) step.style.display = 'block';

    // Si el paso es "resumen", actualiza la vista con los datos del usuario
    if (stepId === 'resumen') {
        updateSummary();
    }

    // Si el paso es "pago", genera automáticamente el código de reserva
    if (stepId === 'pago') {
        const spanCodigo = document.getElementById('codigo-reserva');
        if (spanCodigo) {
            const codigo = generarCodigoReserva();
            spanCodigo.textContent = codigo;
        }
    }

    // Si el paso es "mis-reservas", cargar las reservas del usuario
    if (stepId === 'mis-reservas') {
        cargarMisReservas();
    }
}
async function cargarMisReservas() {

    const correo = localStorage.getItem("correo_usuario");
    if (!correo) {
        console.warn("No hay correo en localStorage");
        return;
    }

    try {
        const res = await fetch(`${BASE_URL}reservas?correo=${encodeURIComponent(correo)}`);
        const data = await res.json();

        const cont = document.getElementById("lista-mis-reservas");
        if (!cont) {
            console.error("No existe el contenedor lista-mis-reservas en el HTML");
            return;
        }

        cont.innerHTML = "";

        if (!data.success || data.reservas.length === 0) {
            cont.innerHTML = `<p>No tienes reservas registradas.</p>`;
            return;
        }

        data.reservas.forEach(r => {
            cont.innerHTML += `
                <div class="reserva-card">
                    <h3><strong>Código:</strong> ${r.codigo_reserva}</h3>
                    <p><strong>Titular:</strong> ${r.titular}</p>
                    <p><strong>Personas:</strong> ${r.num_personas}</p>

                    <p><strong>Fecha:</strong> ${r.fecha}</p>
                    <p><strong>Hora:</strong> ${r.hora}</p>

                    <p><strong>Celebración:</strong> ${r.celebracion ?? '—'}</p>
                    <p><strong>Alergias:</strong> ${r.alergias ?? '—'}</p>

                    <p><strong>Requerimientos:</strong> ${r.requerimientos_adicionales ?? '—'}</p>

                    <p><strong>Estado:</strong> ${r.estado}</p>
                    <p><strong>Monto:</strong> S/ ${r.monto}</p>

                    <small>Registrado el: ${r.fecha_creacion}</small>
                </div>
                <hr>
            `;
        });

    } catch (error) {
        console.error("Error al cargar mis reservas:", error);
    }
}
// ——— Calendario ———
function toggleCalendar() {
    nextMonth();
    previousMonth();
    
    fetchUnavailableSlots(new Date());
    calendarOpen = !calendarOpen;
    document.getElementById('calendar-popup').classList.toggle('active', calendarOpen);
    
}

function previousMonth() {
    currentMonth = currentMonth === 0 ? 11 : currentMonth - 1;
    if (currentMonth === 11) currentYear--;
    generateCalendar();
}

function nextMonth() {
    currentMonth = currentMonth === 11 ? 0 : currentMonth + 1;
    if (currentMonth === 0) currentYear++;
    generateCalendar();
}

function generateCalendar() {

    const grid  = document.getElementById('calendar-grid');
    const title = document.getElementById('current-month');
    if (!grid || !title) return;

    grid.innerHTML = '';
    title.textContent = `${months[currentMonth]} ${currentYear}`;

    const fullyBooked = window.FULLY_BOOKED_DATES || [];
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const todayTS = new Date().setHours(0, 0, 0, 0);

    // Espacios vacíos al inicio del mes
    for (let i = 0; i < firstDay; i++) {
        const d = document.createElement('div');
        d.className = 'calendar-day disabled';
        grid.appendChild(d);
    }

    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

    for (let d = 1; d <= daysInMonth; d++) {
        const cell = document.createElement('div');
        cell.className = 'calendar-day';
        cell.textContent = d;

        const thisDate = new Date(currentYear, currentMonth, d);
        const iso = thisDate.toISOString().split("T")[0];

        // Día completo desde PHP (10 reservas)
        if (fullyBooked.includes(iso)) {
            cell.classList.add("full-day", "disabled");
        }

        // Día pasado
        if (thisDate.getTime() < todayTS) {
            cell.classList.add("disabled");
        }

        if (cell.classList.contains("disabled")) {
            cell.addEventListener("click", e => e.stopPropagation());
        } else {
            cell.addEventListener('click', () => selectDate(thisDate));

            if (selectedDate && thisDate.toDateString() === selectedDate.toDateString()) {
                cell.classList.add('selected');
            }
        }

        // Marcar hoy
        if (thisDate.toDateString() === new Date().toDateString()) {
            cell.classList.add('today');
        }

        grid.appendChild(cell);
    }
}

function selectDate(dt) {
    const iso = dt.toISOString().split("T")[0];

    if (window.FULLY_BOOKED_DATES.includes(iso)) {
        alert("⚠️ Esta fecha está completamente ocupada.");
        return;
    }

    selectedDate = dt;

    document.getElementById('selected-date').textContent = dt.toLocaleDateString('es-ES', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
    });

    toggleCalendar();
    clearFieldError('date-input');

    fetchUnavailableSlots(dt);
}


function fetchUnavailableSlots(dateObj) {
    const iso = dateObj.toISOString().split('T')[0];
    const url = `${window.BASE_URL}reservas/disponibilidad?fecha=${iso}`;

    console.log('👉 Disponibilidad URL:', url);

    fetch(url)
        .then(r => r.ok ? r.json() : Promise.reject(`Status ${r.status}`))
        .then(data => {
            console.log("📌 Respuesta disponibilidad:", data);

            // Actualizar las fechas completas desde backend
            window.FULLY_BOOKED_DATES = data.diasCompletos || [];

            // Regenerar calendario con los nuevos días completos
            generateCalendar();

            // Horas no disponibles → generar slots
            generateTimeSlots(data.horasNoDisponibles || []);
        })
        .catch(err => {
            console.warn('⚠ fetchUnavailableSlots error:', err);
            generateTimeSlots([]);
        });
}


function generateTimeSlots(unavailable = []) {
    const cont = document.getElementById('time-selector');
    cont.innerHTML = '';

    timeSlots.forEach(t => {
        const slot = document.createElement('div');
        slot.className = 'time-slot';
        slot.textContent = t;

        if (unavailable.includes(t)) {
            slot.classList.add('disabled');
        } else {
            slot.addEventListener('click', () => selectTime(t, slot));
        }

        cont.appendChild(slot);
    });
}

function selectTime(t, el) {
    selectedTime = t;
    document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
    el.classList.add('selected');
    clearFieldError('time-selector');
}

// ——— Personas ———
function increasePersonas() {
    const inp = document.getElementById('personas');
    const v   = parseInt(inp.value) || 1;
    if (v < parseInt(inp.max)) inp.value = v + 1;
}
function decreasePersonas() {
    const inp = document.getElementById('personas');
    const v   = parseInt(inp.value) || 1;
    if (v > parseInt(inp.min)) inp.value = v - 1;
}

// ——— Validación ———
function validateAndContinue() {
    const errs = [];
    if (!selectedDate)    errs.push({ field: 'date-input',    message: 'Selecciona una fecha' });
    if (!selectedTime)    errs.push({ field: 'time-selector', message: 'Selecciona una hora' });
    const p = document.getElementById('personas').value;
   if (!p || isNaN(p) || parseInt(p) < 1 || parseInt(p) > 8) {
    errs.push({ 
        field: 'personas', 
        message: 'El número de personas debe ser entre 1 y 8' 
    });
}
    const tit = document.getElementById('titular').value.trim();
    if (!tit) errs.push({ field: 'titular', message: 'Ingresa el nombre del titular' });

    if (errs.length) {
        showErrors(errs);
        scrollToTop();
    } else {
        hideErrors();
        toStep('preferencias');
    }
}

function showErrors(errs) {
    const ec = document.getElementById('error-messages');
    ec.innerHTML = '';
    errs.forEach(e => {
        const d = document.createElement('div');
        d.className = 'error-message';
        d.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${e.message}`;
        ec.appendChild(d);
        if (e.field) {
            const fg = document.getElementById(e.field).closest('.form-group');
            fg && fg.classList.add('error');
        }
    });
    ec.style.display = 'block';
}

function hideErrors() {
    document.getElementById('error-messages').style.display = 'none';
    document.querySelectorAll('.form-group').forEach(g => g.classList.remove('error'));
}

function clearFieldError(id) {
    const grp = document.getElementById(id).closest('.form-group');
    grp && grp.classList.remove('error');
}

function updateSummary() {
    const cont = document.querySelector('.resumen-card');
    cont.innerHTML = '';

    const personas = document.getElementById('personas').value;
    const titular  = document.getElementById('titular').value;
    const fecha    = selectedDate 
        ? selectedDate.toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' }) 
        : 'No seleccionada';
    const hora     = selectedTime || 'No seleccionada';
    const alergias = document.getElementById('alergias').value || 'Ninguna';
    const celebracion = document.getElementById('celebracion').value || 'Ninguna';
    const req = document.getElementById('requerimientos_adicionales').value || 'Ninguno';

    const monto = (50 * parseInt(personas || 0)).toFixed(2);

    // Mostrar resumen en HTML
    cont.innerHTML = `
        <ul>
            <li><strong>Titular:</strong> ${titular}</li>
            <li><strong>Personas:</strong> ${personas}</li>
            <li><strong>Fecha:</strong> ${fecha}</li>
            <li><strong>Hora:</strong> ${hora}</li>
            <li><strong>Alergias:</strong> ${alergias}</li>
            <li><strong>Celebración:</strong> ${celebracion}</li>
            <li><strong>Requerimientos:</strong> ${req}</li>
        </ul>
    `;

    document.getElementById('precio-estimado').innerText = monto;
}



// ——— Función para generar el código de reserva ———
function generarCodigoReserva() {
    const fecha = new Date();
    const año = fecha.getFullYear();
    const mes = String(fecha.getMonth() + 1).padStart(2, '0');
    const dia = String(fecha.getDate()).padStart(2, '0');
    const letras = Math.random().toString(36).substring(2, 4).toUpperCase();
    const numeros = Math.floor(1000 + Math.random() * 9000);
    return `RES-${año}${mes}${dia}-${letras}${numeros}`;
}

async function buscarReserva() {
  const codigo = document.getElementById('codigo-busqueda').value.trim();
  const resultado = document.getElementById('resultado-busqueda');

  resultado.innerHTML = ''; // limpiar

  if (!codigo) {
    resultado.innerHTML = '<p style="color:red;">Por favor, ingresa un código.</p>';
    return;
  }

  try {
    const res = await fetch(`${BASE_URL}reservas/buscar?codigo=${encodeURIComponent(codigo)}`, {
      method: 'GET',
      headers: { 'Accept': 'application/json' }
    });

    if (res.status === 404) {
      resultado.innerHTML = '<p style="color:red;">❌ No se encontró ninguna reserva con ese código.</p>';
      return;
    }

    if (!res.ok) {
      throw new Error('Error en la petición: ' + res.status);
    }

    const payload = await res.json();

    if (payload.status === 'ok' && payload.reserva) {
      const r = payload.reserva;
      resultado.innerHTML = `
        <div class="busqueda-reserva">
          <p style="color:green;">✅ Reserva encontrada</p>
          <p><strong>Código:</strong> ${r.codigo_reserva}</p>
          <p><strong>Titular:</strong> ${r.titular}</p>
          <p><strong>Fecha:</strong> ${r.fecha} ${r.hora || ''}</p>
          <p><strong>Personas:</strong> ${r.num_personas}</p>
          <p><strong>Estado:</strong> ${r.estado}</p>
        </div>
      `;
    } else {
      resultado.innerHTML = '<p style="color:red;">❌ No se encontró ninguna reserva con ese código.</p>';
    }
  } catch (err) {
    console.error('buscarReserva error:', err);
    resultado.innerHTML = '<p style="color:red;">❌ Error al buscar la reserva. Revisa la consola.</p>';
  }
}

async function verificarSesionYBuscar() {
    try {
        const response = await fetch(`${BASE_URL}usuarios/verificarSesion`);
        const data = await response.json();

        if (data.status === 'ok') {
            // Usuario logueado → permite buscar
            toStep('buscar');
        } else {
            // No logueado → redirigir al login
            Swal.fire({
                title: 'Inicia sesión',
                text: 'Debes iniciar sesión para buscar una reserva.',
                icon: 'warning',
                confirmButtonText: 'Ir al login'
            }).then(() => {
                window.location.href = `${BASE_URL}login`;
            });
        }
    } catch (error) {
        console.error('Error al verificar sesión:', error);
        Swal.fire('Error', 'No se pudo verificar la sesión.', 'error');
    }
}

async function verificarSesionYMisReservas() {
    try {
        const response = await fetch(`${BASE_URL}usuarios/verificarSesion`);
        const data = await response.json();
        const correo = localStorage.getItem("correo_usuario");

        if (!correo) {
            console.warn("⚠️ No hay correo en localStorage, mandando al login...");
            window.location.href = BASE_URL + "login";
            return;
        }
        if (data.status === 'ok') {
            // Usuario logueado → dirigir a mis reservas
            toStep('mis-reservas');  // Cambia esto si tu vista se llama diferente
        } else {
            // No logueado → mostrar aviso y redirigir a login
            Swal.fire({
                title: 'Inicia sesión',
                text: 'Debes iniciar sesión para ver tus reservas.',
                icon: 'warning',
                confirmButtonText: 'Ir al login'
            }).then(() => {
                window.location.href = `${BASE_URL}login`;
            });
        }
    } catch (error) {
        console.error('Error al verificar sesión:', error);
        Swal.fire('Error', 'No se pudo verificar la sesión.', 'error');
    }
}

function validarPagoSimulado() {
  const email = document.getElementById('email-simulado').value.trim();
  const tarjeta = document.getElementById('fake-card').value.trim();
  const nombre = document.getElementById('fake-name').value.trim();
  let exp = document.getElementById('fake-exp').value.trim();
  const cvv = document.getElementById('fake-cvv').value.trim();

  // Elimina espacios ocultos

  exp = exp.replace(/\s+/g, "");

  // Validación de correo
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    alert("⚠️ Ingresa un correo electrónico válido.");
    return false;
  }

  // Validación número de tarjeta (formato simple)
  const cardRegex = /^(\d{4}[-\s]?){3}\d{4}$/;
  if (!cardRegex.test(tarjeta)) {
    alert("⚠️ Ingresa un número de tarjeta válido (16 dígitos).");
    return false;
  }

  // Validación nombre
  if (nombre.length < 3) {
    alert("⚠️ Ingresa el nombre del titular de la tarjeta.");
    return false;
  }

  // Validación fecha de expiración (MM/YY)
  
  const expRegex = /^(0[1-9]|1[0-2])\/\d{2}$/;
  if (!expRegex.test(exp)) {
    alert("⚠️ La fecha de expiración debe tener el formato MM/YY.");
    console.log("VALOR EXP:", JSON.stringify(exp));
    return false;
  }

  // Validar que no esté vencida
  const [mes, año] = exp.split('/');
  const fechaActual = new Date();
  const añoActual = parseInt(fechaActual.getFullYear().toString().slice(-2));
  const mesActual = fechaActual.getMonth() + 1;

  if (parseInt(año) < añoActual || (parseInt(año) === añoActual && parseInt(mes) < mesActual)) {
    alert("⚠️ La tarjeta está vencida.");
    return false;
  }

  // Validación CVV
  if (!/^\d{3,4}$/.test(cvv)) {
    alert("⚠️ Ingresa un código CVV válido (3 o 4 dígitos).");
    return false;
  }

  return true;
}

async function guardarReserva() {
  try {
    if (selectedDate) {
      document.getElementById('fecha').value = selectedDate.toISOString().split('T')[0];
    }
    if (selectedTime) {
      document.getElementById('hora').value = selectedTime;
    }
    const correoGuardado = localStorage.getItem("correo_usuario");
    const datos = {
        fecha: document.getElementById('fecha').value,
        hora: document.getElementById('hora').value,
        num_personas: document.getElementById('personas').value,
        titular: document.getElementById('titular').value,
        alergias: document.getElementById('alergias').value,
        celebracion: document.getElementById('celebracion').value,
        requerimientos_adicionales: document.getElementById('requerimientos_adicionales').value,
        estado: "pendiente",
        monto: document.getElementById('precio-estimado').innerText || "0.00",
        correo: correoGuardado ? correoGuardado : document.getElementById('email-simulado').value
    };

    const response = await fetch(`${BASE_URL}reservas/guardar`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(datos)
    });

    if (!response.ok) throw new Error(`Error HTTP: ${response.status}`);
    const result = await response.json();

    if (result.status === "ok") {
      alert(`✅ Reserva guardada correctamente.\nCódigo: ${result.codigo_reserva}`);
      toStep('pago');
      document.getElementById("codigo-reserva").innerText = result.codigo_reserva;
    } else {
      alert("⚠️ Error al guardar la reserva: " + result.message);
    }

  } catch (error) {
    console.error("Error al guardar la reserva:", error);
    alert("❌ Error inesperado al guardar la reserva.");
  }
}
async function fetchFullyBookedDays() {
    try {
        const res = await fetch(`${window.BASE_URL}reservas/disponibilidad`);
        const data = await res.json();

        if (data.success && Array.isArray(data.fechas)) {
            window.FULLY_BOOKED_DATES = data.fechas; 
        } else {
            window.FULLY_BOOKED_DATES = [];
        }

        // Refrescar calendario con las fechas bloqueadas
        generateCalendar();
    } catch (err) {
        console.warn("⚠ Error obteniendo fechas llenas:", err);
        window.FULLY_BOOKED_DATES = [];
        generateCalendar();
    }
}
// ——— Bootstrapping ———
document.addEventListener('DOMContentLoaded', async () => {
    showStep('main-options');
    // Cerrar calendario al hacer clic fuera
    document.addEventListener('click', e => {
        const pop = document.getElementById('calendar-popup');
        const inp = document.getElementById('date-input');
        if (calendarOpen && pop && !pop.contains(e.target) && !inp.contains(e.target)) {
            toggleCalendar();
        }
    });
});

