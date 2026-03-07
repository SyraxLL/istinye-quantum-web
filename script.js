document.addEventListener('DOMContentLoaded', () => {
    const langCheckbox = document.getElementById('lang-checkbox');
    const submitLangCheckbox = document.getElementById('submit-lang-checkbox');

    let currentLang = localStorage.getItem('siteLang') || 'tr';

    function setLanguage(lang) {
        currentLang = lang;
        localStorage.setItem('siteLang', lang);

        const isEn = (lang === 'en');

        if (isEn) {
            document.body.classList.add('lang-en');
            // WhatsApp metinlerini İngilizce yap
            document.querySelectorAll('.fab-whatsapp-tooltip, .mobile-wp-btn span').forEach(el => {
                el.textContent = 'WhatsApp';
            });
        } else {
            document.body.classList.remove('lang-en');
            // WhatsApp metinlerini Türkçe yap
            document.querySelectorAll('.fab-whatsapp-tooltip').forEach(el => {
                el.textContent = 'WhatsApp Kanalına Katıl';
            });
            document.querySelectorAll('.mobile-wp-btn span').forEach(el => {
                el.textContent = 'WhatsApp'; // Mobilde zaten WhatsApp demiştik
            });
        }

        if (langCheckbox) langCheckbox.checked = isEn;
        if (submitLangCheckbox) submitLangCheckbox.checked = isEn;

    }

    setLanguage(currentLang);

    if (langCheckbox) {
        langCheckbox.addEventListener('change', (e) => {
            setLanguage(e.target.checked ? 'en' : 'tr');
        });
    }

    if (submitLangCheckbox) {
        submitLangCheckbox.addEventListener('change', (e) => {
            setLanguage(e.target.checked ? 'en' : 'tr');

        });
    }
});
