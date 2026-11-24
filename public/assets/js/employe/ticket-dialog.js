document.addEventListener('DOMContentLoaded', () => {
    const dialogue = document.getElementById('maintenance-ticket-dialog');
    if (!dialogue) return;

    const urlListe = dialogue.dataset.listUrl;
    if (typeof dialogue.showModal === 'function') {
        dialogue.showModal();
    } else {
        dialogue.setAttribute('open', '');
    }

    dialogue.addEventListener('cancel', evenement => {
        evenement.preventDefault();
        window.location.assign(urlListe);
    });

    dialogue.addEventListener('click', evenement => {
        if (evenement.target === dialogue) window.location.assign(urlListe);
    });
});
