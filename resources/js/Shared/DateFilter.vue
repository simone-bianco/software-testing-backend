<template>
  <v-container>
    <v-row>
      <v-col cols="12" >
        <v-menu
            ref="menu1"
            v-model="menu1"
            :close-on-content-click="false"
            transition="scale-transition"
            offset-y
            max-width="290px"
            min-width="auto" >
          <template v-slot:activator="{ on, attrs }">
            <div class="flex items-center">
              <v-text-field
                  v-model="fromDateFormatted"
                  label="Dal"
                  persistent-hint
                  prepend-icon="mdi-calendar"
                  v-bind="attrs"
                  v-on="on"
              ></v-text-field>
              <v-btn
                  @click="refreshFromDate"
                  class="mb-3"
                  color="blue"
                  v-blur
                  fab
                  dark
                  x-small>
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </div>
          </template>
          <v-date-picker
              v-model="fromDate"
              no-title
              @input="menu1 = false"
          ></v-date-picker>
        </v-menu>
      </v-col>

      <v-col cols="12">
        <v-menu
            v-model="menu2"
            :close-on-content-click="false"
            transition="scale-transition"
            offset-y
            max-width="290px"
            min-width="auto" >
          <template v-slot:activator="{ on, attrs }">
            <div class="flex items-center">
              <v-text-field
                  v-model="toDateFormatted"
                  label="Al"
                  persistent-hint
                  prepend-icon="mdi-calendar"
                  readonly
                  v-bind="attrs"
                  v-on="on"
              ></v-text-field>
              <v-btn
                  @click="refreshToDate"
                  class="mb-3"
                  color="blue"
                  v-blur
                  fab
                  dark
                  x-small>
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </div>
          </template>
          <v-date-picker
              v-model="toDate"
              no-title
              @input="menu2 = false"
          ></v-date-picker>
        </v-menu>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
export default {
  data() {
    return {
      fromDate: null,
      toDate: null,
      fromDateFormatted: "",
      toDateFormatted: "",
      menu1: false,
      menu2: false,
    }
  },

  watch: {
    fromDate (val) {
      this.$emit('dateChanged', {'from_date': this.fromDate})
      this.fromDateFormatted = this.formatDate(this.fromDate)
    },

    toDate (val) {
      this.$emit('dateChanged', {'to_date': this.toDate})
      this.toDateFormatted = this.formatDate(this.toDate)
    },
  },

  methods: {
    refreshFromDate() {
      this.fromDate = null
    },

    refreshToDate() {
      this.toDate = null
    },

    formatDate (date) {
      if (!date) return null

      const [year, month, day] = date.split('-')
      return `${day}/${month}/${year}`
    },
  },
}
</script>



