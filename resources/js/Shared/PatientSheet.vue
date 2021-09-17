<template>
  <v-card>
    <v-card-title>
      Scheda Paziente
    </v-card-title>
    <v-divider></v-divider>
    <div style="color: black" >
      <div class="flex flex-wrap">
        <div>
          <v-card-text>
            <span class="font-weight-bold">Nome: </span>{{account.first_name}}
          </v-card-text>
          <v-card-text>
            <span class="font-weight-bold">Cognome: </span>{{account.last_name}}
          </v-card-text>
          <v-card-text>
            <span class="font-weight-bold">Data di nascita: </span>{{ account.date_of_birth }}
          </v-card-text>
          <v-card-text>
            <span class="font-weight-bold">Genere: </span>
            {{ account.gender === 0 ? 'Femminile' : (account.gender === 1 ? 'Maschile' : 'Non Specificato') }}
          </v-card-text>
          <v-card-text>
            <span class="font-weight-bold">Codice Fiscale: </span>{{ account.fiscal_code }}
          </v-card-text>
          <v-card-text>
            <span class="font-weight-bold">Telefono: </span>{{ account.mobile_phone }}
          </v-card-text>
          <v-card-text>
            <span class="font-weight-bold">Email: </span>{{ user.email }}
          </v-card-text>
        </div>
        <div class="lg:ml-10 xl:ml-10">
          <v-card-text>
            <span class="font-weight-bold">Cardiopatia: </span>{{ getFormLabel(patient.heart_disease) }}
          </v-card-text>
          <v-card-text>
            <span class="font-weight-bold">Allergia: </span>{{ getFormLabel(patient.allergy)}}
          </v-card-text>
          <v-card-text>
            <span class="font-weight-bold">Immunodepressione: </span>{{ getFormLabel(patient.immunosuppression) }}
          </v-card-text>
          <v-card-text>
            <span class="font-weight-bold">Anticoagulanti: </span>{{ getFormLabel(patient.anticoagulants) }}
          </v-card-text>
          <v-card-text>
            <span class="font-weight-bold">Covid: </span>{{ getFormLabel(patient.covid) }}
          </v-card-text>
        </div>
        <div class="lg:ml-10 xl:ml-10 xs:mb-5">
          <div>
            <v-card-title>Storico Prenotazioni</v-card-title>
            <v-data-table
                v-if="oldReservations.length > 0"
                class="cursor-pointer text-left"
                :options.sync="options"
                :multi-sort="false"
                :headers="headers"
                :items="oldReservations"
                :items-per-page="1000"
                item-class="color"
                @click:row="handleClick"
                hide-default-footer />
            <div v-else>
              <v-card-text>Nessuna prenotazione trovata</v-card-text>
            </div>
          </div>
        </div>
      </div>
    </div>
  </v-card>
</template>

<script>
export default {
  props: {
    patient: Object,
    account: Object,
    user: Object,
    oldReservations: Array,
  },

  mounted() {
    this.reservationsTransform()
  },

  data() {
    return {
      headers: [
        { text: 'ID', sortable: false, value: 'id'},
        { text: 'Vaccino', value: 'stock.batch.vaccine.name', sortable: false },
        { text: 'Stato Prenotazione', value: 'stateLabel', sortable: false },
        { text: 'Data Prenotazione', value: 'date', sortable: false },
      ],
      options: { 'itemsPerPage': 100 },
    }
  },

  watch: {
    'oldReservations': {
      deep: true,
      immediate: true,
      handler(newValue, oldValue) {
        this.reservationsTransform()
      }
    }
  },

  methods: {
    getFormLabel(value) {
      if (value === 'true') return 'Si'
      return 'No'
    },

    handleClick(value) {
      this.$inertia.get(this.route('reservations.edit', value.id));
    },

    reservationsTransform() {
      this.oldReservations.map(function(content, index) {
        let style = ''
        switch (content['state']) {
          case 'cancelled':
            style = 'red-bg'
            content['stateLabel'] = 'Cancellata'
            break
          case 'confirmed':
            style = 'green-bg'
            content['stateLabel'] = 'Confermata'
            break
          case 'completed':
            content['stateLabel'] = 'Completa'
            break
          case 'pending':
            style = 'yellow-bg'
            content['stateLabel'] = 'Da Confermare'
            break
        }
        content['color'] = style
        content['is_recall'] = content['is_recall'] ? "Richiamo" : "Prima"
      })
    },
  },
}
</script>

<style>
.yellow-bg td {
  background-color: rgba(255, 255, 0, 0.3);
}

.green-bg td {
  background-color: rgba(0, 255, 0, 0.3);
}

.red-bg td{
  background-color: rgba(255, 0, 0, 0.3);
}
</style>

<style lang="scss" scoped>
.state-filters-container .v-input--selection-controls {
  margin-top: 0;
  padding-top: 0;
}

.state-filters-container .v-input--checkbox {
  margin-right: 10px;
}

.filters-card .v-card__title {
  padding-bottom: 0;
}
</style>
