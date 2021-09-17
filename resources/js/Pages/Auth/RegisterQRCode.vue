<template>
  <v-app>
    <v-main class="bg">
      <div>
        <v-container fluid class="mt-15">
          <div class="xl:mt-20 lg:mt-10 xl:w-4/12 lg:w-6/12 md:w-8/12 xs:w-10/12 sm:w-8/12 mx-auto pb-10">
            <v-card class="pa-7">
              <v-card-title>Registrazione Obbligatoria 2FA</v-card-title>
              <div class="w-full flex items-center align-center justify-center">
                <div class="w-6/12 h-30 mt-10">
                  <v-img src="/uploads/qr.svg" alt="" />
                </div>
              </div>
              <form @submit.prevent="submit">
                <v-card-title>Secret</v-card-title>
                <v-card-text>{{ loadedSecret }}</v-card-text>
                <v-text-field
                    v-model="form.code"
                    label="Codice Generato"
                    :rules="generatedCodeRules"
                    hide-details="auto"
                    class="ml-4"
                ></v-text-field>
                <errors-catcher class="ml-4" :bag="form.errors" :exclude-mode="true" :wildcards="[]" />
                <v-btn
                    :loading="loading"
                    v-blur
                    :disabled="loading"
                    color="#212529"
                    class="mt-7 ml-4 white--text"
                    @click="submit">
                  Completa Registrazione
                  <v-icon
                      right
                      dark>
                    mdi-login
                  </v-icon>
                </v-btn>
              </form>
            </v-card>
          </div>
        </v-container>
      </div>
    </v-main>
  </v-app>
</template>


<script>
import ErrorsCatcher from "../../Shared/ErrorsCatcher";
import { validationMixin } from "vuelidate";
import { required, email } from 'vuelidate/lib/validators'

export default {
  components: {
    ErrorsCatcher
  },

  mixins: [validationMixin],

  props: {
    qr: String,
    secret: String,
    status: String,
    error: String,
  },

  data() {
    return {
      loading: false,
      loadedSecret: this.secret,
      errorMessages: [],
      successMessages: [],
      generatedCodeRules: [
        value => !!value || 'Obbligatorio',
        value => (value && value.length === 6) || 'Deve essere di 6 numeri',
      ],
      form: this.$inertia.form({
        code: this.code,
        secret: this.secret,
      }, {
        bag: 'validation',
        errors: Object,
        resetOnSuccess: false,
      })
    }
  },

  created() {
    document.title = "Authenticator"
  },

  watch: {
    'form.processing': {
      deep: true,
      immediate: true,
      handler(newValue, oldValue) {
        if (typeof (oldValue) === 'undefined') {
          this.loading = false;
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

  methods: {
    alertErrors() {
      this.errorMessages.push('Si è verificato un errore')
    },

    submit() {
      this.form
          .transform(data => ({
            ... data,
          }))
          .post(this.route('2fa.completeRegistration'), {
            preserveScroll: true
          })
    }
  }
}
</script>

<style>
.bg {
  background: url("login_images/back6.jpg")
  center center fixed !important;
  background-size: cover;
}
</style>