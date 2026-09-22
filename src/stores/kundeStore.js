// stores/kundeStore.js
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useKundeStore = defineStore('kunde', () => {
  const defaultKunde = () => ({
    // Base
    id: null,
    kundennummer: '',
    person_type: 'Privatkunde',
    profilbild: null,

    // Personendaten
    anrede: '',
    vorname: '',
    nachname: '',

    // Gewerbedaten
    firma: '',
    zusatz: '',
    kontakt: '',
    hrb_register_nummer: '',
    hrb_gerichtsstand: '',

    // Authentifizierung
    email: '',
    passwort: '',
    email_verifiziert: false,
    letzte_anmeldung: null,
    agb_accepted: false,
    datenschutz_accepted: false,

    // Identifikation
    steuernummer: '',
    ust_id_nr: '',
    auslandskunden: false,
    eg_ausland: false,

    // Klassifizierung
    kundengruppe: '',
    branche: '',

    // Adressen
    adressen: [
      {
        id: null,
        typ: 'Rechnungsadresse',
        strasse: '',
        hausnummer: '',
        adresszusatz: '',
        plz: '',
        ort: '',
        bundesland: '',
        land: 'Deutschland',
        postfach: '',
        ist_standard: true
      }
    ],

    // Kommunikation
    kommunikation: [
      {
        id: null,
        typ: 'Telefon',
        wert: '',
        bemerkung: '',
        ist_standard: true
      }
    ],

    // Bankverbindungen
    bankverbindungen: [
      {
        id: null,
        kontoinhaber: '',
        iban: '',
        bic: '',
        bankname: '',
        ist_standard: true
      }
    ],

    // Rechnungsdaten
    zahlungsweise: '',
    versandart: '',
    zahlungsziel: null,
    gesamtrabatt: null,

    // Preisgruppen
    preisgruppen: [{ id: null, preisgruppe: '', prioritaet: 0 }],

    // Artikelrabatte
    artikelrabatte: [{ id: null, stufe: 1, prozent: null }],

    // Mahngebühren
    mahngebuehren: [{ id: null, stufe: 1, betrag: null }],

    // Skonto
    skonto: [{ id: null, stufe: 1, prozent: null, tage: null }],

    // Bemerkungen
    bemerkungen: [{ id: null, typ: 'internal', bemerkung: '' }],

    // Zusatzfelder
    zusatzfelder: [{ id: null, feldname: '', wert: '' }]
  })

  const kunde = ref(defaultKunde())

  const setKunde = (data) => {
    kunde.value = { ...defaultKunde(), ...data }
  }

  const resetKunde = () => {
    kunde.value = defaultKunde()
  }

  return { kunde, setKunde, resetKunde }
})