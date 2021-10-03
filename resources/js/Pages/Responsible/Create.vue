<template>
  <app-layout :external-errors.sync="errorMessages" :external-success.sync="successMessages" :breadcrumbs="breadItems">
    <div>
      <div class="pb-12 pt-2 mx-10">
        <v-card>
          <v-card-title>
            Registra Responsabile
          </v-card-title>
          <form @submit.prevent="" class="mx-5 my-5">
            <v-row>
              <v-col cols="6">
                <v-text-field
                    id="first_name"
                    placeholder="Inserisci Nome"
                    :error-messages="getValidationErrors(this.form.errors, 'validation.first_name*')"
                    class="mt-6"
                    v-model="form.first_name"
                    label="Nome"
                    required />
                <v-text-field
                    id="last_name"
                    placeholder="Inserisci Cognome"
                    :error-messages="getValidationErrors(this.form.errors, 'validation.last_name*')"
                    class="mt-6"
                    v-model="form.last_name"
                    label="Cognome"
                    required />
                <v-text-field
                    id="email"
                    placeholder="Inserisci email"
                    :error-messages="getValidationErrors(this.form.errors, 'validation.email*')"
                    class="mt-6"
                    v-model="form.email"
                    label="Email"
                    required />
                <v-text-field
                    id="mobile_phone"
                    placeholder="Inserisci Numero di Telefono"
                    :error-messages="getValidationErrors(this.form.errors, 'validation.mobile_phone*')"
                    class="mt-6"
                    v-model="form.mobile_phone"
                    label="Numero di Telefono"
                    required />
                <v-select
                    class="mt-6"
                    v-model="form.gender"
                    :items="[{'text': 'Maschio', 'value': 0}, {'text': 'Femmina', 'value': 1}]"
                    item-text="text"
                    item-value="value" />
                <date-of-birth-picker
                    class="mt-6"
                    :disabled="false"
                    :busy-dates="[]"
                    v-model="form.date_of_birth"
                    :error-messages="getValidationErrors(form.errors, 'validation.date_of_birth*')"
                    :base-date="date" />
                <v-text-field
                    id="fiscal_code"
                    placeholder="Inserisci Codice Fiscale"
                    :error-messages="getValidationErrors(this.form.errors, 'validation.fiscal_code*')"
                    class="mt-6"
                    v-model="form.fiscal_code"
                    label="Codice Fiscale"
                    required />
                <v-text-field
                    id="city"
                    placeholder="Inserisci Città"
                    :error-messages="getValidationErrors(this.form.errors, 'validation.city*')"
                    class="mt-6"
                    v-model="form.city"
                    label="Città"
                    required />
                <v-text-field
                    id="address"
                    placeholder="Inserisci Indirizzo"
                    :error-messages="getValidationErrors(this.form.errors, 'validation.address*')"
                    class="mt-6"
                    v-model="form.address"
                    label="Indirizzo"
                    required />
                <v-text-field
                    id="cap"
                    placeholder="Inserisci Codice Postale"
                    :error-messages="getValidationErrors(this.form.errors, 'validation.cap*')"
                    class="mt-6"
                    v-model="form.cap"
                    label="Codice Postale"
                    required />
              </v-col>
            </v-row>
            <errors-catcher :bag="form.errors" :exclude-mode="true" :wildcards="excludeErrors" />
            <v-btn
                id="cancel"
                :loading="false"
                v-blur
                :disabled="false"
                color="#D31013"
                class="ma-2 white--text"
                @click="$inertia.get(route('dashboard.index'))">
              Annulla
            </v-btn>
            <v-btn
                id="submit"
                :loading="false"
                v-blur
                :disabled="false"
                color="#1022d3"
                class="ma-2 white--text"
                @click="submit">
              Salva
            </v-btn>
          </form>
        </v-card>
      </div>
    </div>
  </app-layout>
</template>

<script>

import AppLayout from '@/Layouts/AppLayout'
import ErrorsCatcher from "../../Shared/ErrorsCatcher";
import DateOfBirthPicker from "../../Shared/DateOfBirthPicker";

export default {
  components: {
    AppLayout,
    ErrorsCatcher,
    DateOfBirthPicker,
  },

  props: ['date'],

  data() {
    return {
      errorMessages: [],
      successMessages: [],
      loading: false,
      form: this.$inertia.form({
        first_name: "",
        last_name: "",
        email: "",
        date_of_birth: "2021-09-20",
        gender: 0,
        fiscal_code: "",
        city: "",
        address: "",
        cap: "",
        mobile_phone: "",
      }, {
        bag: 'validation',
        errors: Object,
        resetOnSuccess: false,
      }),
      breadItems: [
        {
          text: 'Dashboard',
          disabled: false,
          href: route('dashboard.index'),
        },
        {
          text: 'Registra Responsabile',
          disabled: true,
        },
      ],
      excludeErrors: [
        'validation.first_name*',
        'validation.last_name*',
        'validation.email*',
        'validation.date_of_birth*',
        'validation.gender*',
        'validation.fiscal_code*',
        'validation.city*',
        'validation.address*',
        'validation.cap*',
        'validation.mobile_phone*',
      ],
    }
  },

  watch: {
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
        this.loading = false;
      }
    },
  },

  created() {
    document.title = "Registra Responsabile"
  },

  methods: {
    alertErrors() {
      this.errorMessages.push('Si è verificato un errore')
    },

    submit() {
      this.form
          .transform(data => ({
            ...data
          }))
          .post(route('responsible.store'), {
            preserveScroll: true
          })
    },
  }
}
</script>