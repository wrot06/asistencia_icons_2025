const tipoDoc = document.getElementById('tipo_documento');
const numeroDoc = document.getElementById('numero_documento');

if (tipoDoc && numeroDoc) {
  tipoDoc.addEventListener('change', () => {
    const tipo = tipoDoc.value;

    // Reiniciar campo
    numeroDoc.value = '';
    numeroDoc.removeAttribute('pattern');
    numeroDoc.disabled = false;

    if (tipo === '') {
      numeroDoc.placeholder = 'Seleccione tipo de documento';
      numeroDoc.disabled = true;
      return;
    }

    if (tipo === 'CC' || tipo === 'TI') {
      numeroDoc.placeholder = `Ingrese número de ${tipo}`;
      numeroDoc.setAttribute('pattern', '^[0-9]{5,12}$');
      numeroDoc.setAttribute('title', 'Solo números (5 a 12 dígitos)');
      numeroDoc.addEventListener('input', soloNumeros);
    } else if (tipo === 'CE' || tipo === 'Pasaporte') {
      numeroDoc.placeholder = `Ingrese número de ${tipo}`;
      numeroDoc.setAttribute('pattern', '^[A-Za-z0-9-]{4,20}$');
      numeroDoc.setAttribute('title', 'Letras, números o guiones (4 a 20 caracteres)');
      numeroDoc.removeEventListener('input', soloNumeros);
    }
  });
}

function soloNumeros(e) {
  e.target.value = e.target.value.replace(/\D/g, '');
}
