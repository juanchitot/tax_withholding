const languages = new Map([['en', 'https://cdn.datatables.net/plug-ins/1.10.19/i18n/English.json'],['es', 'https://cdn.datatables.net/plug-ins/1.10.19/i18n/Spanish.json']]);
const currentLanguage = languages.get(document.documentElement.lang);

export default currentLanguage;