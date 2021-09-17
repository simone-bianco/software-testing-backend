<template>
  <div>
    <v-menu
        :disabled="disabled"
        ref="menu"
        v-model="menu"
        :close-on-content-click="false"
        :return-value.sync="date"
        transition="scale-transition"
        offset-y
        min-width="auto">
      <template v-slot:activator="{ on, attrs }">
        <v-text-field
            v-model="date"
            label="Data"
            prepend-icon="mdi-calendar"
            readonly
            :error-messages="errorMessages"
            v-bind="attrs"
            v-on="on" />
      </template>
      <v-date-picker
          :min="getCurrentDate()"
          v-model="date"
          :allowed-dates="d => !busyDates.includes(d)"
          no-title
          scrollable>
        <v-spacer />
        <v-btn
            text
            color="primary"
            @click="menu = false">
          Cancel
        </v-btn>
        <v-btn
            text
            color="primary"
            @click="$refs.menu.save(date)">
          OK
        </v-btn>
      </v-date-picker>
    </v-menu>
  </div>
</template>

<script>
export default {
  props: {
    errorMessages: Array,
    baseDate: String,
    disabled: Boolean,
    busyDates: Array,
  },

  data() {
    return {
      date: this.baseDate,
      menu: false
    }
  },

  watch: {
    'date': {
      deep: true,
      immediate: true,
      handler(newValue, oldValue) {
        this.$emit('input', newValue)
      }
    },

    'baseDate': {
      handler(newValue, oldValue) {
        this.date = newValue
      }
    }
  }
}
</script>



