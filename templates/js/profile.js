(() => {
  const API_SAVE = '../api/index.php/user/profile';
  const $ = id => document.getElementById(id);

  document.addEventListener('DOMContentLoaded', () => {
    const form = /** @type {HTMLFormElement} */ (document.getElementById('profileForm'));
    const inputOldPwd = /** @type {HTMLInputElement} */ (form.elements.namedItem('old_password'));
    const inputNewPwd = /** @type {HTMLInputElement} */ (form.elements.namedItem('password'));

    form.addEventListener('submit', async e => {
      e.preventDefault();

      if (!inputOldPwd.value.trim()) {
        alert('Ievadiet savu pašreizējo paroli!');
        return;
      }

      try {
        const res = await fetch(API_SAVE, {
          method: 'POST',
          body: new FormData(form),
          credentials: 'include'
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Neizdevās saglabāt datus');

        inputOldPwd.value = '';
        inputNewPwd.value = '';

        alert('Dati saglabāti!');
      } catch (err) {
        alert(err.message);
        console.error(err);
      }
    });
  });
})();
