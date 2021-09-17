<template>
  <app-layout :external-errors.sync="errorMessages" :external-success.sync="successMessages" :breadcrumbs="breadItems">
    <div>
      <div class="pb-12 pt-2 mx-10">
        <v-card>
          <v-card-title>
            Reservation Richiamo
          </v-card-title>
          <form @submit.prevent="" class="mx-5 my-5">
            <v-row>
              <v-col cols="6">
                <reservation-date-picker
                    :disabled="false"
                    v-model="form.date"
                    :error-messages="getValidationErrors(form.errors, 'validation.date*')"
                    :busy-dates="busyDates"
                    :state="form.state"
                    :base-date="form.date" />
              </v-col>
              <v-col cols="6">
                <reservation-time-picker
                    :disabled="false"
                    v-model="form.time"
                    :error-messages="getValidationErrors(form.errors, 'validation.time*')"
                    :busy-times="busyTimes"
                    :base-time="form.time"
                    :state="form.state" />
              </v-col>
            </v-row>

            <v-select
                class="mt-6"
                v-model="form.vaccine"
                :error-messages="vaccineErrors"
                :items="vaccinesWithQty"
                :item-text="selectedVaccine => selectedVaccine.name + ' - ' + selectedVaccine.qty + ' dosi'"
                :item-value="selectedVaccine => selectedVaccine.name"
                label="Vaccino Assegnato*"
                required
                @change="$v.validation.vaccineOk.$touch()"
                @blur="$v.validation.vaccineOk.$touch()" />

            <v-text-field
                class="mt-5"
                v-model="form.structure"
                label="Structure" />

            <div class="mt-5 flex">
              <v-tooltip bottom>
                <template v-slot:activator="{ on, attrs }">
                  <div v-on="on">
                    <v-btn
                        :disabled="loading"
                        color="success"
                        class="mr-4"
                        v-bind="attrs"
                        v-on="on"
                        @click="submit">
                      Conferma
                    </v-btn>
                  </div>
                </template>
                <span>Conferma Richiamo</span>
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
import PatientSheet from "../../Shared/PatientSheet";
import ErrorsCatcher from "../../Shared/ErrorsCatcher";
import ReservationDatePicker from "../../Shared/ReservationDatePicker";
import ReservationTimePicker from "../../Shared/ReservationTimePicker";
import {required} from "vuelidate/lib/validators";
import {validationMixin} from "vuelidate";

export default {
  props: {
    patient: Object,
    reservation: Object,
    account:Object,
    user:Object,
    structure:Object,
    vaccine:Object,
    availableVaccines: Array,
    vaccinesWithQty: Array,
    busyDates: Array,
    oldReservations: Array,
  },

  mixins: [validationMixin],

  created() {
    document.title = "Prenota Richiamo"
  },

  components: {
    AppLayout,
    PatientSheet,
    ReservationTimePicker,
    ErrorsCatcher,
    ReservationDatePicker,
  },

  validations: {
    validation: {
      vaccineOk: { required }
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
          disabled: false,
          href: route('reservations.edit', this.reservation.id),
        },
        {
          text: 'Prenota Richiamo',
          disabled: true,
        },
      ],
      excludeErrors: ['date', 'time', 'quantity', 'state'],
      busyTimes: [],
      errorMessages: [],
      successMessages: [],
      loading: false,
      form: this.$inertia.form({
        date: this.reservation.date,
        time: this.reservation.time,
        vaccine:this.vaccine.name,
        structure:this.structure.name,
        patient:this.patient.id,
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
        this.loading = false;
        if (this.form.recentlySuccessful) {

        } else {
          this.alertErrors()
        }
      }
    },
  },

  computed: {
    vaccineErrors() {
      const errors = []
      this.validation.vaccineOk = this.availableVaccines.includes(this.form.vaccine) ? this.form.vaccine : null
      !this.$v.validation.vaccineOk.required && errors.push('Vaccino non disponibile')
      errors.concat(this.getValidationErrors(
          this.form.errors, ['validation.vaccine*', 'validation.quantity*']
      ))
      return errors
    },
  },

  methods: {
    alertErrors(error = 'Si è verificato un errore') {
      this.errorMessages.push(error)
    },

    submit() {
      this.$v.$touch()
      if (!this.$v.$anyError && !this.$v.$invalid) {
        this.form
            .post(route('reservations.store'), {
              preserveScroll: true
            })
      } else {
        this.alertErrors()
      }
    },
  }
}
</script>



