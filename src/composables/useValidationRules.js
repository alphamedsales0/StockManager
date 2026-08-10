// composables/useValidationRules.js

export const useValidationRules = () => {
  const required = (v) => !!v || 'Pflichtfeld erforderlich'
  const email = (v) => /.+@.+\..+/.test(v) || 'Ungültige E-Mail-Adresse'
  const phone = (v) => !v || /^[0-9+\s()-]+$/.test(v) || 'Ungültige Telefonnummer'
  const number = (v) => !v || /^\d+(\.\d+)?$/.test(v) || 'Nur Zahlen erlaubt'

  // NEU: alphanumerisch, erlaubt Buchstaben, Zahlen, Leerzeichen, Bindestrich und Schrägstrich
  const alphanumeric = (v) =>
    !v || /^[a-zA-Z0-9\s\-/]+$/.test(v) ||
    'Nur Buchstaben, Zahlen, Leerzeichen, - und / erlaubt'

  const minLength = (len) => (v) => !v || v.length >= len || `Mindestens ${len} Zeichen`
  const maxLength = (len) => (v) => !v || v.length <= len || `Maximal ${len} Zeichen`

  // IBAN (einfache Prüfung)
  const iban = (v) => {
    if (!v) return true
    const cleaned = v.replace(/\s/g, '').toUpperCase()
    if (!/^[A-Z]{2}[0-9]{2}[A-Z0-9]{4,30}$/.test(cleaned)) {
      return 'Ungültige IBAN (muss mit zwei Buchstaben beginnen und mind. 8 Zeichen haben)'
    }
    return true
  }

  return {
    required,
    email,
    phone,
    number,
    alphanumeric,   // neu exportieren
    minLength,
    maxLength,
    iban
  }
}