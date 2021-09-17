<template>
  <v-container>
    <v-row>
      <v-col cols="12">
        <v-menu
            ref="firstMenu"
            v-model="firstMenu"
            :close-on-content-click="false"
            :nudge-right="40"
            :return-value.sync="fromTime"
            transition="scale-transition"
            offset-y
            max-width="290px"
            min-width="290px">
          <template v-slot:activator="{ on, attrs }">
            <div class="flex items-center">
              <v-text-field
                  v-model="fromTime"
                  label="Dalle"
                  prepend-icon="mdi-clock"
                  readonly
                  v-bind="attrs"
                  v-on="on"
              ></v-text-field>
              <v-btn
                  @click="refreshFromTime"
                  class="mb-3"
                  color="blue"
                  fab
                  v-blur
                  dark
                  x-small>
                <v-icon>mdi-refresh</v-icon>
              </v-btn>
            </div>
          </template>
          <v-time-picker
              v-if="firstMenu"
              :allowed-minutes="v => v % 30 === 0"
              v-model="fromTime"
              @click:minute="$refs.firstMenu.save(fromTime)"
              class="mt-4"
              format="24hr"
              full-width
              scrollable
              min="8:00"
              max="20:00" />
        </v-menu>
      </v-col>

      <v-spacer></v-spacer>
      <v-col cols="12">
        <v-menu
            ref="secondMenu"
            v-model="secondMenu"
            :close-on-content-click="false"
            :nudge-right="40"
            :return-value.sync="toTime"
            transition="scale-transition"
            offset-y
            max-width="290px"
            min-width="290px">
          <template v-slot:activator="{ on, attrs }">
            <div class="flex items-center">
              <v-text-field
                  v-model="toTime"
                  label="Alle"
                  prepend-icon="mdi-clock"
                  readonly
                  v-bind="attrs"
                  v-on="on"
              ></v-text-field>
              <v-btn
                  @click="refreshToTime"
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
          <v-time-picker
              v-if="secondMenu"
              :allowed-minutes="v => v % 30 === 0"
              v-model="toTime"
              @click:minute="$refs.secondMenu.save(toTime)"
              class="mt-4"
              format="24hr"
              full-width
              scrollable
              min="8:00"
              max="20:00" />
        </v-menu>
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
export default {
  data () {
    return {
      fromTime: null,
      toTime: null,
      firstMenu: false,
      secondMenu: false,
      timeStep: '10:10',
    }
  },

  watch: {
    fromTime: {
      handler() {
        this.$emit('fromTimeChanged', this.fromTime)
      }
    },

    toTime: {
      handler() {
        this.$emit('toTimeChanged', this.toTime)
      }
    }
  },

  methods: {
    refreshFromTime() {
      this.fromTime = null
    },

    refreshToTime() {
      this.toTime = null
    }
  }
}
</script>
