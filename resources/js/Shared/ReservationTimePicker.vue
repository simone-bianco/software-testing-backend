<template>
  <div>
    <v-menu
        :disabled="disabled"
        ref="menu"
        v-model="menu"
        :close-on-content-click="false"
        :nudge-right="40"
        :return-value.sync="time"
        transition="scale-transition"
        offset-y
        max-width="290px"
        min-width="290px">
      <template v-slot:activator="{ on, attrs }">
        <v-text-field
            v-model="time"
            label="Orario"
            prepend-icon="mdi-clock"
            :error-messages="errorMessages"
            readonly
            v-bind="attrs"
            v-on="on" />
      </template>
      <v-time-picker
          ref="time-picker"
          v-if="menu"
          v-model="time"
          full-width
          :allowed-minutes="v => isMinuteAllowed(v)"
          :allowed-hours="h => !excludedHours.includes(h)"
          format="24hr"
          min="8:00"
          max="20:00"
          @click:minute="$refs.menu.save(time)" />
    </v-menu>
  </div>
</template>

<script>
export default {
  props: {
    errorMessages: Array,
    baseTime: String,
    disabled: Boolean,
    busyTimes: Array,
  },

  data() {
    return {
      time: this.baseTime,
      menu: false
    }
  },

  methods: {
    isMinuteAllowed(v) {
      if (v % 30 !== 0) return false

      let selectedHour = this.$refs["time-picker"].$data.lazyInputHour
      selectedHour = selectedHour + ''
      v = v + ''
      if (selectedHour.length === 1) selectedHour = '0' + selectedHour
      if (v.length === 1) v = '0' + v
      return !this.busyTimes.includes(selectedHour + ':' + v);
    },
  },

  computed: {
    excludedHours() {
      let hours = []
      let excludedHours = []
      this.busyTimes.forEach(function (excludedTime) {
        let hour = excludedTime.split(':')[0]
        if (!hours.includes(hour)) {
          hours.push(hour)
        } else {
          excludedHours.push(Number(hour))
        }
      })
      return excludedHours
    },
  },

  watch: {
    'time': {
      deep: true,
      immediate: true,
      handler(newValue, oldValue) {
        this.$emit('input', newValue)
      }
    }
  }
}
</script>
