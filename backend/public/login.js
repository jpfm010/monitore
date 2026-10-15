(() => {
  const form = document.getElementById('loginForm');
  const button = document.getElementById('submitButton');
  const errorBox = document.getElementById('loginError');
  const showError = (message) => { errorBox.textContent = message; errorBox.style.display = 'block'; };
  fetch('api.php?action=me', {credentials:'same-origin',headers:{'Accept':'application/json'}})
    .then(response => { if (response.ok) window.location.replace('index.html'); })
    .catch(() => {});
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    errorBox.style.display = 'none';
    button.disabled = true;
    button.textContent = 'Verificando...';
    try {
      const response = await fetch('api.php?action=login', {
        method:'POST', credentials:'same-origin',
        headers:{'Content-Type':'application/json','Accept':'application/json'},
        body:JSON.stringify({
          email:document.getElementById('email').value.trim(),
          password:document.getElementById('password').value
        })
      });
      const data = await response.json();
      if (!response.ok) throw new Error(data.error || 'Não foi possível entrar.');
      window.location.replace('index.html');
    } catch (error) {
      showError(error.message || 'Falha de conexão. Tente novamente.');
      button.disabled = false;
      button.textContent = 'Entrar com segurança';
    }
  });
})();
