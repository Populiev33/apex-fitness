/** Réduit une photo côté navigateur pour respecter la limite d’envoi PHP. */
window.compresserPhotoProfil = async function (fichier, tailleMax) {
    if (!fichier.type.startsWith('image/')) {
        throw new Error('Choisissez une image au format JPG, PNG ou WEBP.');
    }
    if (fichier.size <= tailleMax) return fichier;
    if (typeof createImageBitmap !== 'function') {
        throw new Error('Cette photo est trop lourde pour le serveur. Choisissez une image plus petite.');
    }

    const image = await createImageBitmap(fichier);
    try {
        let echelle = Math.min(1, 1800 / Math.max(image.width, image.height));
        for (let essai = 0; essai < 7; essai++) {
            const canvas = document.createElement('canvas');
            canvas.width = Math.max(1, Math.round(image.width * echelle));
            canvas.height = Math.max(1, Math.round(image.height * echelle));
            canvas.getContext('2d').drawImage(image, 0, 0, canvas.width, canvas.height);

            for (const qualite of [0.84, 0.7, 0.56, 0.44]) {
                const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', qualite));
                if (blob && blob.size <= tailleMax) {
                    const nom = fichier.name.replace(/\.[^.]+$/, '') + '.jpg';
                    return new File([blob], nom, { type: 'image/jpeg', lastModified: Date.now() });
                }
            }
            echelle *= 0.78;
        }
    } finally {
        image.close();
    }

    throw new Error('Impossible de réduire cette photo sous la limite du serveur.');
};
