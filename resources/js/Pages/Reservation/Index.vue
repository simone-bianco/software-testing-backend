<template>
  <app-layout :selected-item="1">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Prenotazioni
      </h2>
    </template>

    <div class="mt-7">
      <div class="filters-container max-w-full mx-auto pb-10 sm:px-6 lg:px-8">
        <v-card class="filters-card">
          <v-container class="ma-0 pa-0 ma-0 pa-0 filters-container fill-height">
            <v-row>
              <v-col lg="3">
                <div class="state-filters-container">
                  <v-card-title>Stato</v-card-title>
                  <div class="mt-5 ml-3 state-filters-checkboxes">
                    <v-checkbox v-model="form.state_filters" label="Da Confermare" value="pending" />
                    <v-checkbox v-model="form.state_filters" label="Cancellate" value="cancelled" />
                    <v-checkbox v-model="form.state_filters" label="Confermate" value="confirmed" />
                    <v-checkbox v-model="form.state_filters" label="Complete" value="completed" />
                  </div>
                </div>
              </v-col>
              <v-col>
                <div class="time-filters-container">
                  <v-card-title>Orario</v-card-title>
                  <div class="flex time-filters-pickers">
                    <time-filter @fromTimeChanged="updateFromTime" @toTimeChanged="updateToTime" />
                  </div>
                </div>
              </v-col>
              <v-col>
                <div class="date-filters-container">
                  <v-card-title>Data</v-card-title>
                  <div class="flex date-filters-pickers">
                    <date-filter @dateChanged="updateDateFilters" />
                  </div>
                </div>
              </v-col>
            </v-row>
          </v-container>
        </v-card>
      </div>
      <div class="max-w-full mx-auto pb-10 sm:px-6 lg:px-8">
        <v-card>
          <v-card-title>
            <v-text-field
                v-model="form.search"
                append-icon="mdi-magnify"
                label="Cerca..."
                single-line
                hide-details />
          </v-card-title>
          <v-data-table
              class="cursor-pointer text-left"
              :options.sync="options"
              :custom-sort="getReservations"
              :multi-sort="false"
              :headers="headers"
              :items="reservations.data"
              :items-per-page="form.items_per_page"
              item-class="color"
              @click:row="handleClick"
              hide-default-footer>
            <template v-slot:top>
              <div class="flex justify-space-between">
                <div class="ml-3 mt-4 text-sm">
                  <v-btn
                      @click="loadData"
                      :disabled="reservationsChanged === 0 || loading === true"
                      class="disabled:opacity-50"
                      color="blue"
                      v-blur
                      fab
                      small>
                    <v-icon>mdi-refresh</v-icon>
                  </v-btn>
                  <span v-if="reservationsChanged === 0">Nessun aggiornamento</span>
                  <span v-else>Aggiornare per caricare le ultime modifiche</span>
                </div>
                <div class="w-30 mr-9">
                  <v-select
                      :items="[10, 15, 20, 35, 50, 100]"
                      v-model="form.items_per_page"
                      label="Per Pagina"
                  ></v-select>
                </div>
              </div>
            </template>
          </v-data-table>
          <v-row>
            <v-col cols="2" />
            <v-col cols="8">
              <v-pagination
                  class="mb-5 mt-5"
                  :disabled="reservations.last_page <= 1"
                  v-model="form.current_page"
                  :length="reservations.last_page">
              </v-pagination>
            </v-col>
          </v-row>
        </v-card>
      </div>
    </div>
  </app-layout>
</template>

<script>
import AppLayout from "../../Layouts/AppLayout";
import pickBy from 'lodash/pickBy'
import throttle from 'lodash/throttle'
import { mapValues } from "lodash";
import DateFilter from "../../Shared/DateFilter";
import TimeFilter from "../../Shared/TimeFilter";

export default {
  components: {
    AppLayout,
    DateFilter,
    TimeFilter,
  },

  props: {
    reservations: Object,
    last_update: String,
  },

  mounted() {
    this.reservationsTransform()
  },

  data() {
    return {
      reservationsChanged: 0,
      polling: null,
      loading: false,
      form: {
        from_time: null,
        to_time: null,
        from_date: null,
        to_date: null,
        state_filters: ['pending'],
        search: '',
        sort_order: null,
        sort_field: null,
        items_per_page: this.reservations.per_page,
        current_page: this.reservations.current_page
      },
      headers: [
        { text: 'ID', sortable: false, value: 'id'},
        { text: 'Nome', sortable: true, value: 'patient.account.user.name' },
        { text: 'Email', value: 'patient.account.user.email', sortable: true },
        { text: 'Vaccino', value: 'stock.batch.vaccine.name', sortable: false },
        { text: 'Dose', value: 'is_recall', sortable: false },
        { text: 'Stato Prenotazione', value: 'stateLabel', sortable: false },
        { text: 'Telefono', value: 'patient.account.mobile_phone', sortable: false },
        { text: 'Data Prenotazione', value: 'date', sortable: true },
        { text: 'Orario Prenotazione', value: 'time', sortable: true },
        { text: 'Creazione', value: 'created_at', sortable: true },
        { text: 'Aggiornamento', value: 'updated_at', sortable: true },
      ],
      options: { 'itemsPerPage': -1 },
    }
  },

  beforeDestroy() {
    clearInterval(this.polling)
  },

  created () {
    this.pollReservationsChanged()
    document.title = "Prenotazioni"
  },

  watch: {
    reservations: {
      handler () {
        this.reservationsTransform()
      }
    },

    options: {
      handler () {
        this.form.sort_field = this.options.sortBy.length > 0 ? this.options.sortBy[0] : null;
        this.form.sort_order = this.options.sortDesc.length > 0 ? (this.options.sortDesc[0] ? 'desc' : 'asc') : null;
      },
      deep: true,
    },

    form: {
      handler: throttle(function() {
        this.loadData();
      }, 150),
      deep: true,
    },
  },

  metaInfo: { title: 'Prenotazioni' },

  methods: {
    pollReservationsChanged() {
      this.polling = setInterval(() => {
        this.loading = true
        this.axios.post(route('reservations.poll',
            {
              'last_update': this.last_update,
              'structure_id': this.$page.props.responsible.responsible.structure_id
            })).then((response) => {
          this.reservationsChanged = response.data
        }).catch((error) => {
          console.log(error)
        }).finally(() => {
          this.loading = false
        })
      }, 7000)
    },

    updateFromTime(fromTime) {
      this.form.from_time = fromTime
    },

    updateToTime(toTime) {
      this.form.to_time = toTime
    },

    updateDateFilters(dates) {
      if (Object.keys(dates).includes('from_date')) this.form.from_date = dates['from_date']
      if (Object.keys(dates).includes('to_date')) this.form.to_date = dates['to_date']
    },

    reservationsTransform() {
      this.reservations['data'].map(function(content, index) {
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

    getReservations() {
      return this.reservations.data;
    },

    handleClick(value) {
      this.$inertia.get(this.route('reservations.edit', value.id));
    },

    loadData() {
      let query = pickBy(this.form);
      let newQuery = {};
      Object.keys(query).forEach(function (key) {
        newQuery[key] = query[key];
      });
      this.$inertia.post(window.location.pathname, newQuery, { preserveScroll: true, preserveState: true });
      this.reservationsChanged = 0
    },

    createStudentRedirect() {
      this.$inertia.post(this.route('reservations.create'));
    },

    reset() {
      this.form = mapValues(this.form, () => null)
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
