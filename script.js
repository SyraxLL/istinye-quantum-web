document.addEventListener('DOMContentLoaded', () => {
    const langCheckbox = document.getElementById('lang-checkbox');
    const submitLangCheckbox = document.getElementById('submit-lang-checkbox');

    // Load saved lang, default 'tr'
    let currentLang = localStorage.getItem('siteLang') || 'tr';

    function setLanguage(lang) {
        currentLang = lang;
        localStorage.setItem('siteLang', lang);
        
        const isEn = (lang === 'en');
        
        // Toggle body class which controls .tr-content and .en-content via CSS
        if (isEn) {
            document.body.classList.add('lang-en');
        } else {
            document.body.classList.remove('lang-en');
        }

        // Sync slider positions
        if (langCheckbox) langCheckbox.checked = isEn;
        if (submitLangCheckbox) submitLangCheckbox.checked = isEn;
        
        // For submit.php if there's a back-button dynamic link or something
        // (Handled natively by PHP but we sync the UI toggle)
    }

    // Initialize UI
    setLanguage(currentLang);

    // Bind events
    if (langCheckbox) {
        langCheckbox.addEventListener('change', (e) => {
            setLanguage(e.target.checked ? 'en' : 'tr');
        });
    }

    if (submitLangCheckbox) {
        submitLangCheckbox.addEventListener('change', (e) => {
            // Because submit page is static rendered via PHP,
            // changing the language toggle here doesn't automatically translate PHP errors.
            // But we can store it, and optionally reload or let the user click "Back" in the new lang.
            setLanguage(e.target.checked ? 'en' : 'tr');
            
            // To translate the submit page properly, we might just reload it with a GET param, or since it's a POST response, 
            // It's better they see the page in the language they submitted it in. 
            // But if they toggle it here, we at least save their preference for the next page.
        });
    }
});
