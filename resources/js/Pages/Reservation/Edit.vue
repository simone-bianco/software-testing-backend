<template>
  <app-layout :external-errors.sync="errorMessages" :external-success.sync="successMessages" :breadcrumbs="breadItems">
    <div>
      <div class="pb-12 pt-2 mx-10">
        <v-card>
          <v-card-title>
            Dettagli Prenotazione
          </v-card-title>
          <form @submit.prevent="" class="mx-5 my-5">
            <v-row>
              <v-col cols="6">
                <reservation-date-picker
                    v-model="form.date"
                    :error-messages="getValidationErrors(form.errors, 'validation.date*')"
                    :busy-dates="busyDates"
                    :disabled="form.state !== 'pending'"
                    :base-date="form.date" />
              </v-col>
              <v-col cols="6">
                <reservation-time-picker
                    v-model="form.time"
                    :error-messages="getValidationErrors(form.errors, 'validation.time*')"
                    :busy-times="busyTimes"
                    :base-time="form.time"
                    :disabled="form.state !== 'pending'" />
              </v-col>
            </v-row>

            <v-text-field
                v-model="stateLabel"
                :error-messages="getValidationErrors(form.errors, 'validation.state*')"
                class="mt-6"
                label="Stato"
                readonly />
            <div class="flex mt-3 items-center">
              <v-tooltip :max-width="500" right>
                <template v-slot:activator="{ on, attrs }">
                  <div v-on="on">
                    <v-icon>mdi-information</v-icon>
                  </div>
                </template>
                <span>
                  La dose viene riservata al momento della prenotazione e rimane "bloccata" finché questa non viene
                  confermata o rifiutata. Pertanto è possibile confermare la prenotazione anche se la quantità del
                  vaccino già riservato è 0
                </span>
              </v-tooltip>
              <div class="ml-3">
                <v-select
                    :readonly="form.state !== 'pending'"
                    v-model="form.vaccine"
                    :error-messages="vaccineErrors"
                    :items="vaccinesWithQty"
                    :item-text="selectedVaccine => selectedVaccine.name + ' - ' + selectedVaccine.qty + ' dosi'"
                    :item-value="selectedVaccine => selectedVaccine.name"
                    label="Vaccino Assegnato*"
                    required
                    @change="$v.validation.vaccineOk.$touch()"
                    @blur="$v.validation.vaccineOk.$touch()" />
              </div>
            </div>
            <span v-if="form.state === 'pending'" class="text-sm">
              *Dose di {{ vaccine.name }} già riservata
            </span>

            <v-textarea
                clearable
                counter
                clear-icon="mdi-close-circle"
                placeholder="Note"
                :error-messages="notesErrors.concat(getValidationErrors(this.form.errors, 'notes*'))"
                class="mt-6"
                v-model="form.notes"
                label="Note"
                value=""
                required
                @input="$v.form.notes.$touch()"
                @blur="$v.form.notes.$touch()" />

            <div class="flex flex-wrap">
              <v-tooltip bottom>
                <template v-slot:activator="{ on, attrs }">
                  <div v-on="on">
                    <v-btn
                        :disabled="loading || form.state !== 'pending'"
                        color="success"
                        class="mr-4 mt-6"
                        v-bind="attrs"
                        v-on="on"
                        @click="submit('confirmed')">
                      Conferma
                    </v-btn>
                  </div>
                </template>
                <span>{{ btnConfirmTooltip }}</span>
              </v-tooltip>
              <v-tooltip bottom>
                <template v-slot:activator="{ on, attrs }">
                  <div v-on="on">
                    <v-btn
                        color="error"
                        :disabled="loading || form.state !== 'pending' && form.state !== 'confirmed'"
                        class="mr-4 mt-6"
                        v-bind="attrs"
                        v-on="on"
                        @click="submit('cancelled')">
                      Annulla
                    </v-btn>
                  </div>
                </template>
                <span>{{ btnCancelTooltip }}</span>
              </v-tooltip>
              <v-tooltip bottom>
                <template v-slot:activator="{ on, attrs }">
                  <div v-on="on">
                    <v-btn
                        color="success"
                        :disabled="loading || form.state !== 'confirmed'"
                        class="mr-4 mt-6"
                        @click="submit('completed')">
                      Completa
                    </v-btn>
                  </div>
                </template>
                <span>{{ btnCompleteTooltip }}</span>
              </v-tooltip>
              <v-tooltip bottom>
                <template v-slot:activator="{ on, attrs }">
                  <div v-on="on">
                    <v-btn
                        color="success"
                        :disabled="loading || form.state !== 'completed'"
                        class="mr-4 mt-6"
                        @click="recall">
                      Prenota Richiamo
                    </v-btn>
                  </div>
                </template>
                <span>{{ btnRecallTooltip }}</span>
              </v-tooltip>
            </div>
            <errors-catcher :bag="form.errors" :exclude-mode="true" :wildcards="excludeErrors" />
          </form>
        </v-card>
        <patient-sheet class="mt-5" :patient="patient" :account="account" :user="user" :old-reservations="oldReservations" />
      </div>
    </div>
  </app-layout>

</template>

<script>
import AppLayout from '@/Layouts/AppLayout'
import { validationMixin } from 'vuelidate'
import { helpers, required, maxLength } from 'vuelidate/lib/validators'
import PatientSheet from "../../Shared/PatientSheet";
import ErrorsCatcher from "../../Shared/ErrorsCatcher";
import ReservationDatePicker from "../../Shared/ReservationDatePicker";
import ReservationTimePicker from "../../Shared/ReservationTimePicker";

const alpha = helpers.regex('alpha', /^[A-Za-zÀ-ÖØ-öø-ÿ0-9 ,.;?!\n]*$/)

export default {
  props: {
    patient: Object,
    user:Object,
    reservation: Object,
    account:Object,
    vaccine:Object,
    availableVaccines: Array,
    busyDates: Array,
    vaccinesWithQty: Array,
    oldReservations: Array,
  },

  mixins: [validationMixin],

  components: {
    ReservationTimePicker,
    AppLayout,
    PatientSheet,
    ErrorsCatcher,
    ReservationDatePicker,
  },

  validations: {
    validation: {
      vaccineOk: { required },
    },
    form: {
      notes: {
        maxLength: maxLength(255),
        alpha
      }
    }
  },

  data() {
    return {
      breadItems: [
        {
          text: 'Visualizza Prenotazioni',
          disabled: false,
          href: route('reservations.index'),
        },
        {
          text: 'Gestisci Prenotazione',
          disabled: true,
        },
      ],
      excludeErrors: [
          'validation.date*',
          'validation.time*',
          'validation.quantity*',
          'validation.state*'
      ],
      busyTimes: [],
      errorMessages: [],
      successMessages: [],
      loading: false,
      form: this.$inertia.form({
        id: this.reservation.id,
        date: this.reservation.date,
        time: this.reservation.time,
        vaccine: this.vaccine.name,
        state: this.reservation.state,
        notes: this.reservation.notes
      }, {
        bag: 'validation',
        errors: Object,
        resetOnSuccess: false,
      }),
      validation: {
        vaccineOk: this.availableVaccines.includes(this.vaccine.name)
      },
    }
  },

  watch: {
    'form.date': {
      deep: true,
      immediate: true,
      handler(newValue, oldValue) {
        this.axios.post(route('reservations.busytimes', {'date': newValue, 'reservation_id': this.form.id}))
            .then((response) => {
              if (!Array.isArray(response.data)) {
                this.alertErrors()
              }
              this.busyTimes = response.data
            })
            .catch((error) => {
              this.alertErrors()
            })
      }
    },

    'form.processing': {
      deep: true,
      immediate: true,
      handler(newValue, oldValue) {
        if (typeof (oldValue) === 'undefined') {
          return;
        }
        if (newValue) {
          this.loading = true;
          return;
        }
        if (this.form.recentlySuccessful) {
          this.successMessages.push('Operazione completata con successo')
        } else {
          this.alertErrors()
        }
        this.updateForm()
        this.loading = false;
      }
    },
  },

  created() {
    document.title = "Gestisci Prenotazione"
  },

  methods: {
    updateForm() {
      this.form.date = this.reservation.date
      this.form.time = this.reservation.time
      this.form.state = this.reservation.state
      this.form.vaccine = this.vaccine.name
    },

    alertErrors() {
      this.errorMessages.push('Si è verificato un errore')
    },

    submit(state) {
      this.$v.$touch()
      if (!this.$v.$anyError && !this.$v.$invalid || state === 'cancelled') {
        this.form
            .transform(data => ({
              ...data,
              state: state
            }))
            .put(route('reservations.update', this.form.id), {
              preserveScroll: true
            })
      } else {
        this.alertErrors()
      }
    },

    recall() {
      this.$inertia.get(this.route('reservations.create', this.reservation.id));
    }
  },

  computed: {
    btnCancelTooltip() {
      if (this.form.state === 'pending' || this.form.state === 'confirmed') return 'Rifiuta la prenotazione (le modifiche non verranno salvate)'
      return (this.form.state !== 'completed' ? 'Prenotazione ' : '') + this.stateLabel
    },

    btnConfirmTooltip() {
      if (this.form.state === 'pending') return 'Salva le modifiche e conferma la prenotazione'
      return (this.form.state !== 'completed' ? 'Prenotazione ' : '') + this.stateLabel
    },

    btnCompleteTooltip() {
      if (this.form.state === 'confirmed') return 'Conferma avvenuta somministrazione del vaccino'
      return (this.form.state !== 'completed' ? 'Prenotazione ' : '') + this.stateLabel
    },

    btnRecallTooltip() {
      if (this.form.state === 'cancelled') return 'Prenotazione Cancellata'
      if (this.form.state !== 'completed') return 'Non è possibile prenotare il richiamo prima della somministrazione'
      return 'Prenota il Richiamo'
    },

    vaccineErrors() {
      const errors = []
      this.validation.vaccineOk = this.availableVaccines.includes(this.form.vaccine) ? this.form.vaccine : null
      !this.$v.validation.vaccineOk.required && errors.push('Vaccino non disponibile')
      errors.concat(this.getValidationErrors(
          this.form.errors, ['validation.vaccine*', 'validation.quantity*']
      ))
      return errors
    },

    notesErrors() {
      const errors = []
      if (!this.$v.form.notes.$dirty) return errors
      !this.$v.form.notes.maxLength && errors.push('Massimo di 255 caratteri superato')
      !this.$v.form.notes.alpha && errors.push('Sono presenti caratteri non validi')
      return errors
    },

    stateLabel() {
      if (this.form.state === 'pending') return 'In Attesa di Conferma'
      if (this.form.state === 'cancelled') return 'Cancellata'
      if (this.form.state === 'confirmed') return 'Confermata'
      if (this.form.state === 'completed') return 'Vaccino Somministrato'
      return 'Non definito'
    },
  }
}
</script>
