document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('[data-profil-employe]');
    if (!page) return;

    const { api, csrf } = page.dataset;
    const form = document.getElementById('profileForm');
    const message = document.getElementById('profileMessage');
    const champTelephone = form.querySelector('[name="telephone"]');

    const texte = (id, valeur) => {
        const element = document.getElementById(id);
        if (element) element.textContent = valeur;
    };
    const fixer = (element, valeur) => { element.value = valeur; element.defaultValue = valeur; };
    const urlAction = action => {
        const adresse = new URL(api, window.location.href);
        adresse.searchParams.set('action', action);
        return adresse.toString();
    };

    /** Appel JSON vers le contrôleur (?action=<action>). */
    async function appeler(action, donnees) {
        const formulaire = new FormData();
        Object.entries(donnees).forEach(([cle, valeur]) => formulaire.set(cle, valeur));
        formulaire.set('csrf', csrf);
        const reponse = await fetch(urlAction(action), { method: 'POST', body: formulaire });
        const resultat = await reponse.json();
        if (!reponse.ok || !resultat.success) throw new Error(resultat.message || 'La demande a échoué.');
        return resultat;
    }

    /* ----- Téléphone : saisie en groupes de 2 chiffres ----- */
    if (champTelephone) {
        const chiffres = valeur => valeur.replace(/\D/g, '').slice(0, 10);
        const formater = valeur => chiffres(valeur).match(/.{1,2}/g)?.join(' ') || '';
        const initiaux = champTelephone.value.replace(/\D/g, '');
        if (initiaux.length <= 10) champTelephone.value = formater(initiaux);

        champTelephone.addEventListener('input', () => {
            const avantCurseur = champTelephone.value
                .slice(0, champTelephone.selectionStart ?? champTelephone.value.length)
                .replace(/\D/g, '').length;
            champTelephone.value = formater(champTelephone.value);
            let curseur = 0;
            let comptes = 0;
            while (curseur < champTelephone.value.length && comptes < avantCurseur) {
                if (/\d/.test(champTelephone.value[curseur])) comptes++;
                curseur++;
            }
            champTelephone.setSelectionRange(curseur, curseur);
        });
    }

    /* ----- Photo de profil ----- */
    document.getElementById('photoUpload')?.addEventListener('change', async evenement => {
        const champFichier = evenement.currentTarget;
        const fichier = champFichier.files?.[0];
        if (!fichier) return;

        const donnees = new FormData(document.getElementById('photoForm'));
        message.textContent = 'Préparation de la photo…';

        try {
            const tailleMax = Number(page.dataset.maxPhotoBytes || 0);
            const photo = await window.compresserPhotoProfil(fichier, tailleMax);
            donnees.set('photo', photo, photo.name);
            message.textContent = 'Enregistrement de la photo…';
            const reponse = await fetch(urlAction('profile_photo'), { method: 'POST', body: donnees });
            let resultat;
            try {
                resultat = JSON.parse(await reponse.text());
            } catch {
                throw new Error('Le serveur a refusé la photo. Vérifiez la taille maximale autorisée et réessayez.');
            }
            if (!reponse.ok || !resultat.success) throw new Error(resultat.message || 'La photo n’a pas pu être enregistrée.');

            const avatar = document.getElementById('profileAvatar');
            avatar.src = `${resultat.photo_url}?v=${Date.now()}`;
            avatar.hidden = false;
            document.getElementById('profileAvatarFallback').hidden = true;
            message.textContent = 'Photo de profil enregistrée.';
        } catch (erreur) {
            message.textContent = erreur instanceof Error ? erreur.message : 'La photo n’a pas pu être enregistrée.';
        } finally {
            champFichier.value = '';
        }
    });

    /* Enregistrement direct des champs, comme dans l’espace adhérent. */
    form.addEventListener('submit', async evenement => {
        evenement.preventDefault();
        const boutonEnregistrer = document.querySelector('.employee-profile-save');
        boutonEnregistrer.disabled = true;

        try {
            const { profile } = await appeler('profile', Object.fromEntries(new FormData(form).entries()));
            const nomComplet = `${profile.prenom} ${profile.nom}`;
            const lieu = [profile.nom_commune, profile.code_postal].filter(Boolean).join(' ') || 'Lieu non renseigné';

            texte('employeeProfileName', nomComplet);
            texte('employeeProfileFullName', nomComplet);
            texte('profileContactEmail', profile.email);
            texte('employeeProfileEmail', profile.email);
            texte('profileContactPhone', profile.telephone || 'Téléphone non renseigné');
            texte('employeeProfilePhone', profile.telephone || 'Non renseigné');
            texte('employeeProfileLocation', lieu);
            texte('employeeProfileAddress', lieu);

            fixer(form.querySelector('[name="nom"]'), profile.nom);
            fixer(form.querySelector('[name="prenom"]'), profile.prenom);
            fixer(form.querySelector('[name="email"]'), profile.email);
            fixer(champTelephone, profile.telephone || '');
            fixer(document.getElementById('commune'), profile.nom_commune);
            fixer(document.getElementById('id_commune'), profile.id_commune);
            fixer(document.getElementById('code_postal'), profile.code_postal);

            message.textContent = 'Informations enregistrées.';
        } catch (erreur) {
            message.textContent = erreur.message;
        } finally {
            boutonEnregistrer.disabled = false;
        }
    });
});
