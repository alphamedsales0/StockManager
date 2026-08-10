// stores/employeeStore.js
import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useEmployeeStore = defineStore('employee', () => {
  const employee = ref({
    email: '',
    vorname: '',
    nachname: '',
    mitarbeiter_nummer: '',
    telefon: '',
    mobil: '',
    position: '',
    abteilung: '',
    einstellungsdatum: '',
    geburtsdatum: '',
    gehalt: null,
    notfall_kontakt_name: '',
    notfall_kontakt_telefon: '',
    steuer_id: '',
    sozialversicherungsnummer: '',
    vertragsart: '',
    wochenarbeitszeit: null,
    steuerklasse: '1',
    konfession: 'keine',
    versicherung_typ: 'Krankenversicherung',
    versicherung_gesellschaft: '',
    versicherung_nummer: '',
    versicherung_gueltig_ab: '',
    versicherung_gueltig_bis: '',
    versicherung_beitrag: null,
    bank_accounts: [],
    qualifications: [],
    documents: [],
    adresse: {
      strasse: '',
      hausnummer: '',
      plz: '',
      stadt: '',
      land: 'Deutschland'
    }
  })

  const setEmployee = (data) => {
    employee.value = { ...employee.value, ...data }
  }

  const resetEmployee = () => {
    employee.value = {
      email: '',
      vorname: '',
      nachname: '',
      telefon: '',
      mobil: '',
      position: '',
      abteilung: '',
      einstellungsdatum: '',
      geburtsdatum: '',
      gehalt: null,
      notfall_kontakt_name: '',
      notfall_kontakt_telefon: '',
      steuer_id: '',
      sozialversicherungsnummer: '',
      vertragsart: '',
      wochenarbeitszeit: null,
      steuerklasse: '1',
      konfession: 'keine',
      versicherung_typ: 'Krankenversicherung',
      versicherung_gesellschaft: '',
      versicherung_nummer: '',
      versicherung_gueltig_ab: '',
      versicherung_gueltig_bis: '',
      versicherung_beitrag: null,
      bank_accounts: [],
      qualifications: [],
      documents: [],
      adresse: {
        strasse: '',
        hausnummer: '',
        plz: '',
        stadt: '',
        land: 'Deutschland'
      }
    }
  }

  return { employee, setEmployee, resetEmployee }
})