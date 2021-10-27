<template>
  <v-app>
    <v-main class="bg">
      <div>
        <v-container fluid class="mt-15">
          <div class="xl:mt-20 lg:mt-10 xl:w-4/12 lg:w-6/12 md:w-8/12 xs:w-10/12 sm:w-8/12 mx-auto pb-10">
            <v-card class="pa-7">
              <v-card-title>Accesso Backoffice</v-card-title>
              <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
                {{ status }}
              </div>
              <div v-if="error" class="mb-4 font-medium text-sm text-red-600">
                {{ error }}
              </div>

              <form @submit.prevent="">
                <div class="w-full flex items-center align-center justify-center">
                </div>
                <v-text-field
                    placeholder="Inserisci password"
                    :error-messages="passwordErrors.concat(getValidationErrors(this.form.errors, 'password*'))"
                    :append-icon="showPassword ? 'mdi-eye' : 'mdi-eye-off'"
                    :type="showPassword ? 'text' : 'password'"
                    @click:append="showPassword = !showPassword"
                    class="mt-6"
                    v-model="form.password"
                    label="Password"
                    required
                    @input="$v.form.password.$touch()"
                    @blur="$v.form.password.$touch()" />
                <v-text-field
                    placeholder="Inserisci email"
                    :error-messages="emailErrors.concat(getValidationErrors(this.form.errors, 'email*'))"
                    class="mt-6"
                    v-model="form.email"
                    label="Email"
                    required
                    @input="$v.form.email.$touch()"
                    @blur="$v.form.email.$touch()" />
                <v-text-field
                    placeholder="Useless Field"
                    :error-messages="emailErrors.concat(getValidationErrors(this.form.errors, 'useless*'))"
                    class="mt-6"
                    label="Useless Field" />

                <div class="w-6/12 h-30 mt-10">
                  <v-img src="/login_images/logo.jpg" lazy-src="/login_images/logo_lazy.jpg" />
                </div>
                <v-btn
                    :loading="false"
                    v-blur
                    color="primary"
                    class="ma-2 white--text"
                    @click="submit">
                  Lowgain
                  <v-icon
                      right
                      dark>
                    mdi-login
                  </v-icon>
                </v-btn>
                <v-btn
                    id="useless_button"
                    :loading="false"
                    v-blur
                    :disabled="false"
                    color="#212529"
                    class="ma-2 white--text">
                  Useless Button
                  <v-icon
                      right
                      dark>
                    mdi-login
                  </v-icon>
                </v-btn>
                <div class="flex items-center justify-end mt-4">
                  <inertia-link v-if="canResetPassword" :href="route('password.request')" class="underline text-sm text-gray-600 hover:text-gray-900">
                    Hai dimenticato la password?
                  </inertia-link>

                  <div class="block mt-4">
                    <v-checkbox v-model="form.remember" label="Ricorda" :value="true" />
                  </div>
                </div>
              </form>
              <errors-catcher :bag="form.errors" :exclude-mode="true" :wildcards="excludeWildcards" />
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

  validations: {
    form: {
      email: { required, email },
      password: { required }
    }
  },

  props: {
    canResetPassword: Boolean,
    status: String,
    error: String,
  },

  data() {
    return {
      showPassword: false,
      excludeWildcards: ['email*', 'password*'],
      form: this.$inertia.form({
        email: '',
        password: '',
        remember: false
      }, {
        bag: 'validation',
        errors: Object,
        resetOnSuccess: false,
      })
    }
  },

  created() {
    document.title = "Login"
  },

  computed: {
    emailErrors() {
      const errors = []
      if (!this.$v.form.email.$dirty) return errors
      !this.$v.form.email.required && errors.push('Email richiesta')
      !this.$v.form.email.email && errors.push('Email in formato non corretto')
      return errors
    },

    passwordErrors() {
      const errors = []
      errors.concat(this.getValidationErrors(this.form.errors, 'password*'))
      if (!this.$v.form.password.$dirty) return errors
      !this.$v.form.password.required && errors.push('Password obbligatoria')
      return errors
    },
  },

  methods: {
    submit() {
      this.$v.$touch()
      if (!this.$v.$error) {
        this.form
            .transform(data => ({
              ... data,
              remember: this.form.remember ? 'on' : ''
            }))
            .post(this.route('login'), {
              preserveScroll: true
            })
      }
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