// stores/employeeStore.js
import { defineStore } from 'pinia'
import { reactive } from 'vue'

export const useEmployeeStore = defineStore('employee', () => {
  // ✅ reactive : l'objet est directement accessible sans .value
  const employee = reactive({
    // Identité
    email: '',
    vorname: '',
    nachname: '',
    telefon: '',
    mobil: '',

    // Berufliche Daten
    position: '',
    abteilung: '',
    einstellungsdatum: '',
    austrittsdatum: '',
    geburtsdatum: '',
    gehalt: null,
    role: '',
    vorgesetzter: '',

    // Documents administratifs
    steuer_id: '',
    sozialversicherungsnummer: '',
    vertragsart: '',
    wochenarbeitszeit: null,
    steuerklasse: '1',
    konfession: 'keine',

    // Notfallkontakt
    notfall_kontakt_name: '',
    notfall_kontakt_telefon: '',

    // Versicherung
    versicherung_typ: 'Krankenversicherung',
    versicherung_gesellschaft: '',
    versicherung_nummer: '',
    versicherung_gueltig_ab: '',
    versicherung_gueltig_bis: '',
    versicherung_beitrag: null,

    // Adresse (objet unique pour le formulaire)
    adresse: {
      strasse: '',
      hausnummer: '',
      plz: '',
      stadt: '',
      land: 'Deutschland'
    },

    // Adresses multiples (venant du backend)
    addresses: [],

    // Tableaux
    bank_accounts: [],
    qualifications: [],
    documents: [],

    // Champs issus du backend (lecture seule)
    uid: '',
    benutzer_id: null,
    user_uid: '',
    mitarbeiter_nummer: '',
    username: '',
    is_active: true,
    foto_pfad: null,
    photo: null,
    vorgesetzter_foto: null,
    addresses: []
  })

  /**
   * ✅ Fusionne les données dans l'employé sans casser la réactivité.
   * - Préserve la structure des objets imbriqués (adresse)
   * - Gère les tableaux (remplacement)
   */
  const setEmployee = (data) => {
    if (!data || typeof data !== 'object') return

    Object.keys(data).forEach((key) => {
      const value = data[key]

      // Cas spécial : adresse (objet)
      if (key === 'adresse' && value && typeof value === 'object') {
        employee.adresse = {
          strasse:    value.strasse    ?? employee.adresse.strasse,
          hausnummer: value.hausnummer ?? employee.adresse.hausnummer,
          plz:        value.plz        ?? employee.adresse.plz,
          stadt:      value.stadt      ?? employee.adresse.stadt,
          land:       value.land       ?? employee.adresse.land
        }
        return
      }

      // Tableaux : remplacement
      if (Array.isArray(value)) {
        employee[key] = [...value]
        return
      }

      // Cas standard : assignation directe
      if (key in employee || !key.startsWith('_')) {
        employee[key] = value
      }
    })
  }

  /**
   * ✅ Reset complet de l'employé (pour nouveau formulaire)
   */
  const resetEmployee = () => {
    Object.assign(employee, {
      email: '',
      vorname: '',
      nachname: '',
      telefon: '',
      mobil: '',
      position: '',
      abteilung: '',
      einstellungsdatum: '',
      austrittsdatum: '',
      geburtsdatum: '',
      gehalt: null,
      role: '',
      vorgesetzter: '',
      steuer_id: '',
      sozialversicherungsnummer: '',
      vertragsart: '',
      wochenarbeitszeit: null,
      steuerklasse: '1',
      konfession: 'keine',
      notfall_kontakt_name: '',
      notfall_kontakt_telefon: '',
      versicherung_typ: 'Krankenversicherung',
      versicherung_gesellschaft: '',
      versicherung_nummer: '',
      versicherung_gueltig_ab: '',
      versicherung_gueltig_bis: '',
      versicherung_beitrag: null,
      adresse: {
        strasse: '',
        hausnummer: '',
        plz: '',
        stadt: '',
        land: 'Deutschland'
      },
      addresses: [],
      bank_accounts: [],
      qualifications: [],
      documents: [],
      uid: '',
      benutzer_id: null,
      user_uid: '',
      mitarbeiter_nummer: '',
      username: '',
      is_active: true,
      foto_pfad: null,
      photo: null,
      vorgesetzter_foto: null
    })
  }

  return { employee, setEmployee, resetEmployee }
})